<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin client for the DeepSeek chat API (OpenAI-compatible). Returns the raw
 * assistant message content; callers decide how to interpret it.
 */
class DeepSeekClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, float $temperature = 0.7): string
    {
        $response = Http::withToken((string) config('services.deepseek.key'))
            ->baseUrl((string) config('services.deepseek.base_url'))
            ->timeout(90)
            ->post('/chat/completions', [
                'model' => config('services.deepseek.model'),
                'messages' => $messages,
                'temperature' => $temperature,
                'response_format' => ['type' => 'json_object'],
            ])
            ->throw()
            ->json();

        return (string) ($response['choices'][0]['message']['content'] ?? '');
    }
}
