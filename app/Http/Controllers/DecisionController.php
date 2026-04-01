<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class DecisionController extends Controller
{
    public function index(Request $request)
    {
        $decisions = DB::table('decisions')->where('user_id', $request->user()->id)->orderBy('id', 'desc')->get();
        return Inertia::render('Decisions', [
            'decisions' => $decisions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['problem' => 'required|string']);
        $user_id = $request->user()->id;

        
        $income = DB::table('transactions')->where('user_id', $user_id)->where('type', 'income')->sum('amount');
        $expense = DB::table('transactions')->where('user_id', $user_id)->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        $pendingTasksCount = DB::table('tasks')->where('user_id', $user_id)->where('status', 'pending')->count();
        $recentIdeas = DB::table('ideas')->where('user_id', $user_id)->latest()->limit(2)->pluck('content')->toArray();

        $context = "السياق الحالي للمستخدم: \n- الرصيد المالي: {$balance} $ \n- عدد المهام المعلقة: {$pendingTasksCount} مهام \n- أفكار حديثة: " . implode(', ', $recentIdeas);

        $prompt = "لدي هذه المشكلة/القرار: {$request->problem}\n" .
                  "{$context}\n" .
                  "حللها برمجياً وأعطني الرد بتنسيق JSON حصرياً كالتالي:\n" .
                  "{\n" .
                  "  \"pros\": [\"إيجابية 1\", \"إيجابية 2\"],\n" .
                  "  \"cons\": [\"سلبية 1\", \"سلبية 2\"],\n" .
                  "  \"analysis\": \"تحليل موجز يربط القرار بوضعي المالي ومهامي الحالية\",\n" .
                  "  \"suggestion\": \"القرار المقترح\",\n" .
                  "  \"score\": 85\n" . // درجة منطقية القرار من 100 بناء على المعطيات
                  "}\n" .
                  "لا تكتب أي نص قبل أو بعد الـ JSON.";

        try {
            $response = Http::withoutVerifying()->post('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [
                    ['role' => 'system', 'content' => 'أنت محلل قرارات استراتيجي عالي الدقة، تأخذ بعين الاعتبار الموارد المالية والوقتية للمستخدم.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);
            $ai_json = $response->json('choices.0.message.content');
            
            // تنظيف الـ JSON
            $ai_json = trim($ai_json);
            if (strpos($ai_json, '```json') !== false) {
                $ai_json = str_replace(['```json', '```'], '', $ai_json);
            }
            
            $ai_advice = trim($ai_json);
        } catch (\Exception $e) {
            $ai_advice = json_encode([
                'pros' => ['تعذر التحليل'], 'cons' => ['تحقق من الاتصال'],
                'analysis' => 'فشل الربط العصبي مع البيانات الحالية.',
                'suggestion' => 'حاول لاحقاً', 'score' => 0
            ]);
        }

        DB::table('decisions')->insert([
            'user_id' => $user_id,
            'problem' => $request->problem,
            'ai_advice' => $ai_advice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function finalize(Request $request, $id)
    {
        $request->validate(['final_decision' => 'required|string']);
        DB::table('decisions')->where('id', $id)->where('user_id', $request->user()->id)->update([
            'final_decision' => $request->final_decision,
            'updated_at' => now()
        ]);
        return back();
    }

    public function destroy(Request $request, $id)
    {
        DB::table('decisions')->where('id', $id)->where('user_id', $request->user()->id)->delete();
        return back();
    }
}
