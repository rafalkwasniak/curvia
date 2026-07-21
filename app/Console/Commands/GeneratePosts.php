<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\PostGenerator;
use App\Services\PostScorer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePosts extends Command
{
    protected $signature = 'curvia:generate-posts {--limit=10}';

    protected $description = 'Generate Polish Facebook posts from scraped articles via DeepSeek';

    public function handle(PostGenerator $generator, PostScorer $scorer): int
    {
        $articles = NewsArticle::where('status', ArticleStatus::New)
            ->whereNotNull('content')
            ->limit((int) $this->option('limit'))
            ->get();

        $done = 0;
        $failed = 0;

        foreach ($articles as $article) {
            try {
                $generated = $generator->generate($article);

                $article->ai_title = $generated['title'];
                $article->ai_post = $generated['post'];
                $article->status = ArticleStatus::WaitingReview;
                $article->save();

                // Score the fresh post against the taste profile so the review
                // list shows its value straight away. A scoring failure (e.g. no
                // profile yet) must never lose the generated post - swallow it,
                // the scheduled score-posts backfill will retry later.
                try {
                    $scorer->scoreAndStore($article);
                } catch (Throwable $e) {
                    Log::warning("Post scoring failed for article {$article->id}: ".$e->getMessage());
                }

                $done++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning("Post generation failed for article {$article->id} ({$article->url}): ".$e->getMessage());
            }
        }

        $this->info("Wygenerowano: {$done} | nieudane: {$failed}");

        return self::SUCCESS;
    }
}
