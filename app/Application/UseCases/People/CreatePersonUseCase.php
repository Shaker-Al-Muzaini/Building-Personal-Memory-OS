<?php

namespace App\Application\UseCases\People;

use App\Domain\Entities\Person;
use App\Domain\Repositories\Contracts\PersonRepositoryInterface;

class CreatePersonUseCase
{
    public function __construct(private PersonRepositoryInterface $personRepository) {}

    public function execute(
        int $userId,
        string $name,
        ?string $relationshipType = null,
        ?string $notes = null
    ): Person {
        $person = new Person(
            id: 0,
            userId: $userId,
            name: $name,
            relationshipType: $relationshipType,
            notes: $notes,
            lastContact: now()
        );

        return $this->personRepository->create($person);
    }
}

