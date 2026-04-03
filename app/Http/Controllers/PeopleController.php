<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $people = DB::table('people')->where('user_id', $request->user()->id)->get();
        
        $peopleWithBond = $people->map(function($person) {
            $daysSinceContact = now()->diffInDays($person->last_contact);
            
            // حساب معدل التحلل بناء على الأهمية
            $decayRate = 2; // Default
            if ($person->importance === 'عالية') $decayRate = 5;
            if ($person->importance === 'منخفضة') $decayRate = 1;
            
            $bondStrength = max(0, 100 - ($daysSinceContact * $decayRate));
            $person->bond_strength = $bondStrength;
            return $person;
        });

        return Inertia::render('People', [
            'people' => $peopleWithBond
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:255',
            'importance' => 'required|in:عالية,متوسطة,منخفضة',
            'gifts_notes' => 'nullable|string',
        ]);

        DB::table('people')->insert([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'relation' => $request->relation ?? 'صديق',
            'importance' => $request->importance,
            'last_contact' => now()->toDateString(),
            'gifts_notes' => $request->gifts_notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function touch(Request $request, $id)
    {
        $person = DB::table('people')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if ($person) {
            DB::table('people')->where('id', $id)->update([
                'last_contact' => now()->toDateString(),
                'updated_at' => now()
            ]);
        }
        return back();
    }

    public function deleteAction(Request $request, $id)
    {
        DB::table('people')->where('id', $id)->where('user_id', $request->user()->id)->delete();
        return back();
    }

    public function generatePeoplePlan(Request $request)
    {
        $people = DB::table('people')->where('user_id', $request->user()->id)->get();

        if ($people->isEmpty()) {
            return response()->json([
                'plan' => 'أنت لم تُضف أحداً بعد لقائمتك. ابدأ بإضافة عائلتك وأصدقائك أو زملاء العمل ليتمكن مُمساعدك الذكي من تحليل علاقاتك وتقديم النصائح!'
            ]);
        }

        $peopleContext = [];
        foreach ($people as $person) {
            $lastContacted = $person->last_contact ? "وآخر تواصل كان بتاريخ {$person->last_contact}" : "لم تتواصل معه مؤخراً";
            $peopleContext[] = "- {$person->name} ({$person->relation})، الأهمية: {$person->importance}، {$lastContacted}. وملاحظاتي الخاصة المستمدة عنه: ({$person->gifts_notes})";
        }
        
        $peopleListText = implode("\n", $peopleContext);

        $prompt = "مرحباً، أنا أستخدم قسم 'ذاكرة الناس' بحياتي، وهؤلاء هم الأشخاص المسجلين لدي وتفاصيلهم وآخر تواصل بيننا:\n{$peopleListText}\n\nيُرجى عمل تحلیل ذكي لعلاقاتي وسلاسل تواصلي. اقرأ التواريخ واستنتج من طالت غيبتي عنهم وحثني على التواصل معهم. قدم لي بعض الاقتراحات الودية والسريعة لفتح حوارات مع الأشخاص ذوي الأهمية 'العالية'. كن لطيفاً ومنظماً في ردك ومكتوب بنقاط واضحة.";

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.groq.key'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'أنت العقل المساعد والذكي للمستخدم، اسمك Personal Memory OS. تخصصك الآن: خبير علاقات إنسانية، تعطي توصيات دافئة لتحسين علاقات المستخدم بأصدقائه وعائلته.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 1024,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $plan = $data['choices'][0]['message']['content'] ?? 'عذراً، لم أتمكن من إعداد تحليل العلاقات حالياً.';
                return response()->json(['plan' => $plan]);
            }

            return response()->json([
                'plan' => 'عذراً، لم أتمكن من إعداد تحليل العلاقات حالياً. خطأ: ' . $response->status()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'plan' => 'حدث خطأ في الاتصال بالذكاء الاصطناعي: ' . $e->getMessage()
            ], 500);
        }
    }

    public function quickAdvice(Request $request, $id)
    {
        $person = DB::table('people')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$person) return response()->json(['advice' => 'شخص غير معروف.']);

        $prompt = "Give me 1 creative and short ice-breaker or social advice to contact: \"{$person->name}\" who is my \"{$person->relation}\". 
        Notes about them: \"{$person->gifts_notes}\".
        Respond in Arabic, warm and funny. Max 1 sentence.";

        try {
            $response = Http::timeout(15)->withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.groq.key')])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
            return response()->json(['advice' => $response->json()['choices'][0]['message']['content']]);
        } catch (\Exception $e) {
            return response()->json(['advice' => 'جرب أن تسأل عن أحوالهم ببساطة!']);
        }
    }
}
