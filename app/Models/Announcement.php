<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'published_at',
        'expires_at',
        'is_active',
        'created_by',
        'audience_type',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->published_at && $this->published_at->isFuture()) {
            return 'Scheduled';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetUsers()
    {
        return $this->belongsToMany(User::class, 'announcement_targets');
    }

    public function excludedUsers()
    {
        return $this->belongsToMany(User::class, 'announcement_exceptions');
    }

    public function scopeVisible($query)
    {
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function($q) use ($user) {
            // Condition A: User has override permission
            if ($user->can('view hidden announcements')) {
                $q->whereRaw('1 = 1'); // Explicitly allow everything
                return;
            }

            // Condition B: User is the creator
            $q->where('created_by', $user->id)
              ->orWhere(function($sub) use ($user) {
                  // Condition C: Standard Visibility Rules
                  
                  // 1. Must be Active
                  $sub->where('is_active', true);

                  // 2. Must be Published (not future)
                  $sub->where(function($d) use ($user) {
                      $d->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                        
                      if ($user->can('view scheduled announcements')) {
                          $d->orWhere('published_at', '>', now());
                      }
                  });

                  // 3. Must NOT be Expired
                  $sub->where(function($d) {
                      $d->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                  });

                  // 4. Audience Check
                  $sub->where(function($group) use ($user) {
                      $group->where(function($audience) use ($user) {
                          // Case 1: All Users (or null/default)
                          $audience->where(function($a) {
                                       $a->where('audience_type', 'all')
                                         ->orWhereNull('audience_type');
                                   })
                                   ->whereDoesntHave('excludedUsers', function($ex) use ($user) {
                                       $ex->where('user_id', $user->id);
                                   });
                      })->orWhere(function($audience) use ($user) {
                          // Case 2: Specific Users
                          $audience->where('audience_type', 'users')
                                   ->whereHas('targetUsers', function($target) use ($user) {
                                       $target->where('user_id', $user->id);
                                   })
                                   ->whereDoesntHave('excludedUsers', function($ex) use ($user) {
                                       $ex->where('user_id', $user->id);
                                   });
                      });
                  });
              });
        });
    }
}
