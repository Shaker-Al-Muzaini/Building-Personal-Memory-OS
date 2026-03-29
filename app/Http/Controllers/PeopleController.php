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
        return Inertia::render('People', [
            'people' => $people
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

        // إنشاء سياق تحليلي للأشخاص
        $peopleContext = [];
        foreach ($people as $person) {
            $lastContacted = $person->last_contact ? "وآخر تواصل كان بتاريخ {$person->last_contact}" : "لم تتواصل معه مؤخراً";
            $peopleContext[] = "- {$person->name} ({$person->relation})، الأهمية: {$person->importance}، {$lastContacted}. وملاحظاتي الخاصة المستمدة عنه: ({$person->gifts_notes})";
        }
        
        $peopleListText = implode("\n", $peopleContext);

        $prompt = "مرحباً، أنا أستخدم قسم 'ذاكرة الناس' بحياتي، وهؤلاء هم الأشخاص المسجلين لدي وتفاصيلهم وآخر تواصل بيننا:\n{$peopleListText}\n\nيُرجى عمل تحلیل ذكي لعلاقاتي وسلاسل تواصلي. اقرأ التواريخ واستنتج من طالت غيبتي عنهم وحثني على التواصل معهم. قدم لي بعض الاقتراحات الودية والسريعة لفتح حوارات مع الأشخاص ذوي الأهمية 'العالية'. كن لطيفاً ومنظماً في ردك ومكتوب بنقاط واضحة.";

        try {
            // نستخدم مكتبة الذكاء الاصطناعي المجانية ونتجاوز فحص شهادات حاسوبك (لدواعي التطوير المحلي)
            $response = Http::withoutVerifying()->post('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [
                    ['role' => 'system', 'content' => 'أنت العقل المساعد والذكي للمستخدم، اسمك Personal Memory OS. تخصصك الآن: خبير علاقات إنسانية، تعطي توصيات دافئة لتحسين علاقات المستخدم بأصدقائه وعائلته.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

            return response()->json([
                'plan' => $response->json('choices.0.message.content') ?? 'عذراً، لم أتمكن من إعداد تحليل العلاقات حالياً.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'plan' => 'حدث خطأ في الاتصال بالذكاء الاصطناعي: ' . $e->getMessage()
            ], 500);
        }
    }
}
