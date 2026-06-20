<?php

namespace Database\Seeders;

use App\Models\RssSource;
use Illuminate\Database\Seeder;

class RssSourceSeeder extends Seeder
{
    /**
     * Motorcycle feeds verified to expose a working RSS feed and scrapeable
     * article pages (see the rss-fetch notes). Idempotent: keyed by URL.
     *
     * @var array<int, array{name: string, url: string}>
     */
    private array $sources = [
        ['name' => 'RideApart', 'url' => 'https://www.rideapart.com/rss/category/news/'],
        ['name' => 'Visordown', 'url' => 'https://www.visordown.com/rss'],
        ['name' => 'Ultimate Motorcycling', 'url' => 'https://ultimatemotorcycling.com/feed/'],
        ['name' => 'MCNews Australia', 'url' => 'https://www.mcnews.com.au/feed/'],
        ['name' => 'webBikeWorld', 'url' => 'https://www.webbikeworld.com/feed/'],
        ['name' => 'Asphalt & Rubber', 'url' => 'https://www.asphaltandrubber.com/feed/'],
        ['name' => 'Roadracing World', 'url' => 'https://www.roadracingworld.com/feed/'],
    ];

    public function run(): void
    {
        foreach ($this->sources as $source) {
            RssSource::updateOrCreate(
                ['url' => $source['url']],
                ['name' => $source['name']],
            );
        }
    }
}
