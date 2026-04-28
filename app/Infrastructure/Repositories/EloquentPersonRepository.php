<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Person;
use App\Domain\Repositories\Contracts\PersonRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentPersonRepository implements PersonRepositoryInterface
{
    public function getAllByUserId(int $userId, array $filters = []): array
    {
        $query = DB::table('people')->where('user_id', $userId);

        if (!empty($filters['relationship_type'])) {
            $query->where('relationship_type', $filters['relationship_type']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $results = $query->orderBy('name', 'asc')->get();

        return array_map(fn($data) => Person::fromArray((array)$data), $results->toArray());
    }

    public function getById(int $id): ?Person
    {
        $data = DB::table('people')->find($id);
        return $data ? Person::fromArray((array)$data) : null;
    }

    public function create(Person $person): Person
    {
        $id = DB::table('people')->insertGetId([
            'user_id' => $person->userId,
            'name' => $person->name,
            'relationship_type' => $person->relationshipType,
            'notes' => $person->notes,
            'last_contact' => $person->lastContact?->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $person->id = $id;
        return $person;
    }

    public function update(Person $person): Person
    {
        DB::table('people')->where('id', $person->id)->update([
            'name' => $person->name,
            'relationship_type' => $person->relationshipType,
            'notes' => $person->notes,
            'last_contact' => $person->lastContact?->format('Y-m-d H:i:s'),
            'updated_at' => now(),
        ]);

        return $person;
    }

    public function delete(int $id): bool
    {
        return DB::table('people')->where('id', $id)->delete() > 0;
    }

    public function updateLastContact(int $id): bool
    {
        return DB::table('people')->where('id', $id)->update([
            'last_contact' => now(),
            'updated_at' => now(),
        ]) > 0;
    }
}

