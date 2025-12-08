<?php

use Livewire\Volt\Component;
use App\Models\MeetingType;
use App\Models\User;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public MeetingType $meetingType;
    public $users = [];
    public $selectedUser = null;
    public $permissions = [];
    public $availablePermissions = ['view', 'create', 'edit', 'delete', 'publish'];
    
    public $permissionDescriptions = [
        'view' => 'Can view meetings of this type',
        'create' => 'Can schedule new meetings',
        'edit' => 'Can modify existing meetings',
        'delete' => 'Can cancel/delete meetings',
        'publish' => 'Can publish agendas and minutes'
    ];

    public $permissionIcons = [
        'view' => 'o-eye',
        'create' => 'o-plus-circle',
        'edit' => 'o-pencil-square',
        'delete' => 'o-trash',
        'publish' => 'o-megaphone'
    ];

    public $showModal = false;
    
    public $stats = [
        'total' => 0,
        'full_access' => 0,
        'partial_access' => 0
    ];

    public $headers = [
        ['key' => 'name', 'label' => 'User'],
        ['key' => 'permissions', 'label' => 'Permissions', 'sortable' => false],
        ['key' => 'pivot.created_at', 'label' => 'Added On', 'class' => 'hidden md:table-cell'],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'class' => 'w-20'],
    ];

    // Search
    public $searchUser = '';
    public $foundUsers = [];

    public function mount(MeetingType $meetingType)
    {
        $this->meetingType = $meetingType;
        $this->loadUsers();
    }

    public function loadUsers()
    {
        // Explicitly select pivot columns to ensure they are loaded
        $this->users = $this->meetingType->users()
            ->withPivot(['permissions', 'created_at', 'updated_at'])
            ->get();
        
        $this->stats['total'] = $this->users->count();
        
        $availableCount = count($this->availablePermissions);
        
        $this->stats['full_access'] = $this->users->filter(function($user) use ($availableCount) {
            $pivot = $user->pivot ?? null;
            if (!$pivot) return false;
            
            $perms = $pivot->permissions;
            if (is_string($perms)) $perms = json_decode($perms, true);
            
            return count($perms ?? []) === $availableCount;
        })->count();

        $this->stats['partial_access'] = $this->stats['total'] - $this->stats['full_access'];
    }

    public function updatedShowModal($value)
    {
        if (!$value) {
            $this->loadUsers();
            $this->selectedUser = null;
            $this->permissions = [];
        }
    }

    public function edit($userId)
    {
        $this->selectedUser = $userId;
        // Fetch fresh user data with pivot
        $user = $this->meetingType->users()->where('user_id', $userId)->first();
        
        if ($user && $user->pivot) {
            $pivot = $user->pivot;
            // Handle both string (JSON) and array (casted) cases
            $permissions = $pivot->permissions;
            
            if (is_string($permissions)) {
                $decoded = json_decode($permissions, true);
                $this->permissions = is_array($decoded) ? $decoded : [];
            } elseif (is_array($permissions)) {
                $this->permissions = $permissions;
            } else {
                $this->permissions = [];
            }
            
            // Ensure we have a clean array of strings
            $this->permissions = array_values($this->permissions);
        } else {
            $this->permissions = [];
        }
        
        $this->showModal = true;
    }

    public function toggleAll()
    {
        if (count($this->permissions) === count($this->availablePermissions)) {
            $this->permissions = [];
        } else {
            $this->permissions = $this->availablePermissions;
        }
    }

    public function save()
    {
        if ($this->selectedUser) {
            $this->meetingType->users()->syncWithoutDetaching([
                $this->selectedUser => [
                    'permissions' => json_encode($this->permissions)
                ]
            ]);
            $this->success('Permissions updated');
        }
        
        $this->showModal = false;
        $this->loadUsers();
    }
    
    public function remove($userId)
    {
        $this->meetingType->users()->detach($userId);
        $this->success('User removed');
        $this->showModal = false;
        $this->loadUsers();
    }
    
    public function updatedSearchUser($value)
    {
        if(strlen($value) > 2) {
            // Exclude already added users
            $existingIds = $this->users->pluck('id')->toArray();
            
            $this->foundUsers = User::where(function($q) use ($value) {
                    $q->where('name', 'like', "%$value%")
                      ->orWhere('email', 'like', "%$value%");
                })
                ->whereNotIn('id', $existingIds)
                ->limit(5)
                ->get();
        } else {
            $this->foundUsers = [];
        }
    }
    
    public function addUser($userId)
    {
        $this->meetingType->users()->syncWithoutDetaching([
            $userId => ['permissions' => json_encode([])]
        ]);
        $this->loadUsers(); // Reload to get the new user in the list
        $this->edit($userId); // Open drawer for the new user
        $this->searchUser = '';
        $this->foundUsers = [];
    }
}; ?>

