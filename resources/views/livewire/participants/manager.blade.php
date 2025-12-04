<?php

use App\Models\Participant;
use App\Models\User;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $participable;
    public $users;
    
    // Form
    public $user_id;
    public $name;
    public $email;
    public $phone;
    public $address;
    public $designation;
    public $organization;
    public $is_external = false;

    public function mount($participable)
    {
        $this->participable = $participable;
        $this->users = User::orderBy('name')->get();
    }

    public function save()
    {
        $this->validate([
            'is_external' => 'boolean',
            'user_id' => 'required_if:is_external,false',
            'name' => 'required_if:is_external,true',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        if ($this->is_external) {
            $participant = Participant::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'designation' => $this->designation,
                'organization' => $this->organization,
            ]);
            $this->participable->externalParticipants()->attach($participant, ['type' => 'attendee']);
        } else {
            $this->participable->users()->attach($this->user_id, ['type' => 'attendee']);
        }
        
        $this->reset(['user_id', 'name', 'email', 'phone', 'address', 'designation', 'organization', 'is_external']);
        $this->success('Participant added successfully.');
    }

    public function delete($id, $type)
    {
        if ($type === 'user') {
            $this->participable->users()->detach($id);
        } else {
            $this->participable->externalParticipants()->detach($id);
        }
        $this->success('Participant removed.');
    }
}; ?>

<div class="mt-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold">Participants</h3>
        <span class="badge badge-neutral">{{ $participable->participants->count() }}</span>
    </div>

    {{-- List --}}
    <div class="bg-base-200 rounded-xl p-4 mb-4 space-y-2">
        @forelse($participable->participants as $participant)
            @php
                $isUser = $participant instanceof \App\Models\User;
            @endphp
            <div class="flex justify-between items-center bg-base-100 p-3 rounded-lg shadow-sm">
                <div class="flex items-center gap-3">
                    <x-mary-avatar :title="$participant->name" class="!w-10 !h-10" />
                    <div>
                        <div class="font-bold">
                            {{ $participant->name }}
                            @if(!$isUser)
                                <span class="badge badge-xs badge-warning">External</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500">
                            @if($isUser)
                                {{ $participant->email }}
                            @else
                                {{ $participant->designation }} {{ $participant->organization ? 'at ' . $participant->organization : '' }}
                            @endif
                        </div>
                    </div>
                </div>
                
                @can('delete participants')
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="delete({{ $participant->id }}, '{{ $isUser ? 'user' : 'external' }}')" wire:confirm="Are you sure?" />
                @endcan
            </div>
        @empty
            <div class="text-center text-gray-500 italic py-2">No participants added yet.</div>
        @endforelse
    </div>

    {{-- Add Form --}}
    @can('create participants')
        <div class="bg-base-100 border border-base-300 rounded-xl p-4">
            <div class="font-bold mb-3 text-sm uppercase text-gray-500">Add Participant</div>
            
            <x-mary-form wire:submit="save">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-sm {{ !$is_external ? 'font-bold' : '' }}">Internal User</span>
                    <x-mary-toggle wire:model.live="is_external" />
                    <span class="text-sm {{ $is_external ? 'font-bold' : '' }}">External Guest</span>
                </div>

                @if(!$is_external)
                    <div class="grid grid-cols-1 gap-4">
                        <x-mary-select label="Select User" wire:model="user_id" :options="$users" option-label="name" option-value="id" placeholder="Search user..." searchable />
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="Name" wire:model="name" placeholder="Guest Name" />
                        <x-mary-input label="Email" wire:model="email" placeholder="guest@example.com" />
                        <x-mary-input label="Phone" wire:model="phone" placeholder="0300-1234567" />
                        <x-mary-input label="Address" wire:model="address" placeholder="House #, Street, City" />
                        <x-mary-input label="Designation" wire:model="designation" placeholder="e.g. Consultant" />
                        <x-mary-input label="Organization" wire:model="organization" placeholder="e.g. Acme Corp" />
                    </div>
                @endif

                <x-slot:actions>
                    <x-mary-button label="Add Participant" class="btn-primary btn-sm" type="submit" spinner="save" icon="o-plus" />
                </x-slot:actions>
            </x-mary-form>
        </div>
    @endcan
</div>
