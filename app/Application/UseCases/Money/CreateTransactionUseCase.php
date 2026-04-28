<?php

namespace App\Application\UseCases\Money;

use App\Domain\Entities\Transaction;
use App\Domain\Repositories\Contracts\TransactionRepositoryInterface;

class CreateTransactionUseCase
{
    public function __construct(private TransactionRepositoryInterface $transactionRepository) {}

    public function execute(
        int $userId,
        float $amount,
        string $type, // 'income' or 'expense'
        ?string $category = null,
        ?string $description = null
    ): Transaction {
        $transaction = new Transaction(
            id: 0,
            userId: $userId,
            amount: $amount,
            type: $type,
            category: $category,
            description: $description
        );

        return $this->transactionRepository->create($transaction);
    }
}

