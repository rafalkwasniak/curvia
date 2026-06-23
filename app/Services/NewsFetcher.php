<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Models\RssSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laminas\Feed\Reader\Reader;
use Throwable;

/**
 * Reads active RSS sources, keeps only motorcycle articles (cheap title
 * filter), dedups by URL, and stores new ones as `new`. RSS only carries a
 * title + link + teaser; the full article body is fetched later.
 */
class NewsFetcher
{
    public function __construct(private readonly MotorcycleFilter $filter) {}

    /**
     * @return array<string, int>
     */
    public function fetch(): array
    {
        $summary = ['sources' => 0, 'created' => 0, 'filtered' => 0, 'too_old' => 0, 'duplicates' => 0, 'errors' => 0];

        $cutoff = now()->subDays((int) config('curvia.max_article_age_days'))->startOfDay();

        foreach (RssSource::where('active', true)->get() as $source) {
            $summary['sources']++;

            try {
                $this->fetchSource($source, $summary, $cutoff);
                $source->last_fetched_at = now();
                $source->save();
            } catch (Throwable $e) {
                $summary['errors']++;
                Log::warning("Feed fetch failed for {$source->name} ({$source->url}): ".$e->getMessage());
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, int>  $summary
     */
    private function fetchSource(RssSource $source, array &$summary, \DateTimeInterface $cutoff): void
    {
        $body = Http::withHeaders(['User-Agent' => config('curvia.user_agent')])
            ->timeout(20)
            ->get($source->url)
            ->throw()
            ->body();

        $feed = Reader::importString($body);

        foreach ($feed as $entry) {
            $title = trim((string) $entry->getTitle());
            $url = trim((string) $entry->getLink());

            if ($url === '' || mb_strlen($url) > 500) {
                continue;
            }

            if (! $this->filter->matches($title)) {
                $summary['filtered']++;

                continue;
            }

            $publishedAt = $this->publishedAt($entry);

            if ($publishedAt === null || $publishedAt < $cutoff) {
                $summary['too_old']++;

                continue;
            }

            if (NewsArticle::where('url', $url)->exists()) {
                $summary['duplicates']++;

                continue;
            }

            NewsArticle::create([
                'rss_source_id' => $source->id,
                'source_name' => $source->name,
                'title' => $title,
                'url' => $url,
                'excerpt' => $this->cleanExcerpt($entry->getDescription()),
                'published_at' => $publishedAt,
                'status' => ArticleStatus::New,
            ]);

            $summary['created']++;
        }
    }

    private function cleanExcerpt(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($raw)));

        return $text === '' ? null : mb_substr($text, 0, 1000);
    }

    private function publishedAt(object $entry): ?\DateTimeInterface
    {
        try {
            return $entry->getDateModified() ?? $entry->getDateCreated();
        } catch (Throwable) {
            return null;
        }
    }
}
