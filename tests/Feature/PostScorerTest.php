<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\PostScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PostScorerTest extends TestCase
{
    use RefreshDatabase;

    private function fakeProfile(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('taste-profile.md', '# Profil gustu testowy');
    }

    private function fakeScore(int $score, string $reason = 'Trafia w gust.'): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['score' => $score, 'reason' => $reason])]]],
            ]),
        ]);
    }

    private function makePost(): NewsArticle
    {
        return NewsArticle::create([
            'source_name' => 'RideApart',
            'title' => 'New Ducati',
            'url' => 'https://site.test/ducati',
            'ai_title' => 'Nowa Ducati robi wrażenie',
            'ai_post' => 'Świeża Ducati wjeżdża z przytupem. #Ducati',
            'status' => ArticleStatus::WaitingReview,
        ]);
    }

    public function test_it_scores_and_stores_the_result(): void
    {
        $this->fakeProfile();
        $this->fakeScore(82, 'Mocny temat premierowy.');
        $article = $this->makePost();

        app(PostScorer::class)->scoreAndStore($article);

        $article->refresh();
        $this->assertSame(82, $article->ai_score);
        $this->assertSame('Mocny temat premierowy.', $article->ai_score_reason);
        $this->assertNotNull($article->scored_at);
    }

    public function test_it_clamps_the_score_into_0_100(): void
    {
        $this->fakeProfile();
        $this->fakeScore(140);

        $result = app(PostScorer::class)->score($this->makePost());

        $this->assertSame(100, $result['score']);
    }

    public function test_it_fails_without_a_taste_profile(): void
    {
        Storage::fake('local');
        Http::fake();

        $this->expectException(RuntimeException::class);
        app(PostScorer::class)->score($this->makePost());

        Http::assertNothingSent();
    }

    public function test_it_fails_on_a_post_without_content(): void
    {
        $this->fakeProfile();
        Http::fake();
        $article = NewsArticle::create([
            'source_name' => 'X',
            'title' => 'No post',
            'url' => 'https://site.test/none',
            'status' => ArticleStatus::New,
        ]);

        $this->expectException(RuntimeException::class);
        app(PostScorer::class)->score($article);
    }
}
