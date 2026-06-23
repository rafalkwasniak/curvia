<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Outbound HTTP User-Agent
    |--------------------------------------------------------------------------
    |
    | Feeds and article pages are fetched with a real browser User-Agent.
    | Some sites (e.g. RideApart) return empty bodies to bot-looking agents.
    |
    */

    'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',

    /*
    |--------------------------------------------------------------------------
    | Minimum scraped content length
    |--------------------------------------------------------------------------
    |
    | Articles whose extracted body is shorter than this are treated as failed
    | scrapes (paywall, JS-only, blocked) and left without content.
    |
    */

    'min_content_chars' => 600,

    /*
    |--------------------------------------------------------------------------
    | Maximum article age
    |--------------------------------------------------------------------------
    |
    | Feeds only ingest articles published within this many days (counted from
    | the start of the cut-off day). Older entries are skipped so stale news can
    | never re-enter the review queue and skew later quality scoring. Entries
    | without a parseable publish date are skipped as well.
    |
    */

    'max_article_age_days' => 2,

    /*
    |--------------------------------------------------------------------------
    | AI post generation
    |--------------------------------------------------------------------------
    |
    | Shape of the Polish Facebook post DeepSeek produces from an article.
    |
    */

    'generation' => [
        'title_max_chars' => 90,
        'post_min_chars' => 500,
        'post_max_chars' => 800,
        'content_limit_chars' => 6000,
        'temperature' => 0.8,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI image generation
    |--------------------------------------------------------------------------
    |
    | The illustrative image for a post. DeepSeek writes the English prompt and
    | Replicate/FLUX renders it. flux-schnell is fast and cheap (~$0.003/image);
    | swap to black-forest-labs/flux-dev for higher fidelity at higher cost.
    | 16:9 is the closest preset to the 1200x630 og:image ratio - the exact crop
    | and the "CURVIA" overlay are a later, local post-processing step.
    |
    */

    'image' => [
        // Models, cheapest to best: flux-schnell (~$0.003), flux-dev (~$0.025),
        // flux-1.1-pro (~$0.04). All accept the same input keys used below.
        // On flux-dev for production: clearly more realistic than schnell, worth
        // the cost now that the overlay look is finished.
        'model' => 'black-forest-labs/flux-dev',
        'aspect_ratio' => '16:9',
        'output_format' => 'webp',
        'output_quality' => 80,
        'megapixels' => '1',
        // The article text is only a visual cue here, so far less is sent than
        // for the post rewrite - the prompt builder needs the gist, not the body.
        'prompt_content_limit_chars' => 2000,
        'temperature' => 0.7,

        // Branded overlay drawn on top of the generated photo: a left-side dark
        // scrim guarantees text legibility on any crop, then the CURVIA logo,
        // the post title, a yellow accent rule and a fixed kicker line. Text is
        // rendered here (never by the image model) so Polish glyphs stay correct.
        'overlay' => [
            'accent_color' => '#EAB227',
            'logo_path' => 'img/curvia-logo.png',
            'font_title' => 'resources/fonts/Poppins-SemiBold.ttf',
            'font_kicker' => 'resources/fonts/Poppins-Regular.ttf',
            'kicker' => 'Premiery • Technologie • Innowacje',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook publishing
    |--------------------------------------------------------------------------
    |
    | Approved posts are auto-published to Facebook inside these daily windows.
    | The scheduler picks ONE random minute per window per day (deterministic
    | from the date) and sends the oldest approved post then - at most one per
    | window - so posts do not go out at the same minute every day. Times are
    | "HH:MM" in the app timezone. Credentials live in config/services.php.
    |
    */

    'facebook' => [
        'windows' => [
            ['09:00', '11:00'],
            ['18:00', '20:00'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Motorcycle keyword filter
    |--------------------------------------------------------------------------
    |
    | A fetched article is kept only when its title matches one of these
    | keywords (whole-word, case-insensitive). This is the cheap first-pass
    | filter that decides which articles are worth the expensive AI rewrite.
    | Tune the list freely - sources are motorcycle-only, so brand names that
    | also make cars (Honda, BMW, Suzuki...) are safe here.
    |
    */

    'motorcycle_keywords' => [
        // Generic
        'motorcycle', 'motorcycles', 'motorbike', 'motorbikes', 'moto', 'bike', 'biker',
        'scooter', 'superbike', 'sportbike', 'cruiser', 'cafe racer', 'two-stroke',
        'enduro', 'motocross', 'supercross', 'adventure bike', 'dual-sport', 'dual sport',
        // Racing
        'motogp', 'moto2', 'moto3', 'wsbk', 'superbikes', 'dakar', 'isle of man', 'tt race',
        // Brands
        'ducati', 'ktm', 'kawasaki', 'yamaha', 'honda', 'suzuki', 'triumph', 'aprilia',
        'harley', 'harley-davidson', 'bmw motorrad', 'royal enfield', 'mv agusta',
        'husqvarna', 'indian motorcycle', 'moto guzzi', 'benelli', 'cfmoto', 'cf moto',
        'zero motorcycles', 'energica', 'vespa', 'piaggio', 'norton', 'bimota', 'gasgas',
        'gas gas', 'beta', 'sherco', 'can-am',
    ],

];
