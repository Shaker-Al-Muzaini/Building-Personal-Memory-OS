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

        $prompt = "لدي هذه الفكرة: {$request->content}\nحلل هذه الفكرة وأعطني خطوتين لتطويرها. استخرج تصنيفاً واحداً مختصراً جداً (كلمة واحدة) لهذه الفكرة. اجعل الرد يبدأ بالتصنيف ثم سطر جديد ثم التحليل.";
        $ai_analysis = null;
        $category = 'عام';

        try {
            $response = Http::withoutVerifying()->post('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [
                    ['role' => 'system', 'content' => 'أنت العقل الثاني والمحلل الفكري للمستخدم. ردك يجب أن يبدأ بالتصنيف في أول سطر ثم التحليل.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);
            $full_response = $response->json('choices.0.message.content');
            
            // محاولة استخراج التصنيف من أول سطر
            $lines = explode("\n", $full_response);
            $category = trim(str_replace(['التصنيف:', 'Category:', '#'], '', $lines[0]));
            if (strlen($category) > 20) $category = 'فكرة';
            
            $ai_analysis = implode("\n", array_slice($lines, 1));
        } catch (\Exception $e) {
            $ai_analysis = "لم يتمكن الذكاء الاصطناعي من تحليل الفكرة حالياً.";
        }

        DB::table('ideas')->insert([
            'user_id' => $request->user()->id,
            'content' => $request->content,
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
                'status' => $request->status,
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
