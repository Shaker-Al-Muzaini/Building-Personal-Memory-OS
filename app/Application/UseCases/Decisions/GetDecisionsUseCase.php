<?php

namespace App\Application\UseCases\Decisions;

use App\Domain\Repositories\Contracts\DecisionRepositoryInterface;

class GetDecisionsUseCase
{
    public function __construct(private DecisionRepositoryInterface $decisionRepository) {}

    public function execute(int $userId, array $filters = []): array
    {
        return $this->decisionRepository->getAllByUserId($userId, $filters);
    }
}

