<?php

namespace App\Presentation\Controllers;

use App\Application\UseCases\People\CreatePersonUseCase;
use App\Application\UseCases\People\GetPeopleUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeopleController extends Controller
{
    public function __construct(
        private GetPeopleUseCase $getPeopleUseCase,
        private CreatePersonUseCase $createPersonUseCase,
    ) {}

    public function index(Request $request)
    {
        $people = $this->getPeopleUseCase->execute($request->user()->id);

        return Inertia::render('People', [
            'people' => array_map(fn($person) => $person->toArray(), $people)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'relationship_type' => 'nullable|string',
        ]);

        $person = $this->createPersonUseCase->execute(
            $request->user()->id,
            $request->input('name'),
            $request->input('relationship_type'),
            $request->input('notes')
        );

        return back();
    }
}

