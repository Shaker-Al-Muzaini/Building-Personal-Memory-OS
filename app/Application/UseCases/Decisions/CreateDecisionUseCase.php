<?php

namespace App\Application\UseCases\Decisions;

use App\Domain\Entities\Decision;
use App\Domain\Repositories\Contracts\DecisionRepositoryInterface;
use App\Infrastructure\Services\AIService;

class CreateDecisionUseCase
{
    public function __construct(
        private DecisionRepositoryInterface $decisionRepository,
        private AIService $aiService
    ) {}

    public function execute(int $userId, string $problem): Decision
    {
        // تحليل المشكلة والقرار باستخدام الذكاء الاصطناعي
        $analysis = $this->aiService->analyzeDecision($userId, $problem);

        // إنشاء كيان القرار
        $decision = new Decision(
            id: 0,
            userId: $userId,
            problem: $problem,
            aiAnalysis: $analysis,
            status: 'pending'
        );

        // حفظ القرار في قاعدة البيانات
        return $this->decisionRepository->create($decision);
    }
}

