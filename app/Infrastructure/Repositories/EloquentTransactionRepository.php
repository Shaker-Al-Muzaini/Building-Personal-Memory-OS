<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Transaction;
use App\Domain\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function getAllByUserId(int $userId, array $filters = []): array
    {
        $query = DB::table('transactions')->where('user_id', $userId);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->whereBetween('created_at', [
                $filters['from'] . ' 00:00:00',
                $filters['to'] . ' 23:59:59'
            ]);
        }

        $results = $query->orderBy('created_at', 'desc')->get();

        return array_map(fn($data) => Transaction::fromArray((array)$data), $results->toArray());
    }

    public function getById(int $id): ?Transaction
    {
        $data = DB::table('transactions')->find($id);
        return $data ? Transaction::fromArray((array)$data) : null;
    }

    public function create(Transaction $transaction): Transaction
    {
        $id = DB::table('transactions')->insertGetId([
            'user_id' => $transaction->userId,
            'amount' => $transaction->amount,
            'type' => $transaction->type,
            'category' => $transaction->category,
            'description' => $transaction->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transaction->id = $id;
        return $transaction;
    }

    public function update(Transaction $transaction): Transaction
    {
        DB::table('transactions')->where('id', $transaction->id)->update([
            'amount' => $transaction->amount,
            'type' => $transaction->type,
            'category' => $transaction->category,
            'description' => $transaction->description,
            'updated_at' => now(),
        ]);

        return $transaction;
    }

    public function delete(int $id): bool
    {
        return DB::table('transactions')->where('id', $id)->delete() > 0;
    }

    public function getExpensesInDateRange(int $userId, \DateTime $from, \DateTime $to): float
    {
        return (float)DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('created_at', [
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s')
            ])
            ->sum('amount');
    }

    public function getIncomeInDateRange(int $userId, \DateTime $from, \DateTime $to): float
    {
        return (float)DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('created_at', [
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s')
            ])
            ->sum('amount');
    }
}

