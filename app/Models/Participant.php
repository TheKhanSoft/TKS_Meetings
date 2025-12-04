<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Participant extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'title',
        'name',
        'email',
        'phone',
        'address',
        'designation',
        'organization',
    ];

    public function meetings(): MorphToMany
    {
        return $this->morphToMany(Meeting::class, 'participable', 'meeting_participants');
    }
}
