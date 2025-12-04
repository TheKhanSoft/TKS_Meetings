<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Minute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agenda_item_id',
        'decision',
        'action_required',
        'approval_status',
        'responsible_user_id',
        'target_due_date',
    ];

    protected $casts = [
        'target_due_date' => 'date',
    ];

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function keywords()
    {
        return $this->morphToMany(Keyword::class, 'keywordable');
    }
}
