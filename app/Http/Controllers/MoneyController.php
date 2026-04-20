<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Budget;
use Inertia\Inertia;
use Carbon\Carbon;

class MoneyController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // ─── Core Transactions ───────────────────────────────────────────
        $transactions = DB::table('transactions')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        $totalIncome  = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance      = $totalIncome - $totalExpense;

        // ─── Budget ───────────────────────────────────────────────────────
        $budgetSummary = Budget::getSummaryForUser($userId);
        $activeBudget  = Budget::where('user_id', $userId)
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        // ─── Recurring Expenses (separated by frequency) ──────────────────
        $recurringAll = DB::table('recurring_transactions')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->get();

        $recurringDaily   = $recurringAll->where('frequency', 'daily')->values();
        $recurringMonthly = $recurringAll->whereIn('frequency', ['monthly', 'yearly', 'weekly'])->values();

        // ─── Today's Plan (daily recurring ONLY + actual today spending) ──
        $todayActual = DB::table('transactions')
            ->where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString())
            ->where('type', 'expense')
            ->get();

        $todayRecurringTotal = $recurringDaily->sum('amount');
        $todayActualTotal    = $todayActual->sum('amount');
        $fixedDailyAllowance = $budgetSummary ? $budgetSummary['daily_allowance'] : 0;
        
        $dailyAllowance      = $fixedDailyAllowance + $todayRecurringTotal;

        $todayPlan = [
            'allowance'      => $dailyAllowance,
            'recurring_total'=> $todayRecurringTotal,
            'actual_total'   => $todayActualTotal,
            'remaining'      => $dailyAllowance - $todayActualTotal,
            'daily_items'    => $recurringDaily->map(fn($r) => [
                'category' => $r->category,
                'amount'   => $r->amount,
                'description' => $r->description,
            ]),
            'actual_items'   => $todayActual->map(fn($t) => [
                'category' => $t->category,
                'amount'   => $t->amount,
            ]),
        ];

        // ─── Reports ──────────────────────────────────────────────────────
        // Daily: last 7 days
        $dailyReport = collect(range(6, 0))->map(function ($daysAgo) use ($userId) {
            $date = now()->subDays($daysAgo)->toDateString();
            $expenses = DB::table('transactions')
                ->where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->where('type', 'expense')
                ->sum('amount');
            $income = DB::table('transactions')
                ->where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->where('type', 'income')
                ->sum('amount');
            return [
                'date'    => $date,
                'label'   => Carbon::parse($date)->format('D'),
                'expense' => (float) $expenses,
                'income'  => (float) $income,
            ];
        });

        // Weekly: last 4 weeks
        $weeklyReport = collect(range(3, 0))->map(function ($weeksAgo) use ($userId) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek()->toDateString();
            $end   = now()->subWeeks($weeksAgo)->endOfWeek()->toDateString();
            $expenses = DB::table('transactions')
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->where('type', 'expense')
                ->sum('amount');
            return [
                'label'   => 'W' . (4 - $weeksAgo),
                'expense' => (float) $expenses,
            ];
        });

        // Monthly: by category
        $currentMonthStart = now()->startOfMonth()->toDateString();
        $categoryReport    = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $currentMonthStart)
            ->where('type', 'expense')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get()
            ->map(fn($r) => ['category' => $r->category, 'total' => (float) $r->total]);

        // Savings tip: leftover this month
        $monthlyExpense = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $currentMonthStart)
            ->where('type', 'expense')
            ->sum('amount');
        $monthlyIncome = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $currentMonthStart)
            ->where('type', 'income')
            ->sum('amount');
        $monthlySaved = max(0, $monthlyIncome - $monthlyExpense);

        return Inertia::render('Money', [
            'transactions'     => $transactions,
            'summary'          => [
                'income'   => $totalIncome,
                'expense'  => $totalExpense,
                'balance'  => $balance,
            ],
            'active_budget'    => $activeBudget,
            'budget_summary'   => $budgetSummary,
            'today_plan'       => $todayPlan,
            'recurring_daily'  => $recurringDaily,
            'recurring_monthly'=> $recurringMonthly,
            'reports'          => [
                'daily'    => $dailyReport,
                'weekly'   => $weeklyReport,
                'category' => $categoryReport,
                'monthly_saved' => (float) $monthlySaved,
            ],
        ]);
    }

    public function storeBudget(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:1',
            'period_type' => 'required|in:weekly,monthly',
        ]);

        $endDate = $request->period_type === 'weekly'
            ? now()->addDays(6)->toDateString()
            : now()->endOfMonth()->toDateString();

        Budget::create([
            'user_id'     => $request->user()->id,
            'amount'      => $request->amount,
            'period_type' => $request->period_type,
            'start_date'  => now()->toDateString(),
            'end_date'    => $endDate,
        ]);

        return back();
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0',
            'type'        => 'required|in:income,expense',
            'category'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_recurring'=> 'boolean',
            'frequency'   => 'required_if:is_recurring,true|in:daily,weekly,monthly,yearly',
        ]);

        $user = $request->user();

        DB::table('transactions')->insert([
            'user_id'    => $user->id,
            'amount'     => $request->amount,
            'type'       => $request->type,
            'category'   => $request->category ?? 'عام',
            'description'=> $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->is_recurring) {
            $nextDate = match ($request->frequency) {
                'daily'   => now()->addDay()->toDateString(),
                'weekly'  => now()->addWeek()->toDateString(),
                'monthly' => now()->addMonth()->toDateString(),
                'yearly'  => now()->addYear()->toDateString(),
                default   => now()->toDateString(),
            };

            \App\Models\RecurringTransaction::create([
                'user_id'    => $user->id,
                'amount'     => $request->amount,
                'type'       => $request->type,
                'category'   => $request->category ?? 'عام',
                'description'=> $request->description,
                'frequency'  => $request->frequency,
                'start_date' => now()->toDateString(),
                'next_date'  => $nextDate,
                'is_active'  => true,
            ]);
        }

        return back();
    }

    public function destroy(Request $request, $id)
    {
        DB::table('transactions')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();
        return back();
    }

    public function analyze(Request $request)
    {
        $transactions = DB::table('transactions')->where('user_id', $request->user()->id)->get();
        if ($transactions->isEmpty()) {
            return response()->json(['plan' => 'أنت لم تقم بتسجيل أي مصروفات أو دخل بعد. الذكاء الاصطناعي بانتظار بياناتك!']);
        }

        $totalIncome  = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance      = $totalIncome - $totalExpense;

        $expenses = $transactions->where('type', 'expense')->groupBy('category')
            ->map(fn($rows) => collect($rows)->sum('amount'));

        $expensesText = collect($expenses)->map(fn($v, $k) => "- $k: $v")->implode("\n");

        $prompt = "دخلي هو $totalIncome ومصاريفي $totalExpense والرصيد $balance. المصاريف حسب الفئات:\n$expensesText\n\nحلل وضعي المالي وأعطني 3 نصائح عملية مختصرة (جملة لكل نصيحة) لتوفير المال. اجعل الرد عربياً وودياً ومشجعاً.";

        try {
            $response = Http::timeout(30)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'أنت مستشار مالي ذكي ومختصر. ردودك دائماً بالعربية وعملية وقصيرة.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens'  => 512,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $plan = $response->json()['choices'][0]['message']['content'] ?? 'حاول مجدداً.';
                return response()->json(['plan' => $plan]);
            }
        } catch (\Exception $e) {}

        return response()->json(['plan' => 'المستشار غير متاح حالياً، جرب لاحقاً.']);
    }

    public function aiSuggestions(Request $request)
    {
        $category = $request->input('category', 'طعام');
        $budget   = $request->input('budget', 50);
        $lang     = $request->input('lang', 'ar');

        $prompt = "أنا لدي ميزانية $budget دولار لـ '$category' اليوم. اقترح لي 3 خيارات عملية ومحددة من المطبخ الفلسطيني أو السوري أو الشرق أوسطي بهذه الميزانية. الرد: 3 عناصر فقط، كل عنصر في سطر، ابدأ كل سطر بـ •. هام جداً: يجب أن يكون الرد باللغة العربية حصراً.";


        try {
            $response = Http::timeout(15)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens'  => 200,
                    'temperature' => 0.8,
                ]);

            if ($response->successful()) {
                $text = $response->json()['choices'][0]['message']['content'] ?? '';
                $lines = collect(explode("\n", $text))
                    ->filter(fn($l) => str_starts_with(trim($l), '•'))
                    ->map(fn($l) => trim(ltrim(trim($l), '•')))
                    ->values()
                    ->toArray();
                return response()->json(['suggestions' => $lines]);
            }
        } catch (\Exception $e) {}

        return response()->json(['suggestions' => []]);
    }

    public function savingsTip(Request $request)
    {
        $saved = $request->input('saved', 0);
        $lang  = $request->input('lang', 'ar');

        $prompt = $lang === 'ar'
            ? "لدي $saved دولار متبقية هذا الشهر. اقترح لي 3 أفكار ذكية لادخارها أو استثمارها (مثل شراء ذهب، أو توفير أو فكرة صغيرة). كل فكرة سطر واحد يبدأ بـ 💡."
            : "I have $saved dollars left this month. Suggest 3 smart saving or investment ideas. Each idea on one line starting with 💡.";

        try {
            $response = Http::timeout(15)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens'  => 200,
                    'temperature' => 0.8,
                ]);

            if ($response->successful()) {
                $text = $response->json()['choices'][0]['message']['content'] ?? '';
                return response()->json(['tip' => $text]);
            }
        } catch (\Exception $e) {}

        return response()->json(['tip' => '']);
    }

    public function forecast(Request $request)
    {
        $user = $request->user();
        $transactions = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(60))
            ->get();

        if ($transactions->isEmpty()) {
            return response()->json(['forecast' => []]);
        }

        $summary = $transactions->groupBy(function ($date) {
            return Carbon::parse($date->created_at)->format('Y-m');
        })->map(fn($row) => [
            'income'  => $row->where('type', 'income')->sum('amount'),
            'expense' => $row->where('type', 'expense')->sum('amount'),
        ]);

        $prompt = "Based on these monthly financial stats: " . json_encode($summary) .
            " Predict the balance for the next 3 months. RESPOND ONLY WITH JSON ARRAY of 3 numbers. Format: [1200, 1500, 1100]";

        try {
            $response = Http::timeout(15)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

            if ($response->successful()) {
                $json = $response->json()['choices'][0]['message']['content'] ?? '[0,0,0]';
                $json = preg_replace('/[^0-9,\[\]\.-]/', '', $json);
                return response()->json(['forecast' => json_decode($json) ?: [0, 0, 0]]);
            }
        } catch (\Exception $e) {}

        return response()->json(['forecast' => [0, 0, 0]]);
    }
}
