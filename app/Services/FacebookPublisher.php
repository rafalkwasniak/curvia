<?php

namespace App\Services;

use App\Models\NewsArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Publishes a post to the Facebook Page as a photo with the body text as the
 * caption (the title already lives on the image, so it is not repeated here).
 * The stored WebP is re-encoded to JPEG because the Graph photos endpoint does
 * not reliably accept WebP, and uploaded as raw bytes so it works regardless of
 * whether the public image URL is reachable from Facebook.
 */
class FacebookPublisher
{
    public function isConfigured(): bool
    {
        return filled(config('services.facebook.page_id'))
            && filled(config('services.facebook.access_token'));
    }

    public function publish(NewsArticle $article): void
    {
        if ($article->ai_image_path === null) {
            throw new RuntimeException('Artykuł nie ma zdjęcia do publikacji.');
        }

        $pageId = (string) config('services.facebook.page_id');
        $graph = rtrim((string) config('services.facebook.graph_url'), '/');
        $jpeg = $this->toJpeg(Storage::disk('public')->get($article->ai_image_path));

        $response = Http::attach('source', $jpeg, 'curvia.jpg')
            ->post("{$graph}/{$pageId}/photos", [
                'caption' => (string) $article->ai_post,
                'access_token' => (string) config('services.facebook.access_token'),
            ]);

        if ($response->failed() || $response->json('id') === null) {
            throw new RuntimeException('Facebook API: '.$response->body());
        }
    }

    private function toJpeg(string $binary): string
    {
        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            throw new RuntimeException('Nie udało się odczytać zdjęcia do publikacji.');
        }

        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        return $jpeg;
    }
}
