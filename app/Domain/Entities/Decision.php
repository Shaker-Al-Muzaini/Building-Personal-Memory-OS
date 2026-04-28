<?php

namespace App\Domain\Entities;

class Decision
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $problem,
        public ?string $aiAnalysis,
        public string $status,
        public ?\DateTime $createdAt = null,
        public ?\DateTime $updatedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            userId: $data['user_id'],
            problem: $data['problem'],
            aiAnalysis: $data['ai_analysis'] ?? null,
            status: $data['status'] ?? 'pending',
            createdAt: isset($data['created_at']) ? new \DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new \DateTime($data['updated_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'problem' => $this->problem,
            'ai_analysis' => $this->aiAnalysis,
            'status' => $this->status,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

