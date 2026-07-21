<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            // AI quality score for a generated post: 0-100, how well it fits
            // Rafał's taste (learned from his own publish/reject history). This
            // is informational only for now - nothing is published on its basis.
            $table->unsignedTinyInteger('ai_score')->nullable()->after('ai_image_prompt');
            $table->text('ai_score_reason')->nullable()->after('ai_score');
            $table->timestamp('scored_at')->nullable()->after('ai_score_reason');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn(['ai_score', 'ai_score_reason', 'scored_at']);
        });
    }
};
