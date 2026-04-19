<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('process:recurring')]
#[Description('Process recurring transactions due today')]
class ProcessRecurringTransactions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dueTransactions = \App\Models\RecurringTransaction::where('is_active', true)
            ->where('next_date', '<=', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($dueTransactions as $rt) {
            while ($rt->next_date <= now()->toDateString()) {
                \Illuminate\Support\Facades\DB::table('transactions')->insert([
                    'user_id' => $rt->user_id,
                    'amount' => $rt->amount,
                    'type' => $rt->type,
                    'category' => $rt->category,
                    'description' => $rt->description . ' (Recurring)',
                    'created_at' => $rt->next_date . ' ' . now()->toTimeString(),
                    'updated_at' => now(),
                ]);

                $rt->next_date = match ($rt->frequency) {
                    'daily' => \Carbon\Carbon::parse($rt->next_date)->addDay()->toDateString(),
                    'weekly' => \Carbon\Carbon::parse($rt->next_date)->addWeek()->toDateString(),
                    'monthly' => \Carbon\Carbon::parse($rt->next_date)->addMonth()->toDateString(),
                    'yearly' => \Carbon\Carbon::parse($rt->next_date)->addYear()->toDateString(),
                };
                $count++;
            }
            $rt->save();
        }

        $this->info("Processed {$count} recurring transaction instances.");
    }
}
