<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpArticle extends Model
{
    protected $fillable = ['help_category_id', 'title', 'slug', 'content', 'is_published', 'view_count', 'helpful_count', 'not_helpful_count', 'order'];

    public function category()
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }
}
