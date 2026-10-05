<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Articles that already used up their scrape attempts were left as `new`
     * forever; move them to the dedicated failure status.
     */
    public function up(): void
    {
        DB::table('news_articles')
            ->where('status', 'new')
            ->whereNull('content')
            ->where('scrape_attempts', '>=', (int) config('curvia.max_scrape_attempts', 3))
            ->update(['status' => 'scrape_failed']);
    }

    public function down(): void
    {
        DB::table('news_articles')
            ->where('status', 'scrape_failed')
            ->update(['status' => 'new']);
    }
};
