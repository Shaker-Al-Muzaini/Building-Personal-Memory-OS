<?php

namespace App\Domain\Repositories\Contracts;

use App\Domain\Entities\Decision;

interface DecisionRepositoryInterface
{
    /**
     * احصل على جميع القرارات للمستخدم
     */
    public function getAllByUserId(int $userId, array $filters = []): array;

    /**
     * احصل على قرار بواسطة ID
     */
    public function getById(int $id): ?Decision;

    /**
     * احفظ قرار جديد
     */
    public function create(Decision $decision): Decision;

    /**
     * حدث قرار موجود
     */
    public function update(Decision $decision): Decision;

    /**
     * احذف قرار
     */
    public function delete(int $id): bool;

    /**
     * تحديث حالة القرار
     */
    public function updateStatus(int $id, string $status): bool;
}

