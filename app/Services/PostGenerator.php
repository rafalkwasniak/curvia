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

        $title = mb_substr($this->sanitize((string) $data['title']), 0, (int) config('curvia.generation.title_max_chars', 90));
        $post = $this->sanitize((string) $data['post'])."\n\n──────────\nŹródło: ".$article->source_name;

        return ['title' => $title, 'post' => $post];
    }

    /**
     * Strip emoji, emoticons and other graphic symbols - the posts must be
     * plain text only - and tidy up the leftover whitespace.
     */
    private function sanitize(string $text): string
    {
        $text = preg_replace(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2190}-\x{21FF}\x{2B00}-\x{2BFF}\x{2300}-\x{23FF}\x{FE00}-\x{FE0F}\x{1F1E6}-\x{1F1FF}\x{200D}\x{20E3}\x{2122}\x{2139}]/u',
            '',
            $text,
        );

        $text = preg_replace('/[ \t]+/', ' ', (string) $text);
        $text = preg_replace('/ *\n/', "\n", (string) $text);
        $text = preg_replace('/\n{3,}/', "\n\n", (string) $text);
        $text = trim((string) $text);

        // Keep exactly one blank line before a trailing hashtag line.
        return preg_replace('/\n+(#[^\n]+)$/u', "\n\n$1", $text);
    }

    private function systemPrompt(): string
    {
        $min = (int) config('curvia.generation.post_min_chars', 500);
        $max = (int) config('curvia.generation.post_max_chars', 800);
        $titleMax = (int) config('curvia.generation.title_max_chars', 90);

        return <<<PROMPT
        Jesteś doświadczonym polskim dziennikarzem i redaktorem motoryzacyjnym piszącym dla fanpage'a Curvia. Masz lekkie pióro, wyrazisty własny głos i swobodnie poruszasz się w świecie motocykli. Na podstawie zagranicznego artykułu piszesz autorski post na Facebooka PO POLSKU.

        Zasady:
        - Pisz jak dziennikarz z polotem, nie jak tłumacz. Nie streszczaj źródła zdanie po zdaniu - napisz własny tekst na jego podstawie.
        - Zacznij mocnym leadem (hakiem), który wciąga od pierwszego zdania.
        - Dodaj kontekst, barwny i obrazowy język oraz własną, lekką ocenę - ale trzymaj się faktów z artykułu, nie zmyślaj danych.
        - Pisz swobodnie, ale ze znawstwem - jak ktoś, kto naprawdę zna temat.
        - Podziel post na 2-4 krótkie akapity oddzielone pustą linią, żeby dobrze się czytało.
        - Zachęć do komentarzy (krótkie pytanie do czytelników na końcu).
        - Długość posta: od {$min} do {$max} znaków.
        - Zakończ 2-4 trafnymi hashtagami w osobnej, ostatniej linii, poprzedzonej pustą linią (np. #Motocykle #Ducati).
        - PISZ WYŁĄCZNIE ZWYKŁYM TEKSTEM. Absolutnie żadnych emoji, emotikonów, ikon ani symboli graficznych.
        - NIE dodawaj linków ani adresów URL. NIE dodawaj linii ze źródłem - zostanie dopisana automatycznie.
        - Tytuł: chwytliwy i dziennikarski, po polsku, maksymalnie {$titleMax} znaków, bez emoji.

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
