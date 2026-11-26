<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalFaq extends Model
{
    protected $fillable = ['question', 'answer', 'display_order', 'is_visible'];

    protected function casts(): array
    {
        return ['display_order' => 'integer', 'is_visible' => 'boolean'];
    }
}
