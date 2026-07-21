<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScorePostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('taste-profile.md', '# Profil gustu testowy');
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['score' => 55, 'reason' => 'Przeciętny.'])]]],
            ]),
        ]);
    }

    private function makePost(string $url, ?int $score = null): NewsArticle
    {
        return NewsArticle::create([
            'source_name' => 'RideApart',
            'title' => 'Post',
            'url' => $url,
            'ai_title' => 'Tytuł',
            'ai_post' => 'Treść posta o motocyklu. #Moto',
            'ai_score' => $score,
            'scored_at' => $score !== null ? now() : null,
            'status' => ArticleStatus::WaitingReview,
        ]);
    }

    public function test_it_scores_unscored_posts(): void
    {
        $article = $this->makePost('https://site.test/a');

        $this->artisan('curvia:score-posts')->assertSuccessful();

        $this->assertSame(55, $article->refresh()->ai_score);
    }

    public function test_it_skips_already_scored_posts_by_default(): void
    {
        $article = $this->makePost('https://site.test/b', score: 90);

        $this->artisan('curvia:score-posts')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(90, $article->refresh()->ai_score);
    }

    public function test_all_flag_rescoring_overwrites_existing_scores(): void
    {
        $article = $this->makePost('https://site.test/c', score: 90);

        $this->artisan('curvia:score-posts --all')->assertSuccessful();

        $this->assertSame(55, $article->refresh()->ai_score);
    }

    public function test_it_ignores_posts_without_generated_content(): void
    {
        NewsArticle::create([
            'source_name' => 'X',
            'title' => 'No post',
            'url' => 'https://site.test/none',
            'status' => ArticleStatus::New,
        ]);

        $this->artisan('curvia:score-posts')->assertSuccessful();

        Http::assertNothingSent();
    }
}
