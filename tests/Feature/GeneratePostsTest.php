<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeneratePostsTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDeepSeek(string $title, string $post): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['title' => $title, 'post' => $post])]]],
            ]),
        ]);
    }

    private function articleWithContent(): NewsArticle
    {
        return NewsArticle::create([
            'source_name' => 'RideApart',
            'title' => 'New Ducati unveiled',
            'url' => 'https://site.test/ducati',
            'content' => str_repeat('Real article body about the motorcycle. ', 30),
            'status' => ArticleStatus::New,
        ]);
    }

    public function test_it_generates_a_post_and_moves_to_review(): void
    {
        $article = $this->articleWithContent();
        $this->fakeDeepSeek('Nowa Ducati robi wrażenie', 'Świeża Ducati wjeżdża z przytupem. Co o niej sądzicie? #Motocykle #Ducati');

        $this->artisan('curvia:generate-posts')->assertSuccessful();

        $article->refresh();
        $this->assertSame(ArticleStatus::WaitingReview, $article->status);
        $this->assertSame('Nowa Ducati robi wrażenie', $article->ai_title);
        $this->assertStringContainsString('przytupem', $article->ai_post);
        $this->assertStringContainsString('Źródło: RideApart', $article->ai_post);
    }

    public function test_it_truncates_an_overlong_title(): void
    {
        $this->articleWithContent();
        $this->fakeDeepSeek(str_repeat('a', 200), 'Treść posta #Moto');

        $this->artisan('curvia:generate-posts');

        $this->assertLessThanOrEqual(90, mb_strlen(NewsArticle::firstOrFail()->ai_title));
    }

    public function test_it_skips_articles_without_content(): void
    {
        NewsArticle::create([
            'source_name' => 'X',
            'title' => 'No content yet',
            'url' => 'https://site.test/empty',
            'status' => ArticleStatus::New,
        ]);
        Http::fake();

        $this->artisan('curvia:generate-posts');

        Http::assertNothingSent();
        $this->assertSame(ArticleStatus::New, NewsArticle::firstOrFail()->status);
    }

    public function test_an_api_failure_leaves_the_article_new(): void
    {
        $article = $this->articleWithContent();
        Http::fake(['api.deepseek.com/*' => Http::response('error', 500)]);

        $this->artisan('curvia:generate-posts')->assertSuccessful();

        $article->refresh();
        $this->assertSame(ArticleStatus::New, $article->status);
        $this->assertNull($article->ai_post);
    }

    public function test_an_invalid_ai_response_is_treated_as_failure(): void
    {
        $article = $this->articleWithContent();
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'not json']]],
            ]),
        ]);

        $this->artisan('curvia:generate-posts');

        $this->assertSame(ArticleStatus::New, $article->refresh()->status);
    }
}
