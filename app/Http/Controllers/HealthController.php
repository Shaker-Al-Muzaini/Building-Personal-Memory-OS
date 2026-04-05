<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    public function index(Request $request)
    {
        $logs = DB::table('daily_logs')->where('user_id', $request->user()->id)->orderBy('log_date', 'desc')->get();
        return Inertia::render('Health', [
            'logs' => $logs
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'log_date' => 'required|date',
            'sleep_hours' => 'required|numeric|min:0|max:24',
            'mood_score' => 'required|integer|min:1|max:10',
            'notes' => 'nullable|string'
        ]);

        $user_id = $request->user()->id;

        DB::table('daily_logs')->updateOrInsert(
            ['user_id' => $user_id, 'log_date' => $request->log_date],
            [
                'sleep_hours' => $request->sleep_hours,
                'mood_score' => $request->mood_score,
                'notes' => $request->notes,
                'updated_at' => now(),
                'created_at' => now() // Might overwrite on update but it's simpler
            ]
        );

        return back();
    }

    public function analyze(Request $request)
    {
        $user_id = $request->user()->id;
        $logs = DB::table('daily_logs')->where('user_id', $user_id)->orderBy('log_date', 'desc')->limit(14)->get();
        
        if ($logs->isEmpty()) {
            return response()->json(['analysis' => 'لعمل تحليل صحي وعصبي دقيق، احرص على إدخال سجل يومين على الأقل.']);
        }

        $income = DB::table('transactions')->where('user_id', $user_id)->where('type', 'income')->sum('amount');
        $expense = DB::table('transactions')->where('user_id', $user_id)->where('type', 'expense')->sum('amount');
        
        $decisions = DB::table('decisions')->where('user_id', $user_id)->count();
        
        $logsText = $logs->map(function($l) { 
            return "يوم {$l->log_date}: نوم {$l->sleep_hours} ساعة، المزاج: {$l->mood_score}/10. ({$l->notes})"; 
        })->implode("\n");

        $prompt = "لدي هذه السجلات الصحية خلال الأيام الماضية:\n{$logsText}\n
        إجمالي إنفاقي: {$expense} دولار، وعدد قراراتي المسجلة هو {$decisions}.\n
        قم بدراسة النمط (Pattern) بين نومي ومزاجي. هل هناك تناقض؟ هل تتوقع أن يؤثر هذا على إنفاقي (أموالي) أو طاقتي في القرارات؟
        استخدم أسلوب طبيب أعصاب استراتيجي وصريح جداً ومباشر بخطوات قصيرة باللغة العربية.";

        try {
            $response = Http::timeout(25)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
            
            if ($response->successful()) {
                $analysis = $response->json()['choices'][0]['message']['content'] ?? 'لم نتمكن من صياغة التقرير الصحي.';
                return response()->json(['analysis' => $analysis]);
            }
            return response()->json(['analysis' => 'النظام الطبي منشغل، المرجو الراحة قليلاً والمحاولة لاحقاً.']);
        } catch (\Exception $e) {
            return response()->json(['analysis' => 'انقطع الاتصال بعيادة الذكاء الاصطناعي.']);
        }
    }
}
