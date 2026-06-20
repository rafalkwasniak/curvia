<?php

namespace App\Services;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Http;

/**
 * Fetches an article page and extracts its main body text (Readability).
 * RSS only gives a teaser, so this is where the real content comes from.
 * Returns null when the page is blocked, JS-only or too short to be useful.
 */
class ArticleScraper
{
    public function scrape(string $url): ?string
    {
        $html = Http::withHeaders(['User-Agent' => config('curvia.user_agent')])
            ->timeout(25)
            ->get($url)
            ->throw()
            ->body();

        $readability = new Readability(new Configuration);

        try {
            $readability->parse($html);
        } catch (ParseException) {
            return null;
        }

        $text = $this->toPlainText($readability->getContent());

        return mb_strlen($text) >= (int) config('curvia.min_content_chars', 600) ? $text : null;
    }

    private function toPlainText(?string $html): string
    {
        if ($html === null) {
            return '';
        }

        $html = preg_replace('#</(p|div|h[1-6]|li|br)\s*/?>#i', "\n\n", $html);
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", (string) $text);

        return trim((string) $text);
    }
}
