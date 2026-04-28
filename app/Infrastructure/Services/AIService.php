<?php

namespace App\Infrastructure\Services;

use App\Application\Services\AIServiceClient;
use App\Domain\Repositories\Contracts\PersonRepositoryInterface;
use App\Domain\Repositories\Contracts\DecisionRepositoryInterface;

class AIService
{
    public function __construct(
        private AIServiceClient $aiClient,
        private PersonRepositoryInterface $personRepository,
        private DecisionRepositoryInterface $decisionRepository,
    ) {}

    public function analyzeIdea(int $userId, string $content): array
    {
        // جلب السياق من الأشخاص والقرارات
        $people = $this->personRepository->getAllByUserId($userId);
        $decisions = $this->decisionRepository->getAllByUserId($userId);

        $peopleNames = array_map(fn($p) => $p->name, $people);
        $problemsText = array_map(fn($d) => $d->problem, $decisions);

        $context = "Context - People: " . implode(', ', $peopleNames) . ". Decisions: " . implode(', ', $problemsText);

        $prompt = "لدي هذه الفكرة: " . $content . "\n" .
                  $context . "\n" .
                  "حلل الفكرة وأعطني خطوتين لتطويرها.\n" .
                  "اقترح تصنيفاً واحداً (كلمة واحدة).\n" .
                  "هل ترتبط هذه الفكرة بأي من الأشخاص أو القرارات المذكورة أعلاه؟\n" .
                  "RESPOND IN ARABIC.\n" .
                  "Format: [Category Name] \n Analysis text... \n Neural Suggestion: ...";

        $response = $this->aiClient->sendPrompt($prompt);

        return $this->parseIdeaResponse($response);
    }

    public function analyzeDecision(int $userId, string $problem): string
    {
        $prompt = "لدي مشكلة حقيقية وأحتاج مساعدتك في اتخاذ القرار الصحيح.\n" .
                  "المشكلة: " . $problem . "\n" .
                  "يرجى تحليل هذه المشكلة بعمق وإعطائي:\n" .
                  "1. الخيارات الممكنة\n" .
                  "2. المميزات والعيوب لكل خيار\n" .
                  "3. التوصية النهائية مع التبرير\n" .
                  "RESPOND IN ARABIC ONLY.";

        return $this->aiClient->sendPrompt($prompt, 'أنت مستشار قرارات محترف وعقل ثاني حكيم');
    }

    private function parseIdeaResponse(string $response): array
    {
        $lines = explode("\n", $response);
        $categoryLine = trim(str_replace(['[', ']', 'التصنيف:', '#', '*'], '', $lines[0] ?? 'فكرة'));
        $category = strlen($categoryLine) > 20 ? 'فكرة' : $categoryLine;

        $analysis = implode("\n", array_slice($lines, 1));

        return [
            'category' => $category,
            'analysis' => $analysis,
        ];
    }
}

