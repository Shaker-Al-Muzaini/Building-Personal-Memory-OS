<?php

namespace App\Presentation\Controllers;

use App\Application\UseCases\Decisions\CreateDecisionUseCase;
use App\Application\UseCases\Decisions\GetDecisionsUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DecisionController extends Controller
{
    public function __construct(
        private GetDecisionsUseCase $getDecisionsUseCase,
        private CreateDecisionUseCase $createDecisionUseCase,
    ) {}

    public function index(Request $request)
    {
        $decisions = $this->getDecisionsUseCase->execute($request->user()->id);

        return Inertia::render('Decisions', [
            'decisions' => array_map(fn($decision) => $decision->toArray(), $decisions)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['problem' => 'required|string']);

        $decision = $this->createDecisionUseCase->execute(
            $request->user()->id,
            $request->input('problem')
        );

        return back();
    }
}

