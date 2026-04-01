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
        
        // --- Added for Dashboard Overview ---
        $transactions = DB::table('money_transactions')->where('user_id', $user->id)->get();
        $balance = $transactions->sum(fn($t) => $t->type === 'income' ? $t->amount : -$t->amount);
        
        $lastIdea = DB::table('ideas_memory')->where('user_id', $user->id)->latest()->first();
        
        $personToContact = DB::table('people_memory')
            ->where('user_id', $user->id)
            ->inRandomOrder()
            ->first();

        $goal = ['title' => 'تعلم مهارة جديدة لمدة 30 دقيقة', 'status' => 'pending'];

        return Inertia::render('Dashboard', [
            'tasks' => $tasks,
            'habit' => $habit,
            'goal' => $goal,
            'overview' => [
                'balance' => $balance,
                'last_idea' => $lastIdea ? $lastIdea->content : null,
                'person_to_contact' => $personToContact ? $personToContact->name : null,
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
            $response = Http::withoutVerifying()->post('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [
                    ['role' => 'system', 'content' => "You are Memory OS, a smart AI assistant. Speak in a concise, inspiring, and intelligent way. Use $languageName language only. Format your response in simple bullet points."],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

            return response()->json([
                'plan' => $response->json('choices.0.message.content') ?? 'عذراً، لم أتمكن من إعداد الخطة اليوم (تأكد من إعدادات مفتاح API).'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'plan' => 'حدث خطأ في الاتصال بالذكاء الاصطناعي: ' . $e->getMessage()
            ], 500);
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
