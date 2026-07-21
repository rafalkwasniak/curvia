<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Services\PostScorer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScorePosts extends Command
{
    protected $signature = 'curvia:score-posts
        {--limit=0 : Maksymalna liczba postów do oceny (0 = bez limitu)}
        {--all : Oceń także posty już ocenione (ponowna ocena)}';

    protected $description = 'Score generated posts 0-100 against the taste profile (informational, does not publish)';

    public function handle(PostScorer $scorer): int
    {
        $query = NewsArticle::whereNotNull('ai_post');

        if (! $this->option('all')) {
            $query->whereNull('scored_at');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $articles = $query->orderByDesc('id')->get();

        if ($articles->isEmpty()) {
            $this->info('Brak postów do oceny.');

            return self::SUCCESS;
        }

        $done = 0;
        $failed = 0;

        foreach ($articles as $article) {
            try {
                $result = $scorer->scoreAndStore($article);
                $this->line("#{$article->id}  ".str_pad((string) $result['score'], 3, ' ', STR_PAD_LEFT)."/100  {$article->ai_title}");
                $done++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning("Post scoring failed for article {$article->id}: ".$e->getMessage());
                $this->error("#{$article->id}  błąd: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Ocenione: {$done} | nieudane: {$failed}");

        return self::SUCCESS;
    }
}
