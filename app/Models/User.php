<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'cnic_no',
        'phone',
        'profile_photo_path',
        'date_of_birth',
        'gender',
        'address',
        'postal_code',
        'nationality',
        'marital_status',
        'emergency_contact',
        'emergency_contact_relationship',
        'password',
        'employment_status_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    public function positions()
    {
        return $this->belongsToMany(Position::class, 'user_positions')
                    ->withPivot('position_type', 'appointment_date', 'end_date', 'is_current')
                    ->withTimestamps();
    }

    public function currentPosition()
    {
        return $this->positions()->wherePivot('is_current', true)->first();
    }

    public function employmentStatus()
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function meetings()
    {
        return $this->morphToMany(Meeting::class, 'participable', 'meeting_participants')
                    ->withPivot('type')
                    ->withTimestamps();
    }
}
