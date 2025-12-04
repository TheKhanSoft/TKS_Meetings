<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploymentStatus extends Model
{
    protected $fillable = ['name', 'code', 'description'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