<div>
    <x-mary-header title="Permissions: {{ $meetingType->name }}" separator>
        <x-slot:actions>
            <x-mary-button label="Back" link="{{ route('meeting-types.index') }}" icon="o-arrow-left" class="btn-ghost" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <x-mary-stat title="Total Users" value="{{ $stats['total'] }}" icon="o-users" class="shadow-sm" />
        <x-mary-stat title="Full Access" value="{{ $stats['full_access'] }}" icon="o-shield-check" class="shadow-sm" />
        <x-mary-stat title="Partial Access" value="{{ $stats['partial_access'] }}" icon="o-shield-exclamation" class="shadow-sm" />
    </div>

    <div class="mb-6 relative">
        <div class="flex gap-2">
            <div class="flex-1 relative">
                <x-mary-input placeholder="Search user by name or email to add..." wire:model.live.debounce.300ms="searchUser" icon="o-user-plus" spinner />
                @if($searchUser)
                    <button wire:click="$set('searchUser', '')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                        <x-mary-icon name="o-x-mark" class="w-5 h-5" />
                    </button>
                @endif
            </div>
        </div>
        @if(count($foundUsers) > 0)
            <div class="bg-base-100 shadow-xl rounded-lg p-2 absolute z-50 w-full mt-1 border border-base-300 max-h-60 overflow-y-auto">
                @foreach($foundUsers as $user)
                    <div class="p-3 hover:bg-base-200 cursor-pointer rounded-lg flex justify-between items-center transition-colors" wire:click="addUser({{ $user->id }})">
                        <div class="flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-8">
                                    <span class="text-xs">{{ substr($user->name, 0, 2) }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="font-bold text-sm">{{ $user->name }}</div>
                                <div class="text-xs opacity-70">{{ $user->email }}</div>
                            </div>
                        </div>
                        <x-mary-button icon="o-plus" class="btn-xs btn-ghost" />
                    </div>
                @endforeach
            </div>
        @elseif(strlen($searchUser) > 2)
            <div class="bg-base-100 shadow-xl rounded-lg p-4 absolute z-50 w-full mt-1 border border-base-300 text-center opacity-70">
                No users found.
            </div>
        @endif
    </div>

    <x-mary-card shadow class="rounded-2xl">
        <x-mary-table :headers="$headers" :rows="$users">
            @scope('cell_name', $user)
                <div class="flex items-center gap-3">
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-full w-10">
                            <span class="text-xs">{{ substr($user->name, 0, 2) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="font-bold">{{ $user->name }}</div>
                        <div class="text-xs opacity-70">{{ $user->email }}</div>
                    </div>
                </div>
            @endscope
            @scope('cell_permissions', $user)
                @php
                    $pivot = $user->pivot ?? null;
                    $perms = $pivot && is_string($pivot->permissions) ? json_decode($pivot->permissions, true) : ($pivot->permissions ?? []);
                    $isFullAccess = count($perms ?? []) === count($availablePermissions ?? []);
                @endphp
                <div class="flex flex-wrap gap-1">
                    @if($isFullAccess)
                        <x-mary-badge value="Full Access" class="badge-success badge-sm" icon="o-check-badge" />
                    @else
                        @forelse($perms ?? [] as $perm)
                            <x-mary-badge :value="ucfirst($perm)" class="badge-primary badge-sm" />
                        @empty
                            <span class="text-xs opacity-50 italic">No permissions</span>
                        @endforelse
                    @endif
                </div>
            @endscope
            @scope('cell_pivot.created_at', $user)
                <div class="text-xs opacity-70 hidden md:block">
                    {{ optional($user->pivot)->created_at ? $user->pivot->created_at->format('M d, Y') : '-' }}
                </div>
            @endscope
            @scope('actions', $user)
                <div class="flex gap-1">
                    <x-mary-button icon="o-pencil" wire:click="edit({{ $user->id }})" spinner class="btn-sm btn-ghost text-blue-500" tooltip="Edit Permissions" />
                    <x-mary-button icon="o-trash" wire:click="remove({{ $user->id }})" wire:confirm="Are you sure you want to remove this user?" spinner class="btn-sm btn-ghost text-red-500" tooltip="Remove User" />
                </div>
            @endscope
            <x-slot:empty>
                <div class="flex flex-col items-center justify-center p-10 opacity-50">
                    <x-mary-icon name="o-users" class="w-12 h-12 mb-2" />
                    <div class="text-lg font-bold">No users assigned</div>
                    <div class="text-sm">Search above to add users to this meeting type.</div>
                </div>
            </x-slot:empty>
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showModal" title="Edit Permissions" class="backdrop-blur" box-class="w-11/12 max-w-2xl">
        @if($selectedUser)
            <div class="mb-6 flex items-center gap-3 p-4 bg-base-200 rounded-lg">
                <div class="avatar placeholder">
                    <div class="bg-neutral text-neutral-content rounded-full w-12">
                        <span class="text-xl">{{ substr($users->find($selectedUser)?->name, 0, 2) }}</span>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-lg">{{ $users->find($selectedUser)?->name }}</div>
                    <div class="text-sm opacity-70">{{ $users->find($selectedUser)?->email }}</div>
                </div>
            </div>
        @endif
        
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold opacity-70">Access Control</h3>
            <x-mary-button label="Toggle All" icon="o-check-circle" class="btn-xs btn-ghost" wire:click="toggleAll" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($availablePermissions as $perm)
                <div class="border border-base-300 rounded-xl p-4 hover:bg-base-200 transition-all duration-200 hover:shadow-md group">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-base-100 rounded-lg group-hover:bg-white transition-colors">
                                <x-mary-icon :name="$permissionIcons[$perm]" class="w-5 h-5 opacity-70" />
                            </div>
                            <div>
                                <div class="font-bold">{{ ucfirst($perm) }}</div>
                                <div class="text-xs opacity-60 mt-0.5 leading-tight">{{ $permissionDescriptions[$perm] ?? '' }}</div>
                            </div>
                        </div>
                        <x-mary-toggle wire:key="perm-{{ $perm }}" value="{{ $perm }}" wire:model="permissions" class="toggle-primary toggle-sm" />
                    </div>
                </div>
            @endforeach
        </div>
        <x-slot:actions>
            <div class="flex w-full justify-between">
                <x-mary-button label="Remove User" icon="o-trash" class="btn-error btn-ghost text-red-500" wire:click="remove({{ $selectedUser }})" wire:confirm="Remove this user completely?" />
                <div class="flex gap-2">
                    <x-mary-button label="Cancel" wire:click="$set('showModal', false)" />
                    <x-mary-button label="Save Changes" class="btn-primary" wire:click="save" spinner="save" />
                </div>
            </div>
        </x-slot:actions>
    </x-mary-modal>
</div>
