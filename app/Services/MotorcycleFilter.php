<?php

namespace App\Services;

/**
 * Cheap first-pass filter: decides whether an article is about motorcycles
 * from its title alone (no AI, no network). Keeps the expensive AI rewrite
 * for articles that pass. Keywords live in config/curvia.php.
 */
class MotorcycleFilter
{
    public function matches(string $title): bool
    {
        $title = trim($title);

        if ($title === '') {
            return false;
        }

        foreach ((array) config('curvia.motorcycle_keywords', []) as $keyword) {
            if ($keyword === '') {
                continue;
            }

            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/iu', $title) === 1) {
                return true;
            }
        }

        return false;
    }
}
