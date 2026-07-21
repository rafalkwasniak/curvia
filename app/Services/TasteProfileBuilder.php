<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Distills Rafał's moderation history into an explicit, human-readable "taste
 * profile": what separates the posts he PUBLISHES from the ones he REJECTS.
 * The profile is written to storage as Markdown so it can be inspected and
 * hand-tuned, and is later fed to the PostScorer as the scoring rubric.
 */
class TasteProfileBuilder
{
    public function __construct(private readonly DeepSeekClient $client) {}

    /**
     * Build the profile from history and persist it. Returns the Markdown text.
     */
    public function build(int $samplePerClass = 60): string
    {
        $kept = $this->sample([ArticleStatus::Published, ArticleStatus::Approved], $samplePerClass);
        $rejected = $this->sample([ArticleStatus::Rejected], $samplePerClass);

        if ($kept->isEmpty() || $rejected->isEmpty()) {
            throw new RuntimeException('Za mało danych historycznych do zbudowania profilu gustu (potrzeba zaakceptowanych i odrzuconych postów).');
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($kept, $rejected)],
        ];

        $raw = $this->client->chat($messages, (float) config('curvia.scoring.profile_temperature', 0.4));
        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['profile'])) {
            throw new RuntimeException('Nieprawidłowa odpowiedź AI (brak pola "profile").');
        }

        $profile = trim((string) $data['profile']);

        Storage::disk('local')->put($this->path(), $profile);

        return $profile;
    }

    /**
     * The stored profile, or null if it has not been built yet.
     */
    public function current(): ?string
    {
        return Storage::disk('local')->exists($this->path())
            ? Storage::disk('local')->get($this->path())
            : null;
    }

    private function path(): string
    {
        return (string) config('curvia.scoring.profile_path', 'taste-profile.md');
    }

    /**
     * @param  array<int, ArticleStatus>  $statuses
     * @return Collection<int, NewsArticle>
     */
    private function sample(array $statuses, int $limit)
    {
        return NewsArticle::whereIn('status', $statuses)
            ->whereNotNull('ai_post')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, NewsArticle>  $kept
     * @param  Collection<int, NewsArticle>  $rejected
     */
    private function userPrompt($kept, $rejected): string
    {
        return "POSTY, KTÓRE REDAKTOR OPUBLIKOWAŁ (pozytywne przykłady):\n\n"
            .$this->render($kept)
            ."\n\n=====================================\n\n"
            ."POSTY, KTÓRE REDAKTOR ODRZUCIŁ (negatywne przykłady):\n\n"
            .$this->render($rejected);
    }

    /**
     * @param  Collection<int, NewsArticle>  $articles
     */
    private function render($articles): string
    {
        $limit = (int) config('curvia.scoring.example_chars', 500);

        return $articles
            ->map(function (NewsArticle $a) use ($limit) {
                $body = mb_substr((string) $a->ai_post, 0, $limit);

                return "- Źródło: {$a->source_name}\n  Tytuł: {$a->ai_title}\n  Treść: {$body}";
            })
            ->implode("\n\n");
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        Jesteś analitykiem redakcyjnym. Dostajesz dwie grupy postów z motocyklowego fanpage'a: te, które redaktor OPUBLIKOWAŁ, oraz te, które ODRZUCIŁ. Twoim zadaniem jest wywnioskować, co odróżnia posty publikowane od odrzucanych - czyli gust i kryteria redaktora.

        Przeanalizuj tematy, źródła, styl, długość, ton, atrakcyjność tematu dla polskiego czytelnika-motocyklisty. Zbuduj zwięzły, konkretny "profil gustu" w języku polskim, jako rubrykę oceny nowych postów.

        Profil musi zawierać:
        1. CECHY POSTÓW PUBLIKOWANYCH (co redaktor lubi) - lista konkretnych sygnałów.
        2. CECHY POSTÓW ODRZUCANYCH (czerwone flagi) - lista konkretnych sygnałów.
        3. WSKAZÓWKI DO PUNKTACJI 0-100 - jak przełożyć te cechy na ocenę wartości posta (co daje wysoki wynik, co niski).

        Bądź konkretny i oparty na przykładach, nie ogólnikowy. Unikaj frazesów. Jeśli widać wzorzec tematyczny lub źródłowy - nazwij go wprost.

        Zwróć WYŁĄCZNIE obiekt JSON w formacie: {"profile": "..."} gdzie wartość to profil w formacie Markdown.
        PROMPT;
    }
}
