<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Meeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'number',
        'meeting_type_id',
        'date',
        'time',
        'is_last',
        'total_agenda_items',
        'items_discussed',
        'items_left_over',
        'director_id',
        'registrar_id',
        'vc_id',
        'entry_by_id',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime', // Or string, but datetime is better if casting works for time column
        'is_last' => 'boolean',
    ];

    public function meetingType(): BelongsTo
    {
        return $this->belongsTo(MeetingType::class);
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_id');
    }

    public function vc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vc_id');
    }

    public function entryBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entry_by_id');
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function minutes(): HasManyThrough
    {
        return $this->hasManyThrough(Minute::class, AgendaItem::class);
    }

    // --- Participants Logic ---

    // All Participants linked to this meeting
    public function participants(): MorphToMany
    {
        return $this->morphedByMany(Participant::class, 'participable', 'meeting_participants')
                    ->withPivot('type')
                    ->withTimestamps();
    }

    // Helpers for specific types
    public function getMembersAttribute()
    {
        return $this->participants->where('pivot.type', 'member');
    }

    public function getAttendeesAttribute()
    {
        return $this->participants->where('pivot.type', 'attendee');
    }
}
