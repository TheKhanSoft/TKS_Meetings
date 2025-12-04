<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AgendaItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'meeting_id',
        'agenda_item_type_id',
        'sequence_number',
        'title',
        'details',
        'owner_user_id',
        'discussion_status',
        'is_left_over',
    ];

    protected $casts = [
        'is_left_over' => 'boolean',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agendaItemType(): BelongsTo
    {
        return $this->belongsTo(AgendaItemType::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function minutes(): HasMany
    {
        return $this->hasMany(Minute::class);
    }

    public function keywords()
    {
        return $this->morphToMany(Keyword::class, 'keywordable');
    }
}
