<?php

namespace App\Application\UseCases\Ideas;

use App\Domain\Repositories\Contracts\IdeaRepositoryInterface;

class DeleteIdeaUseCase
{
    public function __construct(private IdeaRepositoryInterface $ideaRepository) {}

    public function execute(int $ideaId): bool
    {
        return $this->ideaRepository->delete($ideaId);
    }
}

