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

        $prompt = "أنا محتار في هذا القرار: {$request->problem}\nأريدك أن تفكر معي بموضوعية، أعطني أبرز الإيجابيات والسلبيات، وخطة بديلة إن لزم الأمر، ثم قرار نهائي مقترح بشكل مختصر ولطيف.";

        try {
            $response = Http::withoutVerifying()->post('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [
                    ['role' => 'system', 'content' => 'أنت مستشار استراتيجي وحكيم وشريك تفكير للمستخدم، مبرمج لتسهيل اتخاذ القرارات وحسم الحيرة بطريقة علمية وبسيطة جداً.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);
            $ai_advice = $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            $ai_advice = "الخادم مشغول حالياً عن إعطاء النصيحة. فكّر مرة أخرى واستشر قلباً يطمئن لك.";
        }

        DB::table('decisions')->insert([
            'user_id' => $request->user()->id,
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
