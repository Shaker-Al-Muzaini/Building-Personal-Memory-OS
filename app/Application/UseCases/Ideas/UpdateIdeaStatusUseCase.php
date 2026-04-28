<?php

namespace App\Application\UseCases\Ideas;

use App\Domain\Repositories\Contracts\IdeaRepositoryInterface;

class UpdateIdeaStatusUseCase
{
    public function __construct(private IdeaRepositoryInterface $ideaRepository) {}

    public function execute(int $ideaId, string $status): bool
    {
        return $this->ideaRepository->updateStatus($ideaId, $status);
    }
}

