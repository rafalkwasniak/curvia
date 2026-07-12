<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\FacebookPublisher;
use App\Services\FacebookWindowScheduler;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishToFacebook extends Command
{
    protected $signature = 'curvia:publish-facebook';

    protected $description = 'Publish the newest approved post to Facebook within the configured time windows';

    public function handle(FacebookWindowScheduler $scheduler, FacebookPublisher $publisher): int
    {
        if (! $publisher->isConfigured()) {
            $this->info('Facebook nie jest skonfigurowany - pomijam.');

            return self::SUCCESS;
        }

        if (! $scheduler->isDue(now())) {
            $this->warnIfWindowMissed($scheduler, now());

            return self::SUCCESS;
        }

        $article = $this->newestPublishable();

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

    /**
     * The newest approved post that still has an image and has not gone out yet,
     * so fresh news goes out first and archival posts only when nothing newer waits.
     */
    private function newestPublishable(): ?NewsArticle
    {
        return NewsArticle::where('status', ArticleStatus::Approved)
            ->whereNotNull('ai_image_path')
            ->whereNull('posted_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Leave a warning in the log when a publish window has just closed without
     * sending while an approved post was still waiting - so a missed slot is
     * visible immediately instead of needing a manual audit after the fact.
     */
    private function warnIfWindowMissed(FacebookWindowScheduler $scheduler, CarbonInterface $now): void
    {
        $window = $scheduler->recentlyMissedWindow($now);

        if ($window === null || $this->newestPublishable() === null) {
            return;
        }

        Log::warning(sprintf(
            'Facebook: okno %s-%s zamknięte bez publikacji, choć zatwierdzony wpis czekał w kolejce.',
            $window[0]->format('H:i'),
            $window[1]->format('H:i'),
        ));
    }
}
