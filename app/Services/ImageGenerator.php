<?php

namespace App\Services;

use App\Models\NewsArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orchestrates image generation for an article: DeepSeek writes the visual
 * prompt, Replicate/FLUX renders it, and the resulting WebP is stored on the
 * public disk. Returns both the prompt (shown in the panel) and the stored path.
 */
class ImageGenerator
{
    public function __construct(
        private readonly ImagePromptBuilder $promptBuilder,
        private readonly ReplicateClient $replicate,
        private readonly ImageComposer $composer,
    ) {}

    /**
     * @return array{prompt: string, path: string}
     */
    public function generate(NewsArticle $article): array
    {
        $prompt = $this->promptBuilder->build($article);
        $format = (string) config('curvia.image.output_format', 'webp');

        $urls = $this->replicate->run((string) config('curvia.image.model'), [
            'prompt' => $prompt,
            'aspect_ratio' => (string) config('curvia.image.aspect_ratio', '16:9'),
            'output_format' => $format,
            'output_quality' => (int) config('curvia.image.output_quality', 80),
            'megapixels' => (string) config('curvia.image.megapixels', '1'),
            'num_outputs' => 1,
            'go_fast' => true,
        ]);

        if ($urls === []) {
            throw new RuntimeException('Replicate nie zwrócił obrazu.');
        }

        $binary = Http::timeout(60)->get($urls[0])->throw()->body();
        $binary = $this->composer->compose($binary, $article->ai_title ?: $article->title);

        $path = 'images/'.$article->id.'-'.Str::random(8).'.webp';
        Storage::disk('public')->put($path, $binary);

        return ['prompt' => $prompt, 'path' => $path];
    }
}
