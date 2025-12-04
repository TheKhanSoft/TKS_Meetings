<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'order', 'is_active'];

    public function articles()
    {
        return $this->hasMany(HelpArticle::class);
    }
}
