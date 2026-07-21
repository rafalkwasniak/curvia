<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'rss_source_id', 'source_name', 'title', 'url', 'excerpt',
    'content', 'published_at', 'ai_title', 'ai_post',
    'ai_image_path', 'ai_image_prompt', 'ai_score', 'ai_score_reason',
    'scored_at', 'posted_at', 'status',
])]
class NewsArticle extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'posted_at' => 'datetime',
            'scored_at' => 'datetime',
            'ai_score' => 'integer',
            'status' => ArticleStatus::class,
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(RssSource::class, 'rss_source_id');
    }

    /**
     * Tailwind badge classes for the AI score, mirroring the picky bands the
     * scorer uses: strong (green), middling (amber), weak (red), unscored (gray).
     */
    public function scoreBadgeClasses(): string
    {
        return match (true) {
            $this->ai_score === null => 'bg-gray-100 text-gray-500',
            $this->ai_score >= 70 => 'bg-green-100 text-green-800',
            $this->ai_score >= 40 => 'bg-amber-100 text-amber-800',
            default => 'bg-red-100 text-red-700',
        };
    }
}
