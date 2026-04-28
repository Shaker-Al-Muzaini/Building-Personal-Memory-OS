<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\User;
use App\Domain\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function getById(int $id): ?User
    {
        $data = DB::table('users')->find($id);
        return $data ? User::fromArray((array)$data) : null;
    }

    public function getByEmail(string $email): ?User
    {
        $data = DB::table('users')->where('email', $email)->first();
        return $data ? User::fromArray((array)$data) : null;
    }

    public function create(User $user): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->id = $id;
        return $user;
    }

    public function update(User $user): User
    {
        DB::table('users')->where('id', $user->id)->update([
            'name' => $user->name,
            'email' => $user->email,
            'updated_at' => now(),
        ]);

        return $user;
    }

    public function delete(int $id): bool
    {
        return DB::table('users')->where('id', $id)->delete() > 0;
    }
}

