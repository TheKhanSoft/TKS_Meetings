<?php

namespace App\Services;

use App\Models\User;
use App\Models\Position;
use App\Models\UserPosition;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class UserService
{
    public function getAllUsers()
    {
        return User::with('positions')->latest()->get();
    }

    public function createUser(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::create(Arr::except($data, ['position_id', 'appointment_date', 'end_date', 'is_current', 'roles', 'permissions']));

        if (isset($data['roles'])) {
            $user->assignRole($data['roles']);
        }

        if (isset($data['permissions'])) {
            $user->givePermissionTo($data['permissions']);
        }

        if (isset($data['position_id'])) {
            $this->assignPosition($user, $data['position_id'], $data['appointment_date'] ?? now());
        }

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        \DB::transaction(function () use ($user, $data) {
            $user->update(Arr::except($data, ['position_id', 'appointment_date', 'end_date', 'is_current', 'roles', 'permissions']));

            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            if (isset($data['permissions'])) {
                $user->syncPermissions($data['permissions']);
            }

            if (array_key_exists('position_id', $data)) {
                $newPositionId = $data['position_id'];
                $startDate = isset($data['appointment_date']) ? Carbon::parse($data['appointment_date']) : now();

                $currentPositions = $user->positions()->wherePivot('is_current', true)->get();

                if (is_null($newPositionId)) {
                    // Remove all current positions
                    foreach ($currentPositions as $pos) {
                        $user->positions()->updateExistingPivot($pos->id, [
                            'is_current' => false,
                            'end_date' => $startDate->copy()->subDay(),
                        ]);
                    }
                } else {
                    // Check if user already has this position
                    $alreadyHas = $currentPositions->contains('id', $newPositionId);

                    if (!$alreadyHas) {
                        // End other positions (assuming single position model from UI)
                        foreach ($currentPositions as $pos) {
                            $user->positions()->updateExistingPivot($pos->id, [
                                'is_current' => false,
                                'end_date' => $startDate->copy()->subDay(),
                            ]);
                        }
                        
                        // Assign new position
                        $this->assignPosition($user, $newPositionId, $startDate);
                    }
                }
            }
        });

        return $user;
    }

    public function deleteUser(User $user): bool
    {
        // Check if user is the only Super Admin
        $superAdminPos = Position::where('code', 'super_admin')->first();
        if ($superAdminPos) {
            $isSuperAdmin = $user->positions()
                ->where('position_id', $superAdminPos->id)
                ->wherePivot('is_current', true)
                ->exists();

            if ($isSuperAdmin) {
                $count = UserPosition::where('position_id', $superAdminPos->id)
                    ->where('is_current', true)
                    ->count();
                
                if ($count <= 1) {
                    throw new \Exception("Cannot delete the only Super Admin.");
                }
            }
        }

        return $user->delete();
    }

    public function assignPosition(User $user, int $positionId, $startDate = null)
    {
        $position = Position::findOrFail($positionId);
        $startDate = $startDate ? Carbon::parse($startDate) : now();

        \DB::transaction(function () use ($user, $position, $startDate) {
            if ($position->is_unique) {
                // Find current active holder of this position
                $currentHolders = UserPosition::where('position_id', $position->id)
                    ->where('is_current', true)
                    ->get();

                foreach ($currentHolders as $holder) {
                    if ($holder->user_id !== $user->id) {
                        // Strict check for Super Admin
                        if ($position->code === 'super_admin') {
                            $holderUser = User::find($holder->user_id);
                            throw new \Exception("The Super Admin position is strictly limited to one user. It is currently held by {$holderUser->name}. Please remove them from this position before assigning it to a new user.");
                        }

                        // End their tenure
                        $holder->update([
                            'is_current' => false,
                            'end_date' => $startDate->copy()->subDay(),
                        ]);
                    }
                }
            }

            // Check if user already has this position active
            $existing = $user->positions()
                ->where('position_id', $position->id)
                ->wherePivot('is_current', true)
                ->first();

            if (!$existing) {
                 $user->positions()->attach($position->id, [
                    'appointment_date' => $startDate,
                    'is_current' => true,
                ]);
            }
        });
    }
}
