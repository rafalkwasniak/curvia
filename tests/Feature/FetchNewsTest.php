<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\RssSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchNewsTest extends TestCase
{
    use RefreshDatabase;

    private function feedXml(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0"><channel>
            <title>Test Feed</title><link>https://feed.test</link><description>d</description>
            <item>
                <title>New Ducati Panigale V4 unveiled</title>
                <link>https://feed.test/ducati-panigale</link>
                <description><![CDATA[<p>A short teaser about the bike.</p>]]></description>
                <pubDate>Fri, 19 Jun 2026 12:00:00 +0000</pubDate>
            </item>
            <item>
                <title>Yamaha launches a new motorcycle for 2026</title>
                <link>https://feed.test/yamaha-2026</link>
                <description>Another teaser</description>
                <pubDate>Sat, 20 Jun 2026 08:00:00 +0000</pubDate>
            </item>
            <item>
                <title>The best kitchen blenders you can buy</title>
                <link>https://feed.test/blenders</link>
                <description>Not about bikes</description>
                <pubDate>Sat, 20 Jun 2026 09:00:00 +0000</pubDate>
            </item>
        </channel></rss>
        XML;
    }

    public function test_it_stores_only_motorcycle_articles(): void
    {
        $source = RssSource::create(['name' => 'Test', 'url' => 'https://feed.test/rss']);
        Http::fake(['https://feed.test/rss' => Http::response($this->feedXml())]);

        $this->artisan('curvia:fetch-news')->assertSuccessful();

        $this->assertSame(2, NewsArticle::count());
        $this->assertDatabaseHas('news_articles', ['url' => 'https://feed.test/ducati-panigale']);
        $this->assertDatabaseHas('news_articles', ['url' => 'https://feed.test/yamaha-2026']);
        $this->assertDatabaseMissing('news_articles', ['url' => 'https://feed.test/blenders']);
    }

    public function test_it_records_article_details(): void
    {
        $source = RssSource::create(['name' => 'Test', 'url' => 'https://feed.test/rss']);
        Http::fake(['https://feed.test/rss' => Http::response($this->feedXml())]);

        $this->artisan('curvia:fetch-news');

        $article = NewsArticle::where('url', 'https://feed.test/ducati-panigale')->firstOrFail();
        $this->assertSame('New Ducati Panigale V4 unveiled', $article->title);
        $this->assertSame('Test', $article->source_name);
        $this->assertSame($source->id, $article->rss_source_id);
        $this->assertSame('A short teaser about the bike.', $article->excerpt);
        $this->assertNotNull($article->published_at);
    }

    public function test_it_does_not_create_duplicates_on_second_run(): void
    {
        RssSource::create(['name' => 'Test', 'url' => 'https://feed.test/rss']);
        Http::fake(['https://feed.test/rss' => Http::response($this->feedXml())]);

        $this->artisan('curvia:fetch-news');
        $this->artisan('curvia:fetch-news');

        $this->assertSame(2, NewsArticle::count());
    }

    public function test_it_updates_last_fetched_at(): void
    {
        $source = RssSource::create(['name' => 'Test', 'url' => 'https://feed.test/rss']);
        Http::fake(['https://feed.test/rss' => Http::response($this->feedXml())]);

        $this->artisan('curvia:fetch-news');

        $this->assertNotNull($source->refresh()->last_fetched_at);
    }

    public function test_it_skips_inactive_sources(): void
    {
        RssSource::create(['name' => 'Off', 'url' => 'https://feed.test/rss', 'active' => false]);
        Http::fake();

        $this->artisan('curvia:fetch-news');

        Http::assertNothingSent();
        $this->assertSame(0, NewsArticle::count());
    }

    public function test_a_broken_feed_does_not_break_the_run(): void
    {
        RssSource::create(['name' => 'Bad', 'url' => 'https://bad.test/rss']);
        RssSource::create(['name' => 'Good', 'url' => 'https://feed.test/rss']);
        Http::fake([
            'https://bad.test/rss' => Http::response('not xml at all', 500),
            'https://feed.test/rss' => Http::response($this->feedXml()),
        ]);

        $this->artisan('curvia:fetch-news')->assertSuccessful();

        $this->assertSame(2, NewsArticle::count());
    }
}
