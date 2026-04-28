<?php

namespace App\Application\UseCases\Money;

use App\Domain\Repositories\Contracts\TransactionRepositoryInterface;

class GetBudgetSummaryUseCase
{
    public function __construct(private TransactionRepositoryInterface $transactionRepository) {}

    public function execute(int $userId, float $budgetAmount, \DateTime $startDate, \DateTime $endDate): array
    {
        $spent = $this->transactionRepository->getExpensesInDateRange($userId, $startDate, $endDate);
        $income = $this->transactionRepository->getIncomeInDateRange($userId, $startDate, $endDate);

        $totalDays = max(1, $startDate->diffInDays($endDate) + 1);
        $daysLeft = max(1, now()->startOfDay()->diffInDays($endDate) + 1);

        $remaining = $budgetAmount - $spent;
        $dailyAllowance = $remaining > 0 ? ($remaining / $daysLeft) : 0;

        return [
            'total' => (float)$budgetAmount,
            'spent' => (float)$spent,
            'income' => (float)$income,
            'remaining' => (float)$remaining,
            'days_left' => $daysLeft,
            'daily_allowance' => (float)$dailyAllowance,
        ];
    }
}

