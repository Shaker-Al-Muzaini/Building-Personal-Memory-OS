<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Decision;
use App\Domain\Repositories\Contracts\DecisionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentDecisionRepository implements DecisionRepositoryInterface
{
    public function getAllByUserId(int $userId, array $filters = []): array
    {
        $query = DB::table('decisions')->where('user_id', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('problem', 'like', '%' . $filters['search'] . '%');
        }

        $results = $query->orderBy('id', 'desc')->get();

        return array_map(fn($data) => Decision::fromArray((array)$data), $results->toArray());
    }

    public function getById(int $id): ?Decision
    {
        $data = DB::table('decisions')->find($id);
        return $data ? Decision::fromArray((array)$data) : null;
    }

    public function create(Decision $decision): Decision
    {
        $id = DB::table('decisions')->insertGetId([
            'user_id' => $decision->userId,
            'problem' => $decision->problem,
            'ai_analysis' => $decision->aiAnalysis,
            'status' => $decision->status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $decision->id = $id;
        return $decision;
    }

    public function update(Decision $decision): Decision
    {
        DB::table('decisions')->where('id', $decision->id)->update([
            'problem' => $decision->problem,
            'ai_analysis' => $decision->aiAnalysis,
            'status' => $decision->status,
            'updated_at' => now(),
        ]);

        return $decision;
    }

    public function delete(int $id): bool
    {
        return DB::table('decisions')->where('id', $id)->delete() > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return DB::table('decisions')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => now(),
        ]) > 0;
    }
}

