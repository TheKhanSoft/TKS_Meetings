<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'minute_id',
        'notification_no',
        'notification_date',
    ];

    protected $casts = [
        'notification_date' => 'date',
    ];

    public function minute(): BelongsTo
    {
        return $this->belongsTo(Minute::class);
    }
}
