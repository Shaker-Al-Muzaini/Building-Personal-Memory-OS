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

        $prompt = "لدي هذه الفكرة: {$request->content}\nحلل هذه الفكرة وأعطني خطوتين لتطويرها، وفي أي تصنيف تندرج بوضوح وإيجاز.";
        $ai_analysis = null;

        try {
            $response = Http::withoutVerifying()->post('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [
                    ['role' => 'system', 'content' => 'أنت العقل الثاني والمحلل الفكري للمستخدم، مبرمج لتوسيع أفكاره وتحليلها وإضافة قيمة إبداعية لها.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);
            $ai_analysis = $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            $ai_analysis = "لم يتمكن الذكاء الاصطناعي من تحليل الفكرة حالياً. يمكنك تحديثها لاحقاً.";
        }

        DB::table('ideas')->insert([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'ai_analysis' => $ai_analysis,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function destroy(Request $request, $id)
    {
        DB::table('ideas')->where('id', $id)->where('user_id', $request->user()->id)->delete();
        return back();
    }
}
