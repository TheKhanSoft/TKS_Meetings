<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPosition extends Model
{
    protected $fillable = ['user_id', 'position_id', 'position_type', 'appointment_date', 'end_date', 'is_current'];

    protected $casts = [
        'appointment_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
