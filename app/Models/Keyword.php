<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = ['name'];

    public function agendaItems()
    {
        return $this->morphedByMany(AgendaItem::class, 'keywordable');
    }

    public function minutes()
    {
        return $this->morphedByMany(Minute::class, 'keywordable');
    }
}
