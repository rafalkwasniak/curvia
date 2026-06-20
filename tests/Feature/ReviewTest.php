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
            'published_at' => now(),
        ]);
    }

    private function articleHtml(): string
    {
        $para = 'The new motorcycle pushes the class forward with a lighter frame and a sharper '
            .'engine, and the electronics come straight from the race paddock for real control.';

        return '<html><head><title>Bike</title></head><body><article><h1>Bike</h1>'
            .str_repeat("<p>{$para}</p>", 6).'</article></body></html>';
    }

    public function test_guests_cannot_see_the_articles(): void
    {
        $this->get('/articles')->assertRedirect('/login');
    }

    public function test_list_shows_all_articles_newest_first(): void
    {
        $this->actingAs(User::factory()->create());
        NewsArticle::create([
            'source_name' => 'Visordown', 'title' => 'Older raw article',
            'url' => 'https://site.test/old', 'status' => ArticleStatus::New,
            'published_at' => now()->subDay(),
        ]);
        $this->generatedArticle(); // newer, generated

        $this->get('/articles')->assertOk()
            ->assertSeeInOrder(['Nowa Ducati robi wrażenie', 'Older raw article']);
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

    public function test_generate_reruns_for_an_article_with_content(): void
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

        $this->post(route('articles.generate', $article));

        $article->refresh();
        $this->assertSame('Zupełnie nowy tytuł', $article->ai_title);
        $this->assertSame(ArticleStatus::WaitingReview, $article->status);
    }

    public function test_generate_scrapes_content_for_a_new_article(): void
    {
        $this->actingAs(User::factory()->create());
        $article = NewsArticle::create([
            'source_name' => 'Visordown', 'title' => 'Fresh one',
            'url' => 'https://site.test/fresh', 'status' => ArticleStatus::New,
        ]);
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'title' => 'Polski tytuł', 'post' => 'Polski post #Moto',
                ])]]],
            ]),
            '*' => Http::response($this->articleHtml()),
        ]);

        $this->post(route('articles.generate', $article));

        $article->refresh();
        $this->assertNotNull($article->content);
        $this->assertSame('Polski tytuł', $article->ai_title);
        $this->assertSame(ArticleStatus::WaitingReview, $article->status);
    }
}
