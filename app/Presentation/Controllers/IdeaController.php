<?php

namespace App\Presentation\Controllers;

use App\Application\UseCases\Ideas\CreateIdeaUseCase;
use App\Application\UseCases\Ideas\GetIdeasUseCase;
use App\Application\UseCases\Ideas\UpdateIdeaStatusUseCase;
use App\Application\UseCases\Ideas\DeleteIdeaUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IdeaController extends Controller
{
    public function __construct(
        private GetIdeasUseCase $getIdeasUseCase,
        private CreateIdeaUseCase $createIdeaUseCase,
        private UpdateIdeaStatusUseCase $updateIdeaStatusUseCase,
        private DeleteIdeaUseCase $deleteIdeaUseCase,
    ) {}

    public function index(Request $request)
    {
        $ideas = $this->getIdeasUseCase->execute($request->user()->id);

        return Inertia::render('Ideas', [
            'ideas' => array_map(fn($idea) => $idea->toArray(), $ideas)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['content' => 'required|string']);

        $idea = $this->createIdeaUseCase->execute(
            $request->user()->id,
            $request->input('content')
        );

        return back();
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|string|in:draft,developing,ready']);

        $this->updateIdeaStatusUseCase->execute($id, $request->input('status'));

        return back();
    }

    public function destroy(Request $request, $id)
    {
        $this->deleteIdeaUseCase->execute($id);

        return back();
    }
}

