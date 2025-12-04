<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = ['name', 'code', 'is_unique', 'role_id'];

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_positions')
                    ->withPivot('start_date', 'end_date', 'is_current')
                    ->withTimestamps();
    }
}
