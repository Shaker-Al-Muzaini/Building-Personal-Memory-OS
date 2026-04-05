<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FocusController extends Controller
{
    public function index(Request $request)
    {
        $tasks = DB::table('tasks')->where('user_id', $request->user()->id)->get();
        return Inertia::render('Focus', [
            'tasks' => $tasks
        ]);
    }

    public function generateFocusPlan(Request $request)
    {
        $user = $request->user();
        $tasks = DB::table('tasks')->where('user_id', $user->id)->where('status', 'pending')->get()->pluck('title')->toArray();
        
        if (empty($tasks)) {
            return response()->json(['plan' => 'ليس لديك مهام معلقة لترتيبها بناءً على التركيز، استمتع بوقتك!']);
        }

        $tasksList = implode(', ', $tasks);
        $prompt = "لدي هذه المهام اليوم: [$tasksList]. 
        أنا بصدد بدء جلسة 'التركيز العميق' (Pomodoro). 
        قم بترتيب هذه المهام من الأهم للأقل أهمية وتوزيعها على جلسات بومودورو (25 دقيقة).
        أعطني خطة عملية ومشجعة باللغة العربية بأسلوب احترافي مباشر.";

        try {
            $response = Http::timeout(20)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
            
            if ($response->successful()) {
                $plan = $response->json()['choices'][0]['message']['content'] ?? 'لم أتمكن من تخطيط التركيز.';
                return response()->json(['plan' => $plan]);
            }
            return response()->json(['plan' => 'مدير التركيز الذكي بأخذ غفوة، ابدأ بالمهمة الأولى فوراً!']);
        } catch (\Exception $e) {
            return response()->json(['plan' => 'انقطع الاتصال، ابدأ بالمهمة الأهم لك الآن.']);
        }
    }
}
