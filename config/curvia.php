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
