<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\ArticleScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchContent extends Command
{
    protected $signature = 'curvia:fetch-content {--limit=20}';

    protected $description = 'Download and extract full article text for new articles';

    public function handle(ArticleScraper $scraper): int
    {
        // Skip articles that keep failing and try the least-attempted first, so
        // a single blocked page cannot stall everything queued behind it.
        $maxAttempts = (int) config('curvia.max_scrape_attempts', 3);

        $articles = NewsArticle::whereNull('content')
            ->where('status', ArticleStatus::New)
            ->where('scrape_attempts', '<', $maxAttempts)
            ->orderBy('scrape_attempts')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        $done = 0;
        $failed = 0;

        foreach ($articles as $article) {
            try {
                $text = $scraper->scrape($article->url);
            } catch (Throwable $e) {
                $text = null;
                Log::warning("Content scrape failed for {$article->url}: ".$e->getMessage());
            }

            if ($text !== null) {
                $article->content = $text;
                $article->save();
                $done++;
            } else {
                $article->scrape_attempts++;

                // Out of attempts: flag it so the list shows the source is
                // unreachable instead of leaving it looking freshly queued.
                if ($article->scrape_attempts >= $maxAttempts) {
                    $article->status = ArticleStatus::ScrapeFailed;
                }

                $article->save();
                $failed++;
            }
        }

        $this->info("Pobrano treść: {$done} | nieudane/pominięte: {$failed}");

        return self::SUCCESS;
    }
}
