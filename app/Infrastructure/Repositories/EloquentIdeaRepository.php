<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Idea;
use App\Domain\Repositories\Contracts\IdeaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentIdeaRepository implements IdeaRepositoryInterface
{
    public function getAllByUserId(int $userId, array $filters = []): array
    {
        $query = DB::table('ideas')->where('user_id', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }

        $results = $query->orderBy('id', 'desc')->get();

        return array_map(fn($data) => Idea::fromArray((array)$data), $results->toArray());
    }

    public function getById(int $id): ?Idea
    {
        $data = DB::table('ideas')->find($id);
        return $data ? Idea::fromArray((array)$data) : null;
    }

    public function create(Idea $idea): Idea
    {
        $id = DB::table('ideas')->insertGetId([
            'user_id' => $idea->userId,
            'content' => $idea->content,
            'ai_analysis' => $idea->aiAnalysis,
            'status' => $idea->status,
            'category' => $idea->category,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $idea->id = $id;
        return $idea;
    }

    public function update(Idea $idea): Idea
    {
        DB::table('ideas')->where('id', $idea->id)->update([
            'content' => $idea->content,
            'ai_analysis' => $idea->aiAnalysis,
            'status' => $idea->status,
            'category' => $idea->category,
            'updated_at' => now(),
        ]);

        return $idea;
    }

    public function delete(int $id): bool
    {
        return DB::table('ideas')->where('id', $id)->delete() > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return DB::table('ideas')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => now(),
        ]) > 0;
    }
}

