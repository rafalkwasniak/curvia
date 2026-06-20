<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function generatedArticle(): NewsArticle
    {
        return NewsArticle::create([
            'source_name' => 'RideApart',
            'title' => 'New Ducati unveiled',
            'url' => 'https://site.test/ducati',
            'content' => str_repeat('Body. ', 50),
            'ai_title' => 'Nowa Ducati robi wrażenie',
            'ai_post' => "Świeża Ducati wjeżdża z przytupem.\n\nŹródło: RideApart",
            'status' => ArticleStatus::WaitingReview,
        ]);
    }

    public function test_guests_cannot_see_the_articles(): void
    {
        $this->get('/articles')->assertRedirect('/login');
    }

    public function test_list_shows_generated_posts_only(): void
    {
        $this->actingAs(User::factory()->create());
        $this->generatedArticle();
        NewsArticle::create([
            'source_name' => 'X', 'title' => 'Raw, not generated',
            'url' => 'https://site.test/raw', 'status' => ArticleStatus::New,
        ]);

        $this->get('/articles')->assertOk()
            ->assertSee('Nowa Ducati robi wrażenie')
            ->assertDontSee('Raw, not generated');
    }

    public function test_detail_shows_original_and_generated(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->generatedArticle();

        $this->get(route('articles.show', $article))->assertOk()
            ->assertSee('New Ducati unveiled')
            ->assertSee('przytupem');
    }

    public function test_a_post_can_be_approved(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->generatedArticle();

        $this->post(route('articles.accept', $article));

        $this->assertSame(ArticleStatus::Approved, $article->refresh()->status);
    }

    public function test_a_post_can_be_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->generatedArticle();

        $this->post(route('articles.reject', $article));

        $this->assertSame(ArticleStatus::Rejected, $article->refresh()->status);
    }

    public function test_a_post_can_be_regenerated(): void
    {
        $this->actingAs(User::factory()->create());
        $article = $this->generatedArticle();
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'title' => 'Zupełnie nowy tytuł',
                    'post' => 'Zupełnie nowa treść posta #Moto',
                ])]]],
            ]),
        ]);

        $this->post(route('articles.regenerate', $article));

        $article->refresh();
        $this->assertSame('Zupełnie nowy tytuł', $article->ai_title);
        $this->assertStringContainsString('nowa treść', $article->ai_post);
        $this->assertSame(ArticleStatus::WaitingReview, $article->status);
    }
}
