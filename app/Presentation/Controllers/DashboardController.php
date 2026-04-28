<?php

namespace App\Presentation\Controllers;

use App\Application\UseCases\Ideas\GetIdeasUseCase;
use App\Application\UseCases\Money\GetBudgetSummaryUseCase;
use App\Application\UseCases\People\GetPeopleUseCase;
use App\Domain\Repositories\Contracts\PersonRepositoryInterface;
use App\Domain\Repositories\Contracts\TransactionRepositoryInterface;
use App\Infrastructure\Services\AIService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private GetIdeasUseCase $getIdeasUseCase,
        private GetBudgetSummaryUseCase $getBudgetSummaryUseCase,
        private GetPeopleUseCase $getPeopleUseCase,
        private PersonRepositoryInterface $personRepository,
        private TransactionRepositoryInterface $transactionRepository,
        private AIService $aiService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $userId = $user->id;

        // استخدام Use Cases
        $ideas = $this->getIdeasUseCase->execute($userId);
        $people = $this->getPeopleUseCase->execute($userId);

        // البيانات من الـ Repository
        $tasks = DB::table('tasks')->where('user_id', $userId)->get();
        $habit = DB::table('habits')->where('user_id', $userId)->first();

        // الحسابات المالية
        $transactions = DB::table('transactions')->where('user_id', $userId)->get();
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // احصائيات المهام
        $pendingTasksCount = $tasks->where('status', 'pending')->count();
        $completedTasksCount = $tasks->where('status', 'completed')->count();
        $taskFactor = ($pendingTasksCount + $completedTasksCount) > 0
            ? ($completedTasksCount / ($pendingTasksCount + $completedTasksCount)) * 100
            : 100;

        // متوسط درجات القرارات
        $avgDecisionScore = (int)DB::table('decisions')
            ->where('user_id', $userId)
            ->avg(DB::raw("CAST(JSON_EXTRACT(ai_advice, '$.score') AS UNSIGNED)")) ?: 0;

        $financeScore = $totalIncome > 0
            ? max(0, min(100, ($balance / $totalIncome) * 100))
            : 50;
        $stabilityIndex = (int)(($financeScore * 0.4) + ($taskFactor * 0.3) + ($avgDecisionScore * 0.3));

        $lastIdea = $ideas[0] ?? null;
        $personToContact = $people[array_rand($people)] ?? null;

        $goal = DB::table('goals')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $sealedDecisionsCount = DB::table('decisions')
            ->where('user_id', $userId)
            ->whereNotNull('final_decision')
            ->count();

        $dailyLogsCount = DB::table('daily_logs')->where('user_id', $userId)->count();

        // نظام الـ Gamification
        $xp = ($completedTasksCount * 15) + ($sealedDecisionsCount * 50) + ($dailyLogsCount * 10);
        $level = floor(sqrt($xp / 25)) + 1;
        $currentLevelXP = pow($level - 1, 2) * 25;
        $nextLevelXP = pow($level, 2) * 25;
        $progressToNext = $nextLevelXP > $currentLevelXP
            ? (($xp - $currentLevelXP) / ($nextLevelXP - $currentLevelXP)) * 100
            : 0;

        // تحديث Telegram sync code إذا لم يكن موجوداً
        if (!$user->telegram_chat_id && !$user->telegram_sync_code) {
            $syncCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('users')->where('id', $userId)->update(['telegram_sync_code' => $syncCode]);
            $user->telegram_sync_code = $syncCode;
        }

        $locale = $request->cookie('user_lang', 'ar');
        app()->setLocale($locale);

        $telegramToken = $user->telegram_bot_token ?: config('services.telegram.token');

        try {
            $webhookResponse = Http::get("https://api.telegram.org/bot" . $telegramToken . "/getWebhookInfo");
            $webhookUrl = $webhookResponse->json()['result']['url'] ?? 'NOT SET';
        } catch (\Exception $e) {
            $webhookUrl = 'ERROR CONNECTING';
        }

        return Inertia::render('Dashboard', [
            'app_url' => config('app.url'),
            'webhook_status' => $webhookUrl,
            'sync_code' => $user->telegram_sync_code,
            'is_telegram_linked' => (bool)$user->telegram_chat_id,
            'telegram_bot_token' => $user->telegram_bot_token,
            'tasks' => $tasks,
            'habit' => $habit,
            'goal' => $goal,
            'gamification' => [
                'xp' => $xp,
                'level' => $level,
                'progress' => (int)min(100, max(0, $progressToNext)),
                'next_xp' => $nextLevelXP
            ],
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
            'last_ai_analysis' => $user->last_ai_analysis,
            'ar_voice_dialect' => $user->ar_voice_dialect ?? 'ar-SA',
            'neural_nodes' => [
                'ideas' => collect($ideas)
                    ->map(fn($idea) => [
                        'id' => $idea->id,
                        'content' => $idea->content,
                        'category' => $idea->category
                    ])
                    ->slice(0, 8)
                    ->values(),
                'decisions' => DB::table('decisions')
                    ->where('user_id', $userId)
                    ->latest()
                    ->limit(6)
                    ->get(['id', 'problem']),
                'people' => collect($people)
                    ->map(fn($person) => [
                        'id' => $person->id,
                        'name' => $person->name,
                    ])
                    ->slice(0, 8)
                    ->values(),
            ],
        ]);
    }

    private function getRoutineTemplates(): array
    {
        return [
            [
                'id' => 'shugairi',
                'title' => 'Ihsan Morning',
                'author' => 'Ahmad Al-Shugairi',
                'icon' => '🌙',
                'description' => 'A holistic spiritual and productivity routine focused on "Ihsan" (Excellence).',
                'full_routine' => [
                    ['time' => '04:30 AM', 'task' => 'Fajr Prayer & Spiritual Meditation'],
                    ['time' => '05:30 AM', 'task' => 'Daily Qur\'an Reading & Reflection'],
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
                'id' => 'huberman',
                'title' => 'Biohacker Flow',
                'author' => 'Andrew Huberman',
                'icon' => '🧬',
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
                'id' => 'founder',
                'title' => 'Founder Sprint',
                'author' => 'Elon Musk',
                'icon' => '🚀',
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

    public function applyRoutine(Request $request): RedirectResponse
    {
        $id = $request->input('routine_id');
        $selectedTasks = $request->input('selected_tasks');
        $user = $request->user();

        if ($selectedTasks && is_array($selectedTasks)) {
            foreach ($selectedTasks as $taskTitle) {
                DB::table('tasks')->insert([
                    'user_id' => $user->id,
                    'title' => $taskTitle,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            return back()->with('success', trans('Routine adopted successfully!'));
        }

        $templates = $this->getRoutineTemplates();
        $selected = collect($templates)->firstWhere('id', $id);

        if ($selected) {
            foreach ($selected['tasks'] as $taskTitle) {
                DB::table('tasks')->insert([
                    'user_id' => $user->id,
                    'title' => $taskTitle,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            return back()->with('success', trans('Routine adopted successfully!'));
        }
        return back()->with('error', trans('Routine not found.'));
    }

    private function calculateHarmony($balance, $pending, $done): int
    {
        $moneyScore = $balance > 0 ? 40 : 10;
        $taskScore = ($pending + $done) > 0 ? ($done / ($pending + $done)) * 60 : 30;
        return (int)($moneyScore + $taskScore);
    }

    public function getDailyBriefing($user, $balance, $tasksCount, $locale = 'ar')
    {
        if ($user->last_daily_briefing && $user->updated_at > now()->startOfDay()) {
            // إرجاع الـ Briefing المحفوظ
        }

        $lastIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->first();

        $prompt = $locale === 'ar'
            ? "أنت العقل الموازي الاستراتيجي. بناءً على: الرصيد (${$balance})، المهام المعلقة ({$tasksCount}), " .
              "وآخر فكرة (\"" . ($lastIdea ? $lastIdea->content : 'لا توجد') . "\"). " .
              "أخبر المستخدم بإحاطة صباحية مهنية قصيرة جداً (3 جمل). اللغة: العربية فقط."
            : "You are the Strategic Shadow Brain. Based on: Balance (\${$balance}), Pending Tasks ({$tasksCount}). " .
              "Generate a short, professional daily briefing (3 sentences). Language: English only.";

        try {
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are Memory OS Intelligence. Respond ONLY in the user\'s language.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? trans('Ready for a great day?');
                DB::table('users')->where('id', $user->id)->update(['last_daily_briefing' => $content, 'updated_at' => now()]);
                return $content;
            }
            return $user->last_daily_briefing ?? trans('Neural servers busy... updating mind.');
        } catch (Exception $e) {
            return $user->last_daily_briefing ?? trans('Thinking...');
        }
    }

    public function updateDialect(Request $request): JsonResponse
    {
        $request->validate(['dialect' => 'required|string|max:10']);
        DB::table('users')->where('id', $request->user()->id)->update([
            'ar_voice_dialect' => $request->dialect,
            'updated_at' => now()
        ]);
        return response()->json(['status' => 'ok']);
    }

    private function getShadowPrediction($user, $balance, $tasksCount, $locale = 'ar')
    {
        $recentIdea = DB::table('ideas')->where('user_id', $user->id)->latest()->value('content');
        $prompt = $locale === 'ar'
            ? "أنت الظل المنبئ. بناءً على: الرصيد (${$balance})، المهام ({$tasksCount}), الفكرة ($recentIdea). " .
              "أعطِ نبوءة مستقبلية صادمة جداً (جملة واحدة). اللغة العربية فقط."
            : "You are the Predictive Shadow. Based on: Balance (\${$balance}), Tasks ({$tasksCount}), Idea ($recentIdea). " .
              "Predict the user's near future in 1 shocking sentence. English only.";

        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are the Shadow Oracle. Respond ONLY in the user\'s language.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? trans('The future is currently clouded...');
            }
            return trans('Searching the void of possibilities...');
        } catch (Exception $e) {
            return trans('Reading neural pathways...');
        }
    }

    public function generatePlan(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        $tasks = DB::table('tasks')->where('user_id', $userId)->get()->pluck('title')->toArray();
        $habit = DB::table('habits')->where('user_id', $userId)->first();

        $income = $this->transactionRepository->getIncomeInDateRange($userId, now()->startOfMonth(), now()->endOfMonth());
        $expense = $this->transactionRepository->getExpensesInDateRange($userId, now()->startOfMonth(), now()->endOfMonth());
        $balance = $income - $expense;

        $ideas = $this->getIdeasUseCase->execute($userId);
        $ideasCount = count($ideas);
        $people = $this->getPeopleUseCase->execute($userId);
        $peopleCount = count($people);
        $lastIdea = $ideas[0] ?? null;

        $tasksList = count($tasks) > 0 ? implode(', ', $tasks) : trans('No tasks');
        $habitName = $habit ? $habit->name : trans('No habits');
        $locale = $request->input('locale', 'ar');

        $prompt = $locale === 'ar'
            ? "أنت العقل المساعد الشامل لنظام Personal Memory OS. \n" .
              "سياق المستخدم الحالي: \n" .
              "- الرصيد المالي: \${$balance} \n" .
              "- عدد الأفكار: {$ideasCount} \n" .
              "- عدد المعارف: {$peopleCount} \n" .
              "- آخر فكرة: " . ($lastIdea ? $lastIdea->content : trans('None')) . "\n" .
              "- مهام اليوم: [$tasksList] \n" .
              "- العادة: [$habitName] \n\n" .
              "اكتب تحليلاً استراتيجياً عميقاً يربط بين حالته المالية ومهامه. كن ملهماً وعملياً. اللغة: العربية."
            : "You are the Global Intelligence of Personal Memory OS. \n" .
              "User Context: \n" .
              "- Balance: \${$balance} \n" .
              "- Total Ideas: {$ideasCount} \n" .
              "- People: {$peopleCount} \n" .
              "- Last Idea: " . ($lastIdea ? $lastIdea->content : trans('None')) . "\n" .
              "- Tasks: [$tasksList] \n" .
              "- Habit: [$habitName] \n\n" .
              "Provide a strategic analysis in English.";

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful productivity assistant.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $plan = $data['choices'][0]['message']['content'] ?? trans('No response obtained.');
                DB::table('users')->where('id', $user->id)->update(['last_ai_analysis' => $plan]);
                return response()->json(['plan' => $plan]);
            }

            return response()->json(['plan' => '⚠️ ' . trans('Request failed: ') . $response->status()]);
        } catch (Exception $e) {
            return response()->json(['plan' => '⚠️ ' . trans('Error: ') . $e->getMessage()]);
        }
    }

    public function handleCommand(Request $request): JsonResponse
    {
        $command = $request->input('command');
        $user = $request->user();
        $locale = $request->input('locale', 'ar');

        $prompt = "You are the Command Center of Personal Memory OS. " .
            "Analyze this user command: \"$command\". " .
            "Extract if it's money, task, or idea. RESPOND ONLY WITH JSON. " .
            "Format: {\"type\": \"money|task|idea|unknown\", \"data\": {...}, " .
            "\"reply\": \"A reply in " . ($locale === 'ar' ? 'Arabic' : 'English') . " (max 10 words)\"}";

        try {
            $response = Http::timeout(20)
                ->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $json = $data['choices'][0]['message']['content'] ?? '{}';
                $res = json_decode($json, true) ?: ['type' => 'unknown', 'reply' => trans('Invalid format.')];

                if (($res['type'] ?? '') === 'money') {
                    DB::table('transactions')->insert([
                        'user_id' => $user->id,
                        'amount' => $res['data']['amount'] ?? 0,
                        'type' => $res['data']['type'] ?? 'expense',
                        'category' => $res['data']['category'] ?? trans('General'),
                        'description' => $res['data']['description'] ?? $command,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } elseif (($res['type'] ?? '') === 'task') {
                    DB::table('tasks')->insert([
                        'user_id' => $user->id,
                        'title' => $res['data']['title'] ?? $command,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } elseif (($res['type'] ?? '') === 'idea') {
                    DB::table('ideas')->insert([
                        'user_id' => $user->id,
                        'content' => $res['data']['content'] ?? $command,
                        'status' => 'draft',
                        'category' => trans('Smart'),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                return response()->json($res);
            }
            return response()->json(['reply' => trans('Connection failed.'), 'type' => 'unknown']);
        } catch (Exception $e) {
            return response()->json(['reply' => trans('Command not understood.'), 'type' => 'unknown']);
        }
    }

    public function storeTask(Request $request): RedirectResponse
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

    public function toggleTask(Request $request, $id): RedirectResponse
    {
        $task = DB::table('tasks')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();
        if ($task) {
            $newStatus = $task->status === 'completed' ? 'pending' : 'completed';
            DB::table('tasks')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => now()]);
        }
        return back();
    }

    public function storeHabit(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $existing = DB::table('habits')->where('user_id', $request->user()->id)->first();
        if ($existing) {
            DB::table('habits')->where('id', $existing->id)->update(['name' => $request->name, 'updated_at' => now()]);
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

    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q');
        if (!$q) return response()->json();

        $user_id = $request->user()->id;

        $ideas = DB::table('ideas')
            ->where('user_id', $user_id)
            ->where('content', 'like', "%$q%")
            ->select('id', 'content as title', DB::raw("'ideas' as type"))
            ->limit(5)
            ->get();

        $people = DB::table('people')
            ->where('user_id', $user_id)
            ->where('name', 'like', "%$q%")
            ->select('id', 'name as title', DB::raw("'people' as type"))
            ->limit(5)
            ->get();

        $tasks = DB::table('tasks')
            ->where('user_id', $user_id)
            ->where('title', 'like', "%$q%")
            ->select('id', 'title', DB::raw("'tasks' as type"))
            ->limit(5)
            ->get();

        return response()->json($ideas->concat((array)$people)->concat((array)$tasks));
    }

    public function storeGoal(Request $request): RedirectResponse
    {
        $request->validate(['title' => 'required|string|max:255']);
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

    public function setTelegramWebhook(Request $request): RedirectResponse
    {
        $user = $request->user();
        $token = $user->telegram_bot_token ?: config('services.telegram.token');

        if (!$token) {
            return back()->with('error', 'Telegram Bot Token missing.');
        }

        $appUrl = env('APP_URL');
        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            return back()->with('error', 'Webhook cannot be set on localhost. Use ngrok or deploy to live server.');
        }

        $webhookUrl = rtrim($appUrl, '/') . '/api/telegram/webhook';

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", ['url' => $webhookUrl]);
            if ($response->successful()) {
                return back()->with('success', 'Webhook set to: ' . $webhookUrl);
            }
            return back()->with('error', 'Telegram Error: ' . $response->body());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateTelegramToken(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'nullable|string|max:100']);
        DB::table('users')->where('id', $request->user()->id)->update([
            'telegram_bot_token' => $request->token,
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Telegram token updated!');
    }

    public function speak(Request $request): JsonResponse
    {
        $text = $request->input('text');
        $dialect = $request->input('dialect', 'ar-SA');
        $apiKey = env('ELEVENLABS_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'ElevenLabs API Key missing in .env'], 422);
        }

        $voiceId = 'pNInz6obpg8nEByWQX2l';

        try {
            $response = Http::withHeaders(['xi-api-key' => $apiKey])
                ->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                    'text' => $text,
                    'model_id' => 'eleven_multilingual_v2',
                    'voice_settings' => [
                        'stability' => 0.5,
                        'similarity_boost' => 0.75
                    ]
                ]);

            if ($response->successful()) {
                $fileName = 'voice_' . time() . '.mp3';
                Storage::disk('public')->put("audio/{$fileName}", $response->body());
                return response()->json(['url' => asset("storage/audio/{$fileName}")]);
            }
            return response()->json(['error' => 'ElevenLabs Error: ' . $response->body()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

