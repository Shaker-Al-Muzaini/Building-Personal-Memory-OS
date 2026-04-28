<?php

namespace App\Domain\Entities;

class Transaction
{
    public function __construct(
        public int $id,
        public int $userId,
        public float $amount,
        public string $type, // 'income' or 'expense'
        public ?string $category,
        public ?string $description,
        public ?\DateTime $createdAt = null,
        public ?\DateTime $updatedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            userId: $data['user_id'],
            amount: (float)$data['amount'],
            type: $data['type'],
            category: $data['category'] ?? null,
            description: $data['description'] ?? null,
            createdAt: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

