<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            // Failed content scrapes so far. Once it reaches the configured cap
            // the article is skipped by the scheduled fetch, so one blocked page
            // can never stall the queue behind it.
            $table->unsignedTinyInteger('scrape_attempts')->default(0)->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn('scrape_attempts');
        });
    }
};
