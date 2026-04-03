<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class MoneyController extends Controller
{
    public function index(Request $request)
    {
        $transactions = DB::table('transactions')->where('user_id', $request->user()->id)->orderBy('id', 'desc')->get();
        
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        return Inertia::render('Money', [
            'transactions' => $transactions,
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $balance
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:income,expense',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::table('transactions')->insert([
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'type' => $request->type,
            'category' => $request->category ?? 'عام',
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function destroy(Request $request, $id)
    {
        DB::table('transactions')->where('id', $id)->where('user_id', $request->user()->id)->delete();
        return back();
    }

    public function analyze(Request $request)
    {
        $transactions = DB::table('transactions')->where('user_id', $request->user()->id)->get();
        if ($transactions->isEmpty()) {
            return response()->json(['plan' => 'أنت لم تقم بتسجيل أي مصروفات أو دخل بعد. الذكاء الاصطناعي بانتظار بياناتك!']);
        }

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $expenses = $transactions->where('type', 'expense')->groupBy('category')->map(function ($row) {
            return collect($row)->sum('amount');
        });
        
        $expensesText = "";
        foreach ($expenses as $cat => $sum) {
            $expensesText .= "- $cat: $sum\n";
        }

        $prompt = "دخلي هو $totalIncome ومصاريفي $totalExpense والرصيد $balance. وكانت مصروفي ضمن هذه الفئات:\n$expensesText\n\nأرجوك حلل حالتي المالية وعادات الإنفاق لدي وقدم لي خطة بسيطة لتوفير المال والتحذير من الخطر، وماذا تتوقع إن استمريت هكذا؟ استخدم نقط ورسائل تشجيعية.";

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.groq.key'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'أنت المستشار المالي الشخصي للمستخدم، خبير بالاقتصاد وتوجيه الأموال والميزانيات وصريح جداً ومختصر.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 1024,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $plan = $data['choices'][0]['message']['content'] ?? 'لن نتمكن من تحليل المالية الآن.';
                return response()->json(['plan' => $plan]);
            }
            return response()->json(['plan' => 'فشل الاتصال بالمستشار المالي الذكي. خطأ: ' . $response->status()]);
        } catch (\Exception $e) {
            return response()->json(['plan' => 'حدث خطأ في الاتصال بالمستشار المالي الذكي.']);
        }
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

        $summary = $transactions->groupBy(function($date) {
            return \Carbon\Carbon::parse($date->created_at)->format('Y-m');
        })->map(function ($row) {
            return [
                'income' => $row->where('type', 'income')->sum('amount'),
                'expense' => $row->where('type', 'expense')->sum('amount')
            ];
        });

        $prompt = "Based on these monthly financial stats: " . json_encode($summary) . "
        Predict the balance for the next 3 months. 
        RESPOND ONLY WITH JSON ARRAY of 3 numbers representing the predicted balance for each month.
        Format: [1200, 1500, 1100]";

        try {
            $response = Http::timeout(15)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
            $json = $response->json()['choices'][0]['message']['content'];
            // تنظيف النص في حال الـ AI أضاف كلاماً
            $json = preg_replace('/[^0-9,\[\]\.-]/', '', $json);
            return response()->json(['forecast' => json_decode($json)]);
        } catch (\Exception $e) {
            return response()->json(['forecast' => [0, 0, 0]]);
        }
    }
}
