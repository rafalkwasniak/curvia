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
        $articles = NewsArticle::whereNull('content')
            ->where('status', ArticleStatus::New)
            ->where('scrape_attempts', '<', (int) config('curvia.max_scrape_attempts', 3))
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
                $article->increment('scrape_attempts');
                $failed++;
            }
        }

        $this->info("Pobrano treść: {$done} | nieudane/pominięte: {$failed}");

        return self::SUCCESS;
    }
}
