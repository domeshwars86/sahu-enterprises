<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EducationalArticle extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'status', 'author_id', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
