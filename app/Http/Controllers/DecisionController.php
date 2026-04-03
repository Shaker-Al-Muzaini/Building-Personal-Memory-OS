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

        $prompt = "Analyze this decision/problem: \"{$request->problem}\"\n" .
                  "User Context: Balance: {$balance}, Pending Tasks: {$pendingTasksCount}.\n" .
                  "RESPONSE MUST BE ONLY A VALID JSON IN ARABIC. NOTHING ELSE.\n" .
                  "Example format:\n" .
                  "{\n" .
                  "  \"pros\": [\"نص عربي 1\", \"نص عربي 2\"],\n" .
                  "  \"cons\": [\"خطر عربي 1\"],\n" .
                  "  \"analysis\": \"تحليل يربط بين المال والوقت والقرار\",\n" .
                  "  \"suggestion\": \"القرار المقترح\",\n" .
                  "  \"score\": 80\n" .
                  "}";

        try {
            $response = Http::timeout(45)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.groq.key'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => "أنت محلل قرارات استراتيجي عالي الدقة. تكلم باللغة العربية بأسلوب فخم وذكي. اخرج النتائج بصيغة JSON فقط. استخدم dir=auto للمحتوى المختلط."],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 2048,
                    'temperature' => 0.5,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $ai_raw = $data['choices'][0]['message']['content'] ?? '';
                
                // --- تنظيف واستخراج الـ JSON بدقة عالية ---
                $firstBrace = strpos($ai_raw, '{');
                $lastBrace = strrpos($ai_raw, '}');
                
                if ($firstBrace !== false && $lastBrace !== false) {
                    $ai_json = substr($ai_raw, $firstBrace, $lastBrace - $firstBrace + 1);
                    // مراجعة إضافية للتأكد أنه JSON صحيح
                    $test = json_decode($ai_json);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $ai_advice = $ai_json;
                    } else {
                        throw new \Exception("Invalid JSON Structure");
                    }
                } else {
                    throw new \Exception("No JSON found in response");
                }
            } else {
                 throw new \Exception("Groq API Error: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            $ai_advice = json_encode([
                'pros' => ['تعذر تحليل البيانات حالياً'], 
                'cons' => ['هناك ضغط على الذاكرة العصبية'],
                'analysis' => 'فشل النظام في استخراج تحليل دقيق لهذا القرار. جرب إعادة صياغة السؤال بشكل أوضح.',
                'suggestion' => 'أعد المحاولة لاحقاً', 
                'score' => 0
            ], JSON_UNESCAPED_UNICODE);
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
