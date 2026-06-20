<?php

namespace App\Services;

use App\Models\NewsArticle;
use RuntimeException;

/**
 * Turns a fetched article into a Polish, editorial Facebook post via DeepSeek.
 * The model writes the title and body; the canonical "Źródło: ..." footer is
 * appended here so its format and the source name are always correct.
 */
class PostGenerator
{
    public function __construct(private readonly DeepSeekClient $client) {}

    /**
     * @return array{title: string, post: string}
     */
    public function generate(NewsArticle $article): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($article)],
        ];

        $raw = $this->client->chat($messages, (float) config('curvia.generation.temperature', 0.8));
        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['title']) || empty($data['post'])) {
            throw new RuntimeException('Nieprawidłowa odpowiedź AI (brak title/post).');
        }

        $title = mb_substr(trim((string) $data['title']), 0, (int) config('curvia.generation.title_max_chars', 90));
        $post = trim((string) $data['post'])."\n\n──────────\nŹródło: ".$article->source_name;

        return ['title' => $title, 'post' => $post];
    }

    private function systemPrompt(): string
    {
        $min = (int) config('curvia.generation.post_min_chars', 500);
        $max = (int) config('curvia.generation.post_max_chars', 800);
        $titleMax = (int) config('curvia.generation.title_max_chars', 90);

        return <<<PROMPT
        Jesteś redaktorem polskiego fanpage'a motocyklowego o nazwie Curvia. Na podstawie zagranicznego artykułu piszesz krótki, wciągający post na Facebooka PO POLSKU.

        Zasady:
        - Nie pisz jak portal informacyjny ani jak suchy news. Pisz swobodnie i lekko, jak pasjonat do pasjonatów.
        - Post ma zachęcać do komentarzy (np. krótkie pytanie do czytelników na końcu).
        - Długość posta: od {$min} do {$max} znaków.
        - Zakończ 2-4 trafnymi hashtagami (np. #Motocykle #Ducati).
        - NIE dodawaj linków ani adresów URL. NIE dodawaj linii ze źródłem - zostanie dopisana automatycznie.
        - Tytuł: krótki i chwytliwy, po polsku, maksymalnie {$titleMax} znaków.
        - Trzymaj się faktów z artykułu, nie zmyślaj danych.

        Zwróć WYŁĄCZNIE obiekt JSON w formacie: {"title": "...", "post": "..."}
        PROMPT;
    }

    private function userPrompt(NewsArticle $article): string
    {
        $body = $article->content ?: ($article->excerpt ?? '');
        $body = mb_substr($body, 0, (int) config('curvia.generation.content_limit_chars', 6000));

        return "Serwis źródłowy: {$article->source_name}\n"
            ."Oryginalny tytuł: {$article->title}\n\n"
            ."Treść artykułu:\n{$body}";
    }
}
