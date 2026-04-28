<?php

namespace App\Domain\Repositories\Contracts;

use App\Domain\Entities\User;

interface UserRepositoryInterface
{
    /**
     * احصل على مستخدم بواسطة ID
     */
    public function getById(int $id): ?User;

    /**
     * احصل على مستخدم بواسطة البريد الإلكتروني
     */
    public function getByEmail(string $email): ?User;

    /**
     * احفظ مستخدم جديد
     */
    public function create(User $user): User;

    /**
     * حدث مستخدم موجود
     */
    public function update(User $user): User;

    /**
     * احذف مستخدم
     */
    public function delete(int $id): bool;
}

