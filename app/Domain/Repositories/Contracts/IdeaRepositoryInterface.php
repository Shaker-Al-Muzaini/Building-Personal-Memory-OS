<?php

namespace App\Domain\Repositories\Contracts;

use App\Domain\Entities\Idea;

interface IdeaRepositoryInterface
{
    /**
     * احصل على جميع الأفكار للمستخدم
     */
    public function getAllByUserId(int $userId, array $filters = []): array;

    /**
     * احصل على فكرة بواسطة ID
     */
    public function getById(int $id): ?Idea;

    /**
     * احفظ فكرة جديدة
     */
    public function create(Idea $idea): Idea;

    /**
     * حدث فكرة موجودة
     */
    public function update(Idea $idea): Idea;

    /**
     * احذف فكرة
     */
    public function delete(int $id): bool;

    /**
     * تحديث حالة الفكرة
     */
    public function updateStatus(int $id, string $status): bool;
}

