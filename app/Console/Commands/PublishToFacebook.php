<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\FacebookPublisher;
use App\Services\FacebookWindowScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishToFacebook extends Command
{
    protected $signature = 'curvia:publish-facebook';

    protected $description = 'Publish the oldest approved post to Facebook within the configured time windows';

    public function handle(FacebookWindowScheduler $scheduler, FacebookPublisher $publisher): int
    {
        if (! $publisher->isConfigured()) {
            $this->info('Facebook nie jest skonfigurowany - pomijam.');

            return self::SUCCESS;
        }

        if (! $scheduler->isDue(now())) {
            return self::SUCCESS;
        }

        $article = NewsArticle::where('status', ArticleStatus::Approved)
            ->whereNotNull('ai_image_path')
            ->orderBy('published_at')
            ->orderBy('id')
            ->first();

        if ($article === null) {
            return self::SUCCESS;
        }

        try {
            $publisher->publish($article);

            $article->update([
                'status' => ArticleStatus::Published,
                'posted_at' => now(),
            ]);

            $this->info("Opublikowano artykuł {$article->id} na Facebooku.");
        } catch (Throwable $e) {
            Log::warning("Facebook publish failed for article {$article->id}: ".$e->getMessage());
            $this->error('Publikacja na Facebooku nie powiodła się.');
        }

        return self::SUCCESS;
    }
}
