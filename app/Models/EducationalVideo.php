<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalVideo extends Model
{
    protected $fillable = ['title', 'url', 'publisher', 'description', 'category', 'is_featured', 'is_visible', 'display_order'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_visible' => 'boolean', 'display_order' => 'integer'];
    }
}
