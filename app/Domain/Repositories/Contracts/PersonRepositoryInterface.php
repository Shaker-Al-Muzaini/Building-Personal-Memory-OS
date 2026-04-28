<?php

namespace App\Domain\Repositories\Contracts;

use App\Domain\Entities\Person;

interface PersonRepositoryInterface
{
    /**
     * احصل على جميع الأشخاص للمستخدم
     */
    public function getAllByUserId(int $userId, array $filters = []): array;

    /**
     * احصل على شخص بواسطة ID
     */
    public function getById(int $id): ?Person;

    /**
     * احفظ شخص جديد
     */
    public function create(Person $person): Person;

    /**
     * حدث شخص موجود
     */
    public function update(Person $person): Person;

    /**
     * احذف شخص
     */
    public function delete(int $id): bool;

    /**
     * تحديث آخر اتصال بشخص
     */
    public function updateLastContact(int $id): bool;
}

