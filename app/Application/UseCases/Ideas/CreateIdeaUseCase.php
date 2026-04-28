<?php

namespace App\Application\UseCases\Ideas;

use App\Domain\Entities\Idea;
use App\Domain\Repositories\Contracts\IdeaRepositoryInterface;
use App\Infrastructure\Services\AIService;

class CreateIdeaUseCase
{
    public function __construct(
        private IdeaRepositoryInterface $ideaRepository,
        private AIService $aiService
    ) {}

    public function execute(int $userId, string $content): Idea
    {
        // تحليل الفكرة باستخدام الذكاء الاصطناعي
        $analysis = $this->aiService->analyzeIdea($userId, $content);

        // إنشاء كيان الفكرة
        $idea = new Idea(
            id: 0,
            userId: $userId,
            content: $content,
            aiAnalysis: $analysis['analysis'],
            status: 'draft',
            category: $analysis['category']
        );

        // حفظ الفكرة في قاعدة البيانات
        return $this->ideaRepository->create($idea);
    }
}

