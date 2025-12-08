<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Setting;

class SettingPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view settings');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->can('view settings');
    }

    public function create(User $user): bool
    {
        // Settings are usually seeded or fixed, but if dynamic creation is allowed:
        return $user->can('edit settings'); 
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->can('edit settings');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->can('edit settings'); // Usually settings aren't deleted, just updated
    }
}
