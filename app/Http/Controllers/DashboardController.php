<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        
        $user = $request->user();
        
        $tasks = DB::table('tasks')->where('user_id', $user->id)->get();
        $habit = DB::table('habits')->where('user_id', $user->id)->first();
        
        // --- Added for Neural Logic & Dashboard Overview ---
        $transactions = DB::table('transactions')->where('user_id', $user->id)->get();
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        
        $pendingTasksCount = $tasks->where('status', 'pending')->count();
        $completedTasksCount = $tasks->where('status', 'completed')->count();
        $taskFactor = ($pendingTasksCount + $completedTasksCount) > 0 
            ? ($completedTasksCount / ($pendingTasksCount + $completedTasksCount)) * 100 
            : 100;

        $avgDecisionScore = (int)DB::table('decisions')->where('user_id', $user->id)->avg(DB::raw("CAST(JSON_EXTRACT(ai_advice, '$.score') AS UNSIGNED)")) ?: 0;
        
        // --- Calculate Stability Index (Weighted 1-100) ---
        // Finance (40%), Tasks (30%), Decisions (30%)
        $financeScore = $totalIncome > 0 ? max(0, min(100, ($balance / $totalIncome) * 100)) : 50;
        $stabilityIndex = (int)(($financeScore * 0.4) + ($taskFactor * 0.3) + ($avgDecisionScore * 0.3));

        $lastIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->first();
        $personToContact = DB::table('people')->where('user_id', $user->id)->inRandomOrder()->first();

        $goal = DB::table('goals')->where('user_id', $user->id)->where('status', 'pending')->latest()->first();

        return Inertia::render('Dashboard', [
            'tasks' => $tasks,
            'habit' => $habit,
            'goal' => $goal,
            'overview' => [
                'balance' => $balance,
                'last_idea' => $lastIdea ? $lastIdea->content : null,
                'person_to_contact' => $personToContact ? $personToContact->name : null,
                'stability_index' => $stabilityIndex,
                'decision_logic_avg' => $avgDecisionScore,
                'sealed_decisions_count' => DB::table('decisions')->where('user_id', $user->id)->whereNotNull('final_decision')->count(),
                'income_expense_ratio' => $totalIncome > 0 ? (int)(($totalExpense / $totalIncome) * 100) : 0,
            ],
            'shadow_prediction' => $this->getShadowPrediction($user, $balance, $pendingTasksCount),
            'harmony_score' => $this->calculateHarmony($balance, $pendingTasksCount, $completedTasksCount),
            'daily_briefing' => $this->getDailyBriefing($user, $balance, $pendingTasksCount)
        ]);
    }

    private function calculateHarmony($balance, $pending, $done)
    {
        // معادلة التناغم: توازن بين المال والإنتاجية
        $moneyScore = $balance > 0 ? 40 : 10;
        $taskScore = ($pending + $done) > 0 ? ($done / ($pending + $done)) * 60 : 30;
        return (int)($moneyScore + $taskScore);
    }

    private function getDailyBriefing($user, $balance, $tasksCount)
    {
        $recentPerson = DB::table('people')->where('user_id', $user->id)->where('importance', 'عالية')->orderBy('last_contact', 'asc')->first();
        $recentIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->value('content');
        
        $prompt = "Generate a short, super professional daily briefing for the user in 3 short sentences. 
        Context: Balance is $balance$. Pending Tasks: $tasksCount. Recent Idea: \"$recentIdea\". 
        Important Person to contact: \"". ($recentPerson->name ?? 'None') ."\". 
        Tone: Strategic, encouraging, wise master. Language: Arabic.";

        try {
            $response = Http::timeout(15)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
            return $response->json()['choices'][0]['message']['content'] ?? 'مستعد ليوم عظيم؟';
        } catch (\Exception $e) {
            return 'العقل قيد التحديث...';
        }
    }


    private function getShadowPrediction($user, $balance, $tasksCount)
    {
        $recentIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->value('content');
        $prompt = "You are the Predictive Shadow Brain. Based on: Balance: $balance$, Pending Tasks: $tasksCount, Recent Idea: \"$recentIdea\".
        Predict the user's near future vibe and give 1 shocking strategic insight in Arabic. Max 2 short sentences. No greeting.";

        try {
            $response = Http::timeout(10)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
            return $response->json()['choices'][0]['message']['content'] ?? 'المستقبل غامض حالياً...';
        } catch (\Exception $e) {
            return 'جاري قراءة المسارات العصبية...';
        }
    }

    public function generatePlan(Request $request)
    {
        $user = $request->user();
        
        $tasks = DB::table('tasks')->where('user_id', $user->id)
            ->get()->pluck('title')->toArray();
        $habit = DB::table('habits')->where('user_id', $user->id)->first();
        
        // --- Contextual Data for Neural Bridge ---
        $income = DB::table('transactions')->where('user_id', $user->id)->where('type', 'income')->sum('amount');
        $expense = DB::table('transactions')->where('user_id', $user->id)->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;
        
        $ideasCount = DB::table('ideas')->where('user_id', $user->id)->count();
        $peopleCount = DB::table('people')->where('user_id', $user->id)->count();
        $lastIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->first();

        $tasksList = count($tasks) > 0 ? implode(', ', $tasks) : 'لا يوجد مهام';
        $habitName = $habit ? $habit->name : 'لا يوجد عادات';
        $locale = $request->input('locale', 'ar');
        
        $prompt = $locale === 'ar' 
            ? "أنت العقل المساعد الشامل لنظام Personal Memory OS. \n" .
              "سياق المستخدم الحالي: \n" .
              "- الرصيد المالي: {$balance}$ \n" .
              "- عدد الأفكار المسجلة: {$ideasCount} \n" .
              "- عدد المعارف المسجلين: {$peopleCount} \n" .
              "- آخر فكرة: " . ($lastIdea ? $lastIdea->content : 'لا توجد') . "\n" .
              "- مهام اليوم: [{$tasksList}] \n" .
              "- العادة الحالية: [{$habitName}] \n\n" .
              "اكتب تحليلاً استراتيجياً عميقاً يربط بين حالته المالية ومهامه وأفكاره. كن ملهماً، صريحاً، وقدم نصائح عملية جداً باللغة العربية."
            : "You are the Global Intelligence of Personal Memory OS. \n" .
              "User Context: \n" .
              "- Balance: {$balance}$ \n" .
              "- Total Ideas: {$ideasCount} \n" .
              "- People in memory: {$peopleCount} \n" .
              "- Last Idea: " . ($lastIdea ? $lastIdea->content : 'None') . "\n" .
              "- Tasks to do: [{$tasksList}] \n" .
              "- Current Habit: [{$habitName}] \n\n" .
              "Provide a deep strategic analysis linking their finances, tasks, and ideas. Be inspiring, direct, and practical in English.";

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
                    [
                        'role' => 'system', 
                        'content' => 'You are a helpful productivity assistant.'
                    ],
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $plan = $data['choices'][0]['message']['content'] ?? 'لم يتم الحصول على رد.';
            return response()->json(['plan' => $plan]);
        }

        // لو فشل الـ request نشوف سبب الفشل
        return response()->json([
            'plan' => '⚠️ فشل الطلب: ' . $response->status() . ' | ' . $response->body()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'plan' => '⚠️ خطأ: ' . $e->getMessage()
        ]);
    }
}

    public function handleCommand(Request $request)
    {
        $command = $request->input('command');
        $user = $request->user();
        
        $prompt = "You are the Command Center of Personal Memory OS. 
        Analyze this user voice command: \"$command\".
        If it contains a financial expense/income, task, or idea, extract the data.
        RESPOND ONLY WITH JSON. 
        Format: {
          \"type\": \"money|task|idea|unknown\",
          \"data\": { ... relevant fields ... },
          \"reply\": \"A cool smart reply in Arabic\"
        }";

        try {
            $response = Http::timeout(20)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'response_format' => ['type' => 'json_object']
                ]);

            $json = $response->json()['choices'][0]['message']['content'];
            $res = json_decode($json, true);

            if ($res['type'] === 'money') {
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'amount' => $res['data']['amount'] ?? 0,
                    'type' => $res['data']['type'] ?? 'expense',
                    'category' => $res['data']['category'] ?? 'عام',
                    'description' => $res['data']['description'] ?? $command,
                    'created_at' => now(), 'updated_at' => now()
                ]);
            } elseif ($res['type'] === 'task') {
                DB::table('tasks')->insert([
                    'user_id' => $user->id,
                    'title' => $res['data']['title'] ?? $command,
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now()
                ]);
            } elseif ($res['type'] === 'idea') {
                DB::table('ideas')->insert([
                    'user_id' => $user->id,
                    'content' => $res['data']['content'] ?? $command,
                    'status' => 'draft', 'category' => 'ذكية', 'created_at' => now(), 'updated_at' => now()
                ]);
            }

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['reply' => 'لم أفهم الأمر بدقة، حاول مجدداً.', 'type' => 'unknown']);
        }
    }


    

    public function storeTask(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255']);
        DB::table('tasks')->insert([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back();
    }

    public function toggleTask(Request $request, $id)
    {
        $task = DB::table('tasks')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if ($task) {
            $newStatus = $task->status === 'completed' ? 'pending' : 'completed';
            DB::table('tasks')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => now()]);
        }
        return back();
    }

    public function storeHabit(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $existing = DB::table('habits')->where('user_id', $request->user()->id)->first();
        if ($existing) {
            DB::table('habits')->where('id', $existing->id)->update([
                'name' => $request->name,
                'updated_at' => now()
            ]);
        } else {
            DB::table('habits')->insert([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'frequency' => 'daily',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return back();
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        if (!$q) return response()->json([]);

        $user_id = $request->user()->id;

        $ideas = DB::table('ideas')
            ->where('user_id', $user_id)
            ->where('content', 'like', "%$q%")
            ->select('id', 'content as title', DB::raw("'ideas' as type"))
            ->limit(5)->get();

        $people = DB::table('people')
            ->where('user_id', $user_id)
            ->where('name', 'like', "%$q%")
            ->select('id', 'name as title', DB::raw("'people' as type"))
            ->limit(5)->get();

        $tasks = DB::table('tasks')
            ->where('user_id', $user_id)
            ->where('title', 'like', "%$q%")
            ->select('id', 'title', DB::raw("'tasks' as type"))
            ->limit(5)->get();

        $decisions = DB::table('decisions')
            ->where('user_id', $user_id)
            ->where('problem', 'like', "%$q%")
            ->select('id', 'problem as title', DB::raw("'decisions' as type"))
            ->limit(5)->get();

        return response()->json($ideas->concat($people)->concat($tasks)->concat($decisions));
    }

    public function storeGoal(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255']);
        
        // مارك الأهداف القديمة كمنتهية أو حذفها (نحن نريد هدف واحد نشط لليوم)
        DB::table('goals')->where('user_id', $request->user()->id)->update(['status' => 'archived']);

        DB::table('goals')->insert([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back();
    }
}
