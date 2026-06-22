<?php

namespace App\Services;

use App\Models\NewsArticle;
use RuntimeException;

/**
 * Turns an article into a single English text-to-image prompt for FLUX. The key
 * rule: never the whole vehicle - always a tight detail crop - so the image
 * evokes the article without trying (and failing) to render a specific, possibly
 * unreleased model. When the article centers on a concrete, existing motorcycle,
 * the crop is steered onto that model's signature design cues, so the detail
 * belongs to the right bike. DeepSeek also adapts the mood to the article's
 * context.
 */
class ImagePromptBuilder
{
    public function __construct(private readonly DeepSeekClient $client) {}

    public function build(NewsArticle $article): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($article)],
        ];

        $raw = $this->client->chat($messages, (float) config('curvia.image.temperature', 0.7));
        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['prompt'])) {
            throw new RuntimeException('Nieprawidłowa odpowiedź AI (brak prompt).');
        }

        return trim((string) $data['prompt']);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        You are an art director for Curvia, a motorcycle media brand. From a Polish motorcycle news article you write ONE English text-to-image prompt for a photorealistic photograph that will illustrate the post.

        First, identify the subject bike: does the article center on, or clearly name, a specific real motorcycle (make and model, e.g. Ducati Panigale V4, BMW R 1300 GS, Yamaha MT-09)? If yes, the detail you show must belong to THAT model - pick the crop that highlights its signature design cues (the shape of its headlight, its single- or double-sided swingarm, its winglets, its distinctive tail or exhaust, the silhouette of its tank). Describe those concrete cues so an enthusiast recognizes the bike from the detail alone. If the article names no specific model, or the model is brand-new, speculative or otherwise something you cannot picture reliably, fall back to a generic but characterful motorcycle detail.

        Hard rules:
        - Show a RECOGNIZABLE, evocative PART of a motorcycle - roughly one section of the bike, about a tenth to a third of it in frame - composed so a viewer instantly sees it is a motorcycle. Good crops: the front end (headlight, fairing and front fork), the cockpit (clip-on bars, top of the fuel tank, instrument cluster), the engine and frame with part of a wheel, the rear (seat, tail unit and exhaust), a wheel with brake disc and caliper and lower fairing.
        - Do NOT zoom into a tiny isolated component (a single bolt, nut, screw, a bare patch of metal) - the subject must clearly read as a motorcycle, not an abstract close-up. Make it a tasteful, characterful crop that shows off design and materials.
        - But do NOT show the whole motorcycle or a full vehicle either. A partial, cropped composition is deliberate: even when the detail belongs to a specific model, show only that one section - never the entire bike - so the image evokes the article rather than posing as a full catalogue render.
        - Match the mood to the article context: racing or competition -> dynamic, motion blur, low angle, track tarmac, speed; a new model presentation or launch -> clean, static, sharp studio-like lighting; touring or travel -> outdoors, natural light, road or landscape hints.
        - Photorealistic professional editorial automotive photography: shallow depth of field, realistic materials, metal and carbon reflections, fine detail, natural lighting.
        - ABSOLUTELY NO text, letters, words, numbers, watermarks, brand logos, badges or emblems, license plates, and NO recognizable human faces.
        - Keep it concise: 1 to 3 vivid, concrete sentences describing only what is in frame.

        Return ONLY a JSON object in the format: {"prompt": "..."}
        PROMPT;
    }

    private function userPrompt(NewsArticle $article): string
    {
        $body = $article->content ?: ($article->excerpt ?? '');
        $body = mb_substr($body, 0, (int) config('curvia.image.prompt_content_limit_chars', 2000));

        return "Source: {$article->source_name}\n"
            ."Original title: {$article->title}\n\n"
            ."Article text:\n{$body}";
    }
}
