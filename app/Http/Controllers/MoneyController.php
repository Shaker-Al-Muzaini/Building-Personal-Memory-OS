<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class MoneyController extends Controller
{
    public function index(Request $request)
    {
        $transactions = DB::table('transactions')->where('user_id', $request->user()->id)->orderBy('id', 'desc')->get();
        
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        return Inertia::render('Money', [
            'transactions' => $transactions,
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'balance' => $balance
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:income,expense',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::table('transactions')->insert([
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'type' => $request->type,
            'category' => $request->category ?? 'عام',
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function destroy(Request $request, $id)
    {
        DB::table('transactions')->where('id', $id)->where('user_id', $request->user()->id)->delete();
        return back();
    }

    public function analyze(Request $request)
    {
        $transactions = DB::table('transactions')->where('user_id', $request->user()->id)->get();
        if ($transactions->isEmpty()) {
            return response()->json(['plan' => 'أنت لم تقم بتسجيل أي مصروفات أو دخل بعد. الذكاء الاصطناعي بانتظار بياناتك!']);
        }

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // تجهيز ملخص للمصاريف للذكاء
        $expenses = $transactions->where('type', 'expense')->groupBy('category')->map(function ($row) {
            return collect($row)->sum('amount');
        });
        
        $expensesText = "";
        foreach ($expenses as $cat => $sum) {
            $expensesText .= "- $cat: $sum\n";
        }

        $prompt = "دخلي هو $totalIncome ومصاريفي $totalExpense والرصيد $balance. وكانت مصروفي ضمن هذه الفئات:\n$expensesText\n\nأرجوك حلل حالتي المالية وعادات الإنفاق لدي وقدم لي خطة بسيطة لتوفير المال والتحذير من الخطر، وماذا تتوقع إن استمريت هكذا؟ استخدم نقط ورسائل تشجيعية.";

        try {
            $response = Http::withoutVerifying()->post('https://text.pollinations.ai/openai', [
                'model' => 'openai',
                'messages' => [
                    ['role' => 'system', 'content' => 'أنت المستشار المالي الشخصي للمستخدم، خبير بالاقتصاد وتوجيه الأموال والميزانيات وصريح جداً ومختصر.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);
            return response()->json(['plan' => $response->json('choices.0.message.content') ?? 'لن نتمكن من تحليل المالية الآن.']);
        } catch (\Exception $e) {
            return response()->json(['plan' => 'حدث خطأ في الاتصال بالمستشار المالي الذكي.']);
        }
    }
}
