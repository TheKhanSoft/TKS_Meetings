<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = ['name'];
    
    protected $appends = ['total_count'];

    public function agendaItems()
    {
        return $this->morphedByMany(AgendaItem::class, 'keywordable');
    }

    public function minutes()
    {
        return $this->morphedByMany(Minute::class, 'keywordable');
    }

    public function getTotalCountAttribute()
    {
        return ($this->agenda_items_count ?? 0) + ($this->minutes_count ?? 0);
    }
}
