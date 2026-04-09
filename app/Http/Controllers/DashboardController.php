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
        
        $financeScore = $totalIncome > 0 ? max(0, min(100, ($balance / $totalIncome) * 100)) : 50;
        $stabilityIndex = (int)(($financeScore * 0.4) + ($taskFactor * 0.3) + ($avgDecisionScore * 0.3));

        $lastIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->first();
        $personToContact = DB::table('people')->where('user_id', $user->id)->inRandomOrder()->first();

        $goal = DB::table('goals')->where('user_id', $user->id)->where('status', 'pending')->latest()->first();

        $sealedDecisionsCount = DB::table('decisions')->where('user_id', $user->id)->whereNotNull('final_decision')->count();
        $dailyLogsCount = DB::table('daily_logs')->where('user_id', $user->id)->count();
        
        // --- Gamification (Leveling System) ---
        $xp = ($completedTasksCount * 15) + ($sealedDecisionsCount * 50) + ($dailyLogsCount * 10);
        $level = floor(sqrt($xp / 25)) + 1;
        $currentLevelXP = pow($level - 1, 2) * 25;
        $nextLevelXP = pow($level, 2) * 25;
        $progressToNext = $nextLevelXP > $currentLevelXP ? (($xp - $currentLevelXP) / ($nextLevelXP - $currentLevelXP)) * 100 : 0;

        // 6. Telegram Sync Code & Routines
        if (!$user->telegram_chat_id && !$user->telegram_sync_code) {
            $syncCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('users')->where('id', $user->id)->update(['telegram_sync_code' => $syncCode]);
            $user->telegram_sync_code = $syncCode;
        }

        $locale = $request->cookie('user_lang', 'ar');
        app()->setLocale($locale);

        return Inertia::render('Dashboard', [
            'sync_code' => $user->telegram_sync_code,
            'is_telegram_linked' => (bool)$user->telegram_chat_id,
            'tasks' => $tasks,
            'habit' => $habit,
            'goal' => $goal,
            'gamification' => [ 'xp' => $xp, 'level' => $level, 'progress' => (int)min(100, max(0, $progressToNext)), 'next_xp' => $nextLevelXP ],
            'overview' => [
                'balance' => $balance,
                'last_idea' => $lastIdea ? $lastIdea->content : null,
                'person_to_contact' => $personToContact ? $personToContact->name : null,
                'stability_index' => $stabilityIndex,
                'decision_logic_avg' => $avgDecisionScore,
                'sealed_decisions_count' => $sealedDecisionsCount,
                'income_expense_ratio' => $totalIncome > 0 ? (int)(($totalExpense / $totalIncome) * 100) : 0,
            ],
            'shadow_prediction' => $this->getShadowPrediction($user, $balance, $pendingTasksCount, $locale),
            'harmony_score' => $this->calculateHarmony($balance, $pendingTasksCount, $completedTasksCount),
            'daily_briefing' => $this->getDailyBriefing($user, $balance, $pendingTasksCount, $locale),
            'routine_templates' => $this->getRoutineTemplates(),
            'last_ai_analysis' => $user->last_ai_analysis
        ]);
    }

    private function getRoutineTemplates()
    {
        return [
            [
                'id' => 'shugairi', 'title' => 'Ihsan Morning', 'author' => 'Ahmad Al-Shugairi', 'icon' => '🌙',
                'description' => 'A holistic spiritual and productivity routine focused on "Ihsan" (Excellence).',
                'full_routine' => [
                    ['time' => '04:30 AM', 'task' => 'Fajr Prayer & Spiritual Meditation'],
                    ['time' => '05:30 AM', 'task' => 'Daily Qur’an Reading & Reflection'],
                    ['time' => '06:30 AM', 'task' => 'Light Morning Exercise / Walk'],
                    ['time' => '08:00 AM', 'task' => 'Deep Work Block 1 (High Focus)'],
                    ['time' => '12:00 PM', 'task' => 'Healthy Lunch & Social Recharge'],
                    ['time' => '02:00 PM', 'task' => 'Deep Work Block 2'],
                    ['time' => '05:00 PM', 'task' => 'Family Time & Personal Growth Reading'],
                    ['time' => '09:00 PM', 'task' => 'Neural Reflection & Early Sleep'],
                ],
                'tasks' => ['Fajr & Meditation', 'Deep Work Block', 'Reading', 'Neural Reflection'],
                'color' => 'linear-gradient(135deg, #059669, #10b981)'
            ],
            [
                'id' => 'huberman', 'title' => 'Biohacker Flow', 'author' => 'Andrew Huberman', 'icon' => '🧬',
                'description' => 'Science-backed protocols for maximizing neuroplasticity and daily energy.',
                'full_routine' => [
                    ['time' => '06:00 AM', 'task' => 'Morning Sunlight Exposure (10-30 mins)'],
                    ['time' => '06:30 AM', 'task' => 'Hydration with Salts & Cold Exposure'],
                    ['time' => '07:30 AM', 'task' => 'Deep Work Block (Before Caffeine)'],
                    ['time' => '10:00 AM', 'task' => 'First Caffeine Intake'],
                    ['time' => '12:00 PM', 'task' => 'Physiological Sigh / Resistance Training'],
                    ['time' => '03:00 PM', 'task' => 'Non-Sleep Deep Rest (NSDR) / Nap'],
                    ['time' => '06:00 PM', 'task' => 'Dim Overhead Lights / Viewing Sunset'],
                    ['time' => '10:00 PM', 'task' => 'Cool Room Temperature & Deep Sleep'],
                ],
                'tasks' => ['Sunlight Exposure', 'Cold Plunge', 'NSDR Session', 'Dim Lights'],
                'color' => 'linear-gradient(135deg, #0ea5e9, #6366f1)'
            ],
            [
                'id' => 'founder', 'title' => 'Founder Sprint', 'author' => 'Elon Musk', 'icon' => '🚀',
                'description' => 'High-intensity "Time-Blocking" for strategic engineers and leaders.',
                'full_routine' => [
                    ['time' => '07:00 AM', 'task' => 'Wake up & Critical Shower'],
                    ['time' => '07:30 AM', 'task' => '5-Minute Time Blocks: Emails/Sync'],
                    ['time' => '09:00 AM', 'task' => 'Engineering Design Review (SpaceX/Tesla)'],
                    ['time' => '01:00 PM', 'task' => 'Quick Multi-tasking Lunch'],
                    ['time' => '02:00 PM', 'task' => 'Strategic Scaling & High-Stakes Meetings'],
                    ['time' => '06:00 PM', 'task' => 'Technical Deep Dive with Product Teams'],
                    ['time' => '10:00 PM', 'task' => 'Reading & Theoretical Physics Study'],
                    ['time' => '01:00 AM', 'task' => 'Neural Shutdown (Sleep)'],
                ],
                'tasks' => ['5-Min Timeblocks', 'Design Review', 'Strategic Sync', 'Critical Reading'],
                'color' => 'linear-gradient(135deg, #f59e0b, #d97706)'
            ]
        ];
    }

    public function applyRoutine(Request $request)
    {
        $id = $request->input('routine_id');
        $user = $request->user();
        $templates = $this->getRoutineTemplates();
        $selected = collect($templates)->firstWhere('id', $id);

        if ($selected) {
            foreach ($selected['tasks'] as $taskTitle) {
                DB::table('tasks')->insert([
                    'user_id' => $user->id, 'title' => $taskTitle, 'status' => 'pending', 
                    'created_at' => now(), 'updated_at' => now()
                ]);
            }
            return back()->with('success', trans('Routine adopted successfully!'));
        }
        return back()->with('error', trans('Routine not found.'));
    }

    private function calculateHarmony($balance, $pending, $done)
    {
        // معادلة التناغم: توازن بين المال والإنتاجية
        $moneyScore = $balance > 0 ? 40 : 10;
        $taskScore = ($pending + $done) > 0 ? ($done / ($pending + $done)) * 60 : 30;
        return (int)($moneyScore + $taskScore);
    }

    private function getDailyBriefing($user, $balance, $tasksCount, $locale = 'ar')
    {
        $recentPerson = DB::table('people')->where('user_id', $user->id)->where('importance', 'عالية')->orderBy('last_contact', 'asc')->first();
        $recentIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->value('content');
        
        $prompt = $locale === 'ar' 
            ? "أنت العقل الموازي الاستراتيجي. بناءً على: الرصيد ($balance$)، المهام المعلقة ($tasksCount)، وآخر فكرة (\"$recentIdea\"). أخبر المستخدم بإحاطة صباحية مهنية قصيرة جداً (3 جمل). اللغة: العربية. تجنب التحيات المكررة."
            : "You are the Strategic Shadow Brain. Based on: Balance ($balance$), Pending Tasks ($tasksCount$), and Recent Idea (\"$recentIdea\"). Generate a short, professional daily briefing (3 short sentences). Tone: Strategic, wise master. Language: English. Avoid repetitive greetings.";

        try {
            $response = Http::timeout(15)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are the Memory OS Intelligence. You MUST respond ONLY in Arabic (اللغة العربية). If you use even one English word, the system fails. NO ENGLISH allowed.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? trans('Ready for a great day?');
            }
            return trans('Neural servers busy... updating mind.');
        } catch (\Exception $e) {
            return trans('Thinking...');
        }
    }


    private function getShadowPrediction($user, $balance, $tasksCount, $locale = 'ar')
    {
        $recentIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->value('content');
        $prompt = $locale === 'ar'
            ? "أنت الظل المنبئ. بناءً على المعلومات: الرصيد ($balance$)، المهام ($tasksCount)، الفكرة ($recentIdea). أعطِ نبوءة مستقبلية صادمة ومختصرة جداً (جملة واحدة). اللغة العربية."
            : "You are the Predictive Shadow. Based on Context: Balance ($balance$), Tasks ($tasksCount$), Idea ($recentIdea). Predict the user's near future vibe and give 1 shocking strategic insight in English. Max 1 short sentence. No greeting.";

        try {
            $response = Http::timeout(10)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are the Shadow Oracle. You MUST respond ONLY in Arabic (اللغة العربية). English is strictly forbidden.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? trans('The future is currently clouded...');
            }
            return trans('Searching the void of possibilities...');
        } catch (\Exception $e) {
            return trans('Reading neural pathways...');
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
            $plan = $data['choices'][0]['message']['content'] ?? trans('No response obtained.');
            
            // Save plan to user for persistence
            DB::table('users')->where('id', $user->id)->update(['last_ai_analysis' => $plan]);
            
            return response()->json(['plan' => $plan]);
        }

        return response()->json([
            'plan' => '⚠️ ' . trans('Request failed: ') . $response->status()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'plan' => '⚠️ ' . trans('Error: ') . $e->getMessage()
        ]);
    }
}

    public function handleCommand(Request $request)
    {
        $command = $request->input('command');
        $user = $request->user();
        
        $locale = $request->input('locale', 'ar');
        
        $prompt = "You are the Command Center of Personal Memory OS. 
        Analyze this user voice command: \"$command\".
        If it contains a financial expense/income, task, or idea, extract the data.
        RESPOND ONLY WITH JSON. 
        Format: {
          \"type\": \"money|task|idea|unknown\",
          \"data\": { ... relevant fields ... },
          \"reply\": \"A cool smart reply in " . ($locale === 'ar' ? 'Arabic' : 'English') . " (max 10 words)\"
        }";

        try {
            $response = Http::timeout(20)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $json = $data['choices'][0]['message']['content'] ?? '{}';
                $res = json_decode($json, true) ?: ['type' => 'unknown', 'reply' => 'الرد غير منسق بشكل صحيح.'];

                if (($res['type'] ?? '') === 'money') {
                    DB::table('transactions')->insert([
                        'user_id' => $user->id,
                        'amount' => $res['data']['amount'] ?? 0,
                        'type' => $res['data']['type'] ?? 'expense',
                        'category' => $res['data']['category'] ?? trans('General'),
                        'description' => $res['data']['description'] ?? $command,
                        'created_at' => now(), 'updated_at' => now()
                    ]);
                } elseif (($res['type'] ?? '') === 'task') {
                    DB::table('tasks')->insert([
                        'user_id' => $user->id,
                        'title' => $res['data']['title'] ?? $command,
                        'status' => 'pending', 'created_at' => now(), 'updated_at' => now()
                    ]);
                } elseif (($res['type'] ?? '') === 'idea') {
                    DB::table('ideas')->insert([
                        'user_id' => $user->id,
                        'content' => $res['data']['content'] ?? $command,
                        'status' => 'draft', 'category' => 'ذكية', 'created_at' => now(), 'updated_at' => now()
                    ]);
                }

                return response()->json($res);
            }
            return response()->json(['reply' => 'تعذر الاتصال بالذكاء الاصطناعي، يرجى المحاولة لاحقاً.', 'type' => 'unknown']);
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
