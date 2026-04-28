<?php

namespace App\Application\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AIServiceClient
{
    protected string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';
    protected string $model = 'llama-3.3-70b-versatile';

    public function __construct(private string $apiKey) {}

    public function sendPrompt(string $prompt, string $systemRole = 'أنت العقل الثاني والمحلل المتطور للمستخدم.'): string
    {
        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemRole],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 1024,
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? '';
            }

            return 'خدمة الذكاء الاصطناعي غير متاحة حالياً';
        } catch (\Exception $e) {
            return 'حدث خطأ في الاتصال بخدمة الذكاء الاصطناعي';
        }
    }
}

