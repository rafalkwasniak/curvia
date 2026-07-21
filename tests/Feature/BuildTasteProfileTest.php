<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuildTasteProfileTest extends TestCase
{
    use RefreshDatabase;

    private function seedPosts(ArticleStatus $status, int $count, string $tag): void
    {
        for ($i = 0; $i < $count; $i++) {
            NewsArticle::create([
                'source_name' => 'RideApart',
                'title' => "{$tag} {$i}",
                'url' => "https://site.test/{$tag}-{$i}",
                'ai_title' => "Tytuł {$tag} {$i}",
                'ai_post' => "Treść posta {$tag} numer {$i}. #Moto",
                'status' => $status,
            ]);
        }
    }

    public function test_it_builds_and_stores_a_profile_from_history(): void
    {
        Storage::fake('local');
        $this->seedPosts(ArticleStatus::Published, 3, 'keep');
        $this->seedPosts(ArticleStatus::Rejected, 3, 'drop');
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['profile' => '# Profil gustu'])]]],
            ]),
        ]);

        $this->artisan('curvia:build-taste-profile')->assertSuccessful();

        Storage::disk('local')->assertExists('taste-profile.md');
        $this->assertStringContainsString('Profil gustu', Storage::disk('local')->get('taste-profile.md'));
    }

    public function test_it_fails_without_enough_history(): void
    {
        Storage::fake('local');
        $this->seedPosts(ArticleStatus::Published, 2, 'keep');
        Http::fake();

        $this->artisan('curvia:build-taste-profile')->assertFailed();

        Http::assertNothingSent();
        Storage::disk('local')->assertMissing('taste-profile.md');
    }
}
