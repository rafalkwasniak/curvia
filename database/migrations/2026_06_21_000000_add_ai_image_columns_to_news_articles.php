<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->string('ai_image_path', 500)->nullable()->after('ai_post');
            $table->text('ai_image_prompt')->nullable()->after('ai_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn(['ai_image_path', 'ai_image_prompt']);
        });
    }
};
