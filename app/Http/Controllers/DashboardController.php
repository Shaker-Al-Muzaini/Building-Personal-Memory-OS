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

        $goal = ['title' => 'تحسين كفاءة الذاكرة الشخصية', 'status' => 'pending'];

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
            ]
        ]);
    }

    public function generatePlan(Request $request)
    {
        $user = $request->user();
        
        $tasks = DB::table('tasks')->where('user_id', $user->id)->get()->pluck('title')->toArray();
        $habit = DB::table('habits')->where('user_id', $user->id)->first();
        
        $tasksList = count($tasks) > 0 ? implode(', ', $tasks) : 'لا يوجد مهام حتى الآن';
        $habitName = $habit ? $habit->name : 'لا يوجد عادات بعد';
        
        $locale = $request->input('locale', 'ar');
        $languageName = $locale === 'ar' ? 'العربية' : 'English';
        
        $prompt = $locale === 'ar' 
            ? "مرحباً! أنا مستخدم بنظام Personal Memory OS. مهامي اليوم هي: [$tasksList]. وعادتي التي أريد المواظبة عليها: [$habitName]. وهدفي: [تعلم مهارة جديدة]. حلل مهامي واقترح جدول اليوم ونصائح نفسية وصحية سريعة لتشجيعي."
            : "Hello! I am a user of Personal Memory OS. My tasks today are: [$tasksList]. My habit: [$habitName]. My goal: [Learning a new skill]. Analyze my tasks and suggest a daily schedule with psychological and health tips.";

        try {
            
            $response = Http::timeout(20)->withoutVerifying()->get("https://text.pollinations.ai/" . rawurlencode($prompt) . "?model=openai&cache=false");

            if ($response->successful()) {
                $plan = $response->body();
                return response()->json(['plan' => $plan]);
            }

            $fallbackPlan = $locale === 'ar' 
                ? "💡 نصيحة سريعة: ركز على المهام الأكثر إلحاحاً اليوم، وحاول تقليل المصاريف الجانبية لزيادة توازنك المالي."
                : "💡 Strategic Tip: Focus on urgent tasks and reduce side expenses to bolster your financial stability.";
            
            return response()->json(['plan' => $fallbackPlan]);
            
        } catch (\Exception $e) {
            return response()->json([
                'plan' => $locale === 'ar' 
                    ? "⚠️ تنبيه: تعذر الوصول للسيرفر. استمر في إنتاجك اليومي." 
                    : "⚠️ Connection Issue. Keep up your productivity."
            ]);
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
}
