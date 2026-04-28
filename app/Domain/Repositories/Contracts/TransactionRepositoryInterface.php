<?php

namespace App\Domain\Repositories\Contracts;

use App\Domain\Entities\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * احصل على جميع التحويلات للمستخدم
     */
    public function getAllByUserId(int $userId, array $filters = []): array;

    /**
     * احصل على تحويل بواسطة ID
     */
    public function getById(int $id): ?Transaction;

    /**
     * احفظ تحويل جديد
     */
    public function create(Transaction $transaction): Transaction;

    /**
     * حدث تحويل موجود
     */
    public function update(Transaction $transaction): Transaction;

    /**
     * احذف تحويل
     */
    public function delete(int $id): bool;

    /**
     * احصل على مبلغ المصروفات في فترة زمنية
     */
    public function getExpensesInDateRange(int $userId, \DateTime $from, \DateTime $to): float;

    /**
     * احصل على مبلغ الإيرادات في فترة زمنية
     */
    public function getIncomeInDateRange(int $userId, \DateTime $from, \DateTime $to): float;
}

