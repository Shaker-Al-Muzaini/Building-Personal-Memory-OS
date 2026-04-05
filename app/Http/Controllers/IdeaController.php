<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class IdeaController extends Controller
{
    public function index(Request $request)
    {
        $ideas = DB::table('ideas')->where('user_id', $request->user()->id)->orderBy('id', 'desc')->get();
        return Inertia::render('Ideas', [
            'ideas' => $ideas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['content' => 'required|string']);

        $prompt = "لدي هذه الفكرة: " . $request->input('content') . "\nحلل هذه الفكرة وأعطني خطوتين لتطويرها. استخرج تصنيفاً واحداً مختصراً جداً (كلمة واحدة) لهذه الفكرة. اجعل الرد يبدأ بالتصنيف ثم سطر جديد ثم التحليل.";
        $ai_analysis = null;
        $category = 'عام';

        try {
            // سحب سياق من الناس والقرارات المسجلة لربطها بالفكرة
            $people = DB::table('people')->where('user_id', $request->user()->id)->limit(5)->pluck('name')->toArray();
            $decisions = DB::table('decisions')->where('user_id', $request->user()->id)->limit(5)->pluck('problem')->toArray();
            
            $context = "Context - People: " . implode(', ', $people) . ". Decisions: " . implode(', ', $decisions);
            
            $prompt = "لدي هذه الفكرة: " . $request->input('content') . "\n
            $context \n
            حلل الفكرة وأعطني خطوتين لتطويرها.
            اقترح تصنيفاً واحداً (كلمة واحدة).
            هل ترتبط هذه الفكرة بأي من الأشخاص أو القرارات المذكورة أعلاه؟
            RESPOND IN ARABIC. 
            Format: [Category Name] \n Analysis text... \n Neural Suggestion: ...";

            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.groq.key'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'أنت العقل الثاني والمحلل المتطور للمستخدم.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 1024,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $full_response = $data['choices'][0]['message']['content'] ?? '';
                
                $lines = explode("\n", $full_response);
                $category = trim(str_replace(['[', ']', 'التصنيف:', '#', '*'], '', $lines[0] ?? 'فكرة'));
                if (strlen($category) > 20) $category = 'فكرة';
                
                $ai_analysis = implode("\n", array_slice($lines, 1));
            } else {
                $ai_analysis = "النظام العصبي منشغل حالياً. يرجى تحليل هذه الفكرة في وقت لاحق.";
            }
        } catch (\Exception $e) {
            $ai_analysis = "انقطع الاتصال بالذكاء الاصطناعي، يرجى المحاولة لاحقاً.";
        }


        DB::table('ideas')->insert([
            'user_id' => $request->user()->id,
            'content' => $request->input('content'),
            'ai_analysis' => $ai_analysis,
            'status' => 'draft',
            'category' => $category,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string|in:draft,developing,ready']);
        
        DB::table('ideas')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update([
                'status' => $request->input('status'),
                'updated_at' => now()
            ]);

        return back();
    }

    public function destroy(Request $request, $id)
    {
        DB::table('ideas')->where('id', $id)->where('user_id', $request->user()->id)->delete();
        return back();
    }
}
