<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use App\Services\FacebookPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class FacebookPublisherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.facebook.page_id' => '1165129356684253',
            'services.facebook.access_token' => 'test-token',
            'services.facebook.graph_url' => 'https://graph.facebook.com/v21.0',
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('images/test.webp', $this->fakeWebp());
    }

    public function test_it_posts_a_photo_with_the_body_as_caption(): void
    {
        Http::fake(['*' => Http::response(['id' => '999', 'post_id' => '1165_999'], 200)]);

        $article = $this->article();
        app(FacebookPublisher::class)->publish($article);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/1165129356684253/photos')
                && collect($request->data())->contains(fn ($field) => ($field['name'] ?? '') === 'caption' && $field['contents'] === 'Treść posta');
        });
    }

    public function test_it_throws_on_api_error(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'bad token']], 400)]);

        $this->expectException(RuntimeException::class);

        app(FacebookPublisher::class)->publish($this->article());
    }

    public function test_it_throws_when_article_has_no_image(): void
    {
        $article = $this->article();
        $article->ai_image_path = null;

        $this->expectException(RuntimeException::class);

        app(FacebookPublisher::class)->publish($article);
    }

    private function article(): NewsArticle
    {
        return NewsArticle::create([
            'source_name' => 'Test',
            'title' => 'Test',
            'url' => 'https://example.test/'.uniqid(),
            'ai_title' => 'Tytuł',
            'ai_post' => 'Treść posta',
            'ai_image_path' => 'images/test.webp',
            'status' => ArticleStatus::Approved->value,
        ]);
    }

    private function fakeWebp(): string
    {
        $image = imagecreatetruecolor(20, 20);
        ob_start();
        imagewebp($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }
}
