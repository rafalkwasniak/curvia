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
        $articles = NewsArticle::whereNull('content')
            ->where('status', ArticleStatus::New)
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
                $failed++;
            }
        }

        $this->info("Pobrano treść: {$done} | nieudane/pominięte: {$failed}");

        return self::SUCCESS;
    }
}
