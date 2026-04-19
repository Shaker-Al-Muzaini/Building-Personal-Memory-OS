<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'period_type',
        'start_date',
        'end_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getSummaryForUser($userId)
    {
        $activeBudget = self::where('user_id', $userId)
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        if (!$activeBudget) return null;

        $startDate = \Carbon\Carbon::parse($activeBudget->start_date);
        $endDate = \Carbon\Carbon::parse($activeBudget->end_date);
        $totalDays = max(1, $startDate->diffInDays($endDate) + 1);
        $daysLeft = max(1, now()->startOfDay()->diffInDays($endDate) + 1);

        $spent = \Illuminate\Support\Facades\DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('created_at', [$activeBudget->start_date . ' 00:00:00', $activeBudget->end_date . ' 23:59:59'])
            ->sum('amount');

        // Calculate Future Committed (recurring expenses that will happen in the remaining days)
        $recurring = \Illuminate\Support\Facades\DB::table('recurring_transactions')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', 'expense')
            ->get();

        $dailyRec = $recurring->where('frequency', 'daily')->sum('amount');
        $weeklyRec = $recurring->where('frequency', 'weekly')->sum('amount');
        
        $futureDays = max(0, $daysLeft - 1); // -1 because today's might already be in $spent
        $futureWeeks = max(0, floor($futureDays / 7));

        $futureCommitted = ($dailyRec * $futureDays) + ($weeklyRec * $futureWeeks);
        $totalConsumed = $spent + $futureCommitted;
        
        $remaining = $activeBudget->amount - $totalConsumed;

        // Fixed Daily Allowance is based on original budget minus ALL recurring commitments
        $totalPeriodCommitted = ($dailyRec * $totalDays) + ($weeklyRec * max(1, floor($totalDays/7)));
        $freePool = $activeBudget->amount - $totalPeriodCommitted;
        $fixedDailyAllowance = $freePool > 0 ? ($freePool / $totalDays) : 0;

        return [
            'total' => (float)$activeBudget->amount,
            'spent' => (float)$spent,
            'future_committed' => (float)$futureCommitted,
            'total_consumed' => (float)$totalConsumed,
            'remaining' => (float)$remaining,
            'days_left' => $daysLeft,
            'daily_allowance' => (float)$fixedDailyAllowance,
            'period' => $activeBudget->period_type
        ];
    }
}
