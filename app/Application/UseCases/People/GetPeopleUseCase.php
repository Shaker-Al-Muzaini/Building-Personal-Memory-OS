<?php

namespace App\Application\UseCases\People;

use App\Domain\Repositories\Contracts\PersonRepositoryInterface;

class GetPeopleUseCase
{
    public function __construct(private PersonRepositoryInterface $personRepository) {}

    public function execute(int $userId, array $filters = []): array
    {
        return $this->personRepository->getAllByUserId($userId, $filters);
    }
}

