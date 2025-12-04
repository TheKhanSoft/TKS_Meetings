<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\Position;
use App\Models\UserPosition;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Validation\Rule;

new class extends Component {
    use Toast;

    public User $user;
    public $positions;
    public $userPositions;

    // Modal State
    public bool $showModal = false;
    public bool $editMode = false;
    public $userPositionId; // ID of the pivot record (UserPosition model)

    // Form Fields
    public $position_id;
    public $position_type = 'permanent';
    public $appointment_date;
    public $end_date;
    public $is_current = true;
    public $is_ongoing = true;

    public function mount(User $user)
    {
        if (!auth()->user()->can('assign positions')) {
            $this->error('Unauthorized access. Redirecting to dashboard...');
            return $this->redirect(route('dashboard'), navigate: true);
        }

        $this->user = $user;
        $this->positions = Position::all();
        $this->loadUserPositions();
    }

    public function loadUserPositions()
    {
        // We can use the UserPosition model directly to get the pivot ID easily
        $this->userPositions = UserPosition::with('position')
            ->where('user_id', $this->user->id)
            ->orderBy('appointment_date', 'desc')
            ->get();
    }

    public function create()
    {
        $this->reset(['userPositionId', 'position_id', 'position_type', 'appointment_date', 'end_date']);
        $this->is_current = true;
        $this->is_ongoing = true;
        $this->position_type = 'permanent';
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $userPosition = UserPosition::find($id);
        $this->userPositionId = $userPosition->id;
        $this->position_id = $userPosition->position_id;
        $this->position_type = $userPosition->position_type;
        $this->appointment_date = $userPosition->appointment_date ? $userPosition->appointment_date->format('Y-m-d') : null;
        $this->end_date = $userPosition->end_date ? $userPosition->end_date->format('Y-m-d') : null;
        $this->is_current = $userPosition->is_current;
        $this->is_ongoing = is_null($userPosition->end_date);
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        if (!auth()->user()->can('assign positions')) {
            abort(403);
        }

        $validated = $this->validate([
            'position_id' => 'required|exists:positions,id',
            'position_type' => 'required|string|in:permanent,additional,acting,temporary',
            'appointment_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:appointment_date',
            'is_current' => 'boolean',
        ]);

        if ($this->is_ongoing) {
            $validated['end_date'] = null;
        }

        if ($this->editMode) {
            $userPosition = UserPosition::find($this->userPositionId);
            $userPosition->update($validated);

            // Assign Role if linked
            $position = Position::find($validated['position_id']);
            if ($position && $position->role_id) {
                $this->user->assignRole($position->role_id);
            }

            $this->success('Position assignment updated successfully.');
        } else {
            $validated['user_id'] = $this->user->id;
            UserPosition::create($validated);

            // Assign Role if linked
            $position = Position::find($validated['position_id']);
            if ($position && $position->role_id) {
                $this->user->assignRole($position->role_id);
            }

            $this->success('Position assigned successfully.');
        }

        $this->showModal = false;
        $this->loadUserPositions();
    }

    public function delete($id)
    {
        if (!auth()->user()->can('assign positions')) {
            abort(403);
        }

        UserPosition::find($id)->delete();
        $this->success('Position assignment removed successfully.');
        $this->loadUserPositions();
    }

    public function with(): array
    {
        return [
            'types' => [
                ['id' => 'permanent', 'name' => 'Permanent'],
                ['id' => 'additional', 'name' => 'Additional'],
                ['id' => 'acting', 'name' => 'Acting'],
                ['id' => 'temporary', 'name' => 'Temporary'],
            ]
        ];
    }
}; ?>

<div>
    <x-mary-header :title="$user->name . ' - Position History'" separator>
        <x-slot:actions>
            <x-mary-button label="Back to Users" icon="o-arrow-left" link="{{ route('users.index') }}" class="btn-ghost" />
            <x-mary-button label="Assign Position" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid gap-4">
        {{-- Current Positions Card --}}
        <x-mary-card title="Current Positions" shadow class="bg-base-100">
            @php
                $currentPositions = $userPositions->where('is_current', true);
            @endphp
            
            @if($currentPositions->isEmpty())
                <div class="text-gray-500 italic">No active positions.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Type</th>
                                <th>Appointment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currentPositions as $pos)
                                <tr>
                                    <td class="font-bold">{{ $pos->position->name }}</td>
                                    <td><x-mary-badge :value="ucfirst($pos->position_type)" class="badge-info" /></td>
                                    <td>{{ $pos->appointment_date ? $pos->appointment_date->format('M d, Y') : '-' }}</td>
                                    <td>
                                        <x-mary-button icon="o-pencil" wire:click="edit({{ $pos->id }})" class="btn-sm btn-ghost text-blue-500" />
                                        <x-mary-button icon="o-trash" wire:click="delete({{ $pos->id }})" wire:confirm="Are you sure?" class="btn-sm btn-ghost text-red-500" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-mary-card>

        {{-- Past Positions Card --}}
        <x-mary-card title="Past Positions" shadow class="bg-base-100">
            @php
                $pastPositions = $userPositions->where('is_current', false);
            @endphp

            @if($pastPositions->isEmpty())
                <div class="text-gray-500 italic">No past positions.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pastPositions as $pos)
                                <tr>
                                    <td>{{ $pos->position->name }}</td>
                                    <td><x-mary-badge :value="ucfirst($pos->position_type)" class="badge-ghost" /></td>
                                    <td>
                                        {{ $pos->appointment_date ? $pos->appointment_date->format('M d, Y') : '?' }} 
                                        - 
                                        {{ $pos->end_date ? $pos->end_date->format('M d, Y') : 'Present' }}
                                    </td>
                                    <td>
                                        <x-mary-button icon="o-pencil" wire:click="edit({{ $pos->id }})" class="btn-sm btn-ghost text-blue-500" />
                                        <x-mary-button icon="o-trash" wire:click="delete({{ $pos->id }})" wire:confirm="Are you sure?" class="btn-sm btn-ghost text-red-500" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-mary-card>
    </div>

    {{-- Assignment Modal --}}
    <x-mary-modal wire:model="showModal" :title="$editMode ? 'Edit Assignment' : 'Assign Position'" class="backdrop-blur">
        <x-mary-form wire:submit="save">
            <x-mary-select label="Position" wire:model="position_id" :options="$positions" option-label="name" option-value="id" placeholder="Select Position" />
            
            <x-mary-select label="Assignment Type" wire:model="position_type" :options="$types" option-label="name" option-value="id" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-mary-datetime label="Appointment Date" wire:model="appointment_date" />
                @if(!$is_ongoing)
                    <x-mary-datetime label="End Date" wire:model="end_date" />
                @endif
            </div>

            <div class="flex gap-4">
                <x-mary-checkbox label="Ongoing (No End Date)" wire:model.live="is_ongoing" />
                <x-mary-checkbox label="Is Current Position?" wire:model="is_current" />
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
