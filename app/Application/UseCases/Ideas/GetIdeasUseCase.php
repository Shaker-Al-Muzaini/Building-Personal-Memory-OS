<?php

namespace App\Application\UseCases\Ideas;

use App\Domain\Repositories\Contracts\IdeaRepositoryInterface;

class GetIdeasUseCase
{
    public function __construct(private IdeaRepositoryInterface $ideaRepository) {}

    public function execute(int $userId, array $filters = []): array
    {
        return $this->ideaRepository->getAllByUserId($userId, $filters);
    }
}

