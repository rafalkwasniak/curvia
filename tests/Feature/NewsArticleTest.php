<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Models\RssSource;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_defaults_to_new_and_is_cast_to_enum(): void
    {
        $article = NewsArticle::create([
            'source_name' => 'RideApart',
            'title' => 'Some motorcycle news',
            'url' => 'https://example.com/article-1',
        ]);

        $this->assertSame(ArticleStatus::New, $article->refresh()->status);
    }

    public function test_it_belongs_to_a_source(): void
    {
        $source = RssSource::create(['name' => 'RideApart', 'url' => 'https://example.com/feed']);
        $article = NewsArticle::create([
            'rss_source_id' => $source->id,
            'source_name' => $source->name,
            'title' => 'Title',
            'url' => 'https://example.com/article-2',
        ]);

        $this->assertTrue($article->source->is($source));
        $this->assertCount(1, $source->articles);
    }

    public function test_url_is_unique(): void
    {
        NewsArticle::create([
            'source_name' => 'X',
            'title' => 'A',
            'url' => 'https://example.com/dup',
        ]);

        $this->expectException(QueryException::class);

        NewsArticle::create([
            'source_name' => 'Y',
            'title' => 'B',
            'url' => 'https://example.com/dup',
        ]);
    }
}
