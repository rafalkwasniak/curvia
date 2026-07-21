<?php

namespace App\Services;

use App\Models\NewsArticle;
use RuntimeException;

/**
 * Scores a generated post 0-100 against the stored taste profile: how well it
 * fits what Rafał actually publishes. Returns the score and a short Polish
 * rationale. Purely informational - it does not change the article's status or
 * publish anything.
 */
class PostScorer
{
    public function __construct(
        private readonly DeepSeekClient $client,
        private readonly TasteProfileBuilder $profiles,
    ) {}

    /**
     * @return array{score: int, reason: string}
     */
    public function score(NewsArticle $article): array
    {
        if (empty($article->ai_post)) {
            throw new RuntimeException('Nie można ocenić posta bez wygenerowanej treści.');
        }

        $profile = $this->profiles->current();

        if ($profile === null) {
            throw new RuntimeException('Brak profilu gustu - uruchom najpierw: php artisan curvia:build-taste-profile');
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($profile)],
            ['role' => 'user', 'content' => $this->userPrompt($article)],
        ];

        $raw = $this->client->chat($messages, (float) config('curvia.scoring.temperature', 0.2));
        $data = json_decode($raw, true);

        if (! is_array($data) || ! isset($data['score'])) {
            throw new RuntimeException('Nieprawidłowa odpowiedź AI (brak pola "score").');
        }

        $score = max(0, min(100, (int) $data['score']));
        $reason = trim((string) ($data['reason'] ?? ''));

        return ['score' => $score, 'reason' => $reason];
    }

    /**
     * Score the article and persist the result on it.
     *
     * @return array{score: int, reason: string}
     */
    public function scoreAndStore(NewsArticle $article): array
    {
        $result = $this->score($article);

        $article->ai_score = $result['score'];
        $article->ai_score_reason = $result['reason'];
        $article->scored_at = now();
        $article->save();

        return $result;
    }

    private function systemPrompt(string $profile): string
    {
        return <<<PROMPT
        Jesteś redaktorem oceniającym posty na motocyklowy fanpage Curvia. Oceniasz, jak bardzo dany post pasuje do gustu redaktora naczelnego i jak wartościowy jest do publikacji. Poniżej masz PROFIL GUSTU wywnioskowany z jego dotychczasowych decyzji - stosuj go rygorystycznie.

        --- PROFIL GUSTU ---
        {$profile}
        --- KONIEC PROFILU ---

        Redaktor jest wymagający i odrzuca większość postów, więc nie zawyżaj ocen. Wysoki wynik (80-100) zarezerwuj dla postów, które wyraźnie trafiają w jego gust. Średni (40-70) dla przeciętnych. Niski (0-40) dla słabych lub takich, które ma zwyczaj odrzucać.

        Oceń post w skali 0-100 i podaj krótkie (1-2 zdania) uzasadnienie PO POLSKU.

        Zwróć WYŁĄCZNIE obiekt JSON w formacie: {"score": <liczba 0-100>, "reason": "..."}
        PROMPT;
    }

    private function userPrompt(NewsArticle $article): string
    {
        return "Źródło: {$article->source_name}\n"
            ."Tytuł: {$article->ai_title}\n\n"
            ."Treść posta:\n{$article->ai_post}";
    }
}
