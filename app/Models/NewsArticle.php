<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $fillable = [
        'external_id',
        'slug',
        'title',
        'source_url',
        'source_name',
        'category',
        'published_at',
        'fetched_at',
        'is_published',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'fetched_at' => 'immutable_datetime',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }
}
