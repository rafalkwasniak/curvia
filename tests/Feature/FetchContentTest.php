<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchContentTest extends TestCase
{
    use RefreshDatabase;

    private function articleHtml(): string
    {
        $para = 'The new motorcycle pushes the class forward with a lighter frame, a sharper '
            .'engine and electronics borrowed straight from the race paddock. Riders will notice '
            .'the difference the moment they pull away from a standstill on a twisty road.';

        return '<html><head><title>Bike Review</title></head><body><article>'
            ."<h1>A Proper Motorcycle Review</h1><p>{$para}</p><p>{$para}</p><p>{$para}</p>"
            .'</article></body></html>';
    }

    private function makeArticle(string $url): NewsArticle
    {
        return NewsArticle::create([
            'source_name' => 'Test',
            'title' => 'A Proper Motorcycle Review',
            'url' => $url,
            'status' => ArticleStatus::New,
        ]);
    }

    public function test_it_extracts_and_stores_article_content(): void
    {
        $article = $this->makeArticle('https://site.test/review');
        Http::fake(['https://site.test/review' => Http::response($this->articleHtml())]);

        $this->artisan('curvia:fetch-content')->assertSuccessful();

        $content = $article->refresh()->content;
        $this->assertNotNull($content);
        $this->assertStringContainsString('lighter frame', $content);
        $this->assertGreaterThan(600, mb_strlen($content));
    }

    public function test_short_pages_are_left_without_content(): void
    {
        $article = $this->makeArticle('https://site.test/stub');
        Http::fake(['https://site.test/stub' => Http::response('<html><body><p>Too short.</p></body></html>')]);

        $this->artisan('curvia:fetch-content');

        $this->assertNull($article->refresh()->content);
    }

    public function test_a_failed_request_does_not_break_the_run(): void
    {
        $article = $this->makeArticle('https://site.test/dead');
        Http::fake(['https://site.test/dead' => Http::response('error', 500)]);

        $this->artisan('curvia:fetch-content')->assertSuccessful();

        $this->assertNull($article->refresh()->content);
    }

    public function test_failed_scrapes_are_counted(): void
    {
        $blocked = $this->makeArticle('https://site.test/blocked');
        $short = $this->makeArticle('https://site.test/stub');
        Http::fake([
            'https://site.test/blocked' => Http::response('Forbidden', 403),
            'https://site.test/stub' => Http::response('<html><body><p>Too short.</p></body></html>'),
        ]);

        $this->artisan('curvia:fetch-content');

        $this->assertSame(1, $blocked->refresh()->scrape_attempts);
        $this->assertSame(1, $short->refresh()->scrape_attempts);
    }

    public function test_a_blocked_article_does_not_stall_the_queue(): void
    {
        $blocked = $this->makeArticle('https://site.test/blocked');
        $good = $this->makeArticle('https://site.test/review');
        Http::fake([
            'https://site.test/blocked' => Http::response('Forbidden', 403),
            'https://site.test/review' => Http::response($this->articleHtml()),
        ]);

        // Paced like the scheduler: one article per run.
        $this->artisan('curvia:fetch-content --limit=1');
        $this->artisan('curvia:fetch-content --limit=1');

        $this->assertSame(1, $blocked->refresh()->scrape_attempts);
        $this->assertNotNull($good->refresh()->content);
    }

    public function test_articles_are_given_up_after_max_attempts(): void
    {
        config(['curvia.max_scrape_attempts' => 3]);
        $article = $this->makeArticle('https://site.test/blocked');
        $article->scrape_attempts = 3;
        $article->save();
        Http::fake();

        $this->artisan('curvia:fetch-content')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(ArticleStatus::New, $article->refresh()->status);
    }

    public function test_the_last_failed_attempt_marks_the_article_as_scrape_failed(): void
    {
        config(['curvia.max_scrape_attempts' => 3]);
        $article = $this->makeArticle('https://site.test/blocked');
        $article->scrape_attempts = 2;
        $article->save();
        Http::fake(['https://site.test/blocked' => Http::response('Forbidden', 403)]);

        $this->artisan('curvia:fetch-content')->assertSuccessful();

        $article->refresh();
        $this->assertSame(3, $article->scrape_attempts);
        $this->assertSame(ArticleStatus::ScrapeFailed, $article->status);
    }

    public function test_a_failure_with_attempts_left_keeps_the_article_new(): void
    {
        config(['curvia.max_scrape_attempts' => 3]);
        $article = $this->makeArticle('https://site.test/blocked');
        Http::fake(['https://site.test/blocked' => Http::response('Forbidden', 403)]);

        $this->artisan('curvia:fetch-content');

        $this->assertSame(ArticleStatus::New, $article->refresh()->status);
    }

    public function test_it_skips_articles_that_already_have_content(): void
    {
        $article = $this->makeArticle('https://site.test/has-content');
        $article->content = 'already here';
        $article->save();
        Http::fake();

        $this->artisan('curvia:fetch-content');

        Http::assertNothingSent();
        $this->assertSame('already here', $article->refresh()->content);
    }
}
