<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'rss_source_id', 'source_name', 'title', 'url', 'excerpt',
    'content', 'published_at', 'ai_title', 'ai_post',
    'ai_image_path', 'ai_image_prompt', 'status',
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
            'status' => ArticleStatus::class,
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(RssSource::class, 'rss_source_id');
    }
}
