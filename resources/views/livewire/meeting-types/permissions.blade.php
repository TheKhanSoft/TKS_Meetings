<?php

use Livewire\Volt\Component;
use App\Models\MeetingType;
use App\Models\User;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public MeetingType $meetingType;
    public $users = [];
    
    // UI State
    public $search = '';
    public $showModal = false;
    public $selectedUser = null;
    
    // Add User Search
    public $searchUserToAdd = '';
    public $foundUsersToAdd = [];

    // Permissions
    public $permissions = [];
    public $availablePermissions = ['view', 'create', 'edit', 'delete', 'publish'];
    
    public $permissionDescriptions = [
        'view' => 'Can view details and agendas',
        'create' => 'Can schedule new meetings',
        'edit' => 'Can update agenda items',
        'delete' => 'Can cancel meetings',
        'publish' => 'Can publish official minutes'
    ];

    public $permissionIcons = [
        'view' => 'o-eye',
        'create' => 'o-plus-circle',
        'edit' => 'o-pencil-square',
        'delete' => 'o-trash',
        'publish' => 'o-megaphone'
    ];

    public function mount(MeetingType $meetingType)
    {
        $this->authorize('managePermissions', $meetingType);
        $this->meetingType = $meetingType;
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $query = $this->meetingType->users()->withPivot(['permissions', 'created_at']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        $this->users = $query->get();
    }

    public function updatedSearchUserToAdd($value)
    {
        if(strlen($value) > 2) {
            $existingIds = $this->meetingType->users()->pluck('users.id')->toArray();
            
            $this->foundUsersToAdd = User::where(function($q) use ($value) {
                    $q->where('name', 'like', "%$value%")
                      ->orWhere('email', 'like', "%$value%");
                })
                ->whereNotIn('id', $existingIds)
                ->limit(5)
                ->get();
        } else {
            $this->foundUsersToAdd = [];
        }
    }

    public function updatedSearch() 
    {
        $this->loadUsers();
    }

    public function addUser($userId)
    {
        $this->meetingType->users()->syncWithoutDetaching([
            $userId => ['permissions' => json_encode(['view'])] 
        ]);
        
        $this->searchUserToAdd = '';
        $this->foundUsersToAdd = [];
        $this->loadUsers();
        $this->edit($userId); 
        $this->success('User added. Please configure permissions.');
    }

    public function edit($userId)
    {
        $this->selectedUser = $userId;
        
        // Always fetch fresh permission data from DB to ensure accuracy
        // This prevents the "stale data" issue when re-opening the modal
        $user = $this->meetingType->users()
                    ->withPivot('permissions')
                    ->where('user_id', $userId)
                    ->first();

        if ($user && $user->pivot) {
            $perms = $user->pivot->permissions;
            // Handle JSON string or Array cast
            $this->permissions = is_string($perms) ? json_decode($perms, true) : ($perms ?? []);
            // Force to array
            $this->permissions = array_values(is_array($this->permissions) ? $this->permissions : []);
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
            $this->success('Permissions updated successfully');
            $this->loadUsers(); // Refresh grid ONLY on save
        }
        
        $this->showModal = false;
    }
    
    public function remove($userId)
    {
        $this->meetingType->users()->detach($userId);
        $this->success('User access revoked');
        $this->showModal = false;
        $this->loadUsers(); // Refresh grid on remove
    }

    public function getStatsProperty() 
    {
        if (!$this->users) return ['total' => 0, 'full_access' => 0, 'partial' => 0];

        $total = $this->users->count();
        $full = 0;
        
        foreach($this->users as $u) {
            $pivot = $u->pivot ?? null;
            if (!$pivot) continue;

            $p = $pivot->permissions;
            $p = is_string($p) ? json_decode($p, true) : $p;
            
            if(count($p ?? []) === count($this->availablePermissions)) $full++;
        }

        return [
            'total' => $total,
            'full_access' => $full,
            'partial' => $total - $full
        ];
    }
}; ?>

<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-base-200 pb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('meeting-types.index') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                    <x-mary-icon name="o-arrow-left" class="w-3 h-3" /> Types
                </a>
                <span>/</span>
                <span>Configuration</span>
            </div>
            <h1 class="text-3xl font-bold text-base-content">{{ $meetingType->name }}</h1>
            <p class="text-base-content/60">Manage user access control lists for this meeting type.</p>
        </div>
        
        {{-- Search Add --}}
        <div class="relative w-full md:w-80">
            <x-mary-input 
                placeholder="Add new user..." 
                icon="o-user-plus" 
                wire:model.live.debounce.300ms="searchUserToAdd"
                class="input-primary bg-base-100"
            />
            
            @if(!empty($foundUsersToAdd))
                <div class="absolute z-50 top-full left-0 right-0 mt-2 bg-base-100 rounded-xl shadow-xl border border-base-200 overflow-hidden">
                    <div class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider bg-base-200/50">Found Users</div>
                    @foreach($foundUsersToAdd as $user)
                        <div wire:click="addUser({{ $user->id }})" class="px-4 py-3 hover:bg-primary/10 cursor-pointer flex items-center justify-between transition-colors group">
                            <div class="flex items-center gap-3">
                                {{-- Small Avatar in Dropdown --}}
                                <div class="avatar {{ $user->avatar ? '' : 'placeholder' }}">
                                    @if($user->avatar)
                                        <div class="w-8 rounded-full">
                                            <img src="{{ $user->avatar }}" />
                                        </div>
                                    @else
                                        <div class="bg-neutral text-neutral-content rounded-full w-8 content-center grid place-items-center">
                                            <span class="text-xs">{{ substr($user->name, 0, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-semibold text-sm">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                            <x-mary-icon name="o-plus" class="w-4 h-4 text-primary opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="stat bg-base-100 shadow-sm border border-base-200 rounded-xl">
            <div class="stat-figure text-primary"><x-mary-icon name="o-users" class="w-8 h-8 opacity-20" /></div>
            <div class="stat-title">Total Users</div>
            <div class="stat-value text-primary">{{ $this->stats['total'] }}</div>
        </div>
        <div class="stat bg-base-100 shadow-sm border border-base-200 rounded-xl">
            <div class="stat-figure text-success"><x-mary-icon name="o-shield-check" class="w-8 h-8 opacity-20" /></div>
            <div class="stat-title">Full Access</div>
            <div class="stat-value text-success">{{ $this->stats['full_access'] }}</div>
        </div>
        <div class="stat bg-base-100 shadow-sm border border-base-200 rounded-xl">
            <div class="stat-figure text-warning"><x-mary-icon name="o-adjustments-horizontal" class="w-8 h-8 opacity-20" /></div>
            <div class="stat-title">Partial Access</div>
            <div class="stat-value text-warning">{{ $this->stats['partial'] }}</div>
        </div>
    </div>

    {{-- Users Grid --}}
    <div>
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-lg">Assigned Users</h2>
            <x-mary-input icon="o-magnifying-glass" placeholder="Filter users..." wire:model.live.debounce="search" class="input-sm w-64" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($users as $user)
                @php
                    $pivot = $user->pivot ?? null;
                    $perms = ($pivot && is_string($pivot->permissions)) ? json_decode($pivot->permissions, true) : ($pivot->permissions ?? []);
                    $isFullAccess = count($perms ?? []) === count($availablePermissions);
                @endphp

                <div wire:key="user-{{ $user->id }}" class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-all duration-200 group">
                    <div class="card-body p-5">
                        {{-- Top: User Info --}}
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-3">
                                {{-- 
                                    AVATAR FIX:
                                    1. Checks for $user->avatar (URL)
                                    2. Fallback to Initials
                                    3. "grid place-items-center" ensures text is perfectly centered
                                --}}
                                <div class="avatar {{ $user->avatar ? '' : 'placeholder' }}">
                                    @if($user->avatar)
                                        <div class="w-10 rounded-full">
                                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" />
                                        </div>
                                    @else
                                        <div class="bg-neutral text-neutral-content rounded-full w-10 content-center grid place-items-center">
                                            <span class="text-xs font-bold">{{ substr($user->name, 0, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="min-w-0">
                                    <h3 class="font-bold text-base-content truncate" title="{{ $user->name }}">{{ $user->name }}</h3>
                                    <p class="text-xs text-gray-500 truncate" title="{{ $user->email }}">{{ $user->email }}</p>
                                </div>
                            </div>
                            
                            <x-mary-button 
                                icon="o-pencil" 
                                class="btn-ghost btn-sm btn-circle opacity-0 group-hover:opacity-100 transition-opacity" 
                                wire:click="edit({{ $user->id }})" 
                            />
                        </div>

                        {{-- Middle: Permissions Visualization --}}
                        <div class="min-h-[40px]">
                            @if($isFullAccess)
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-success/10 text-success rounded-full text-xs font-bold border border-success/20">
                                    <x-mary-icon name="o-shield-check" class="w-3.5 h-3.5" />
                                    Full Access
                                </div>
                            @elseif(empty($perms))
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-base-200 text-gray-500 rounded-full text-xs font-bold">
                                    <x-mary-icon name="o-no-symbol" class="w-3.5 h-3.5" />
                                    No Access
                                </div>
                            @else
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($perms as $perm)
                                        <div class="p-1 rounded bg-primary/5 text-primary border border-primary/10" title="{{ ucfirst($perm) }}">
                                            <x-mary-icon :name="$permissionIcons[$perm] ?? 'o-check'" class="w-3.5 h-3.5" />
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Bottom: Meta --}}
                        <div class="mt-4 pt-3 border-t border-base-200 flex justify-between items-center text-[10px] text-gray-400">
                            <span>Added: {{ optional($pivot)->created_at ? $pivot->created_at->format('M d, Y') : 'N/A' }}</span>
                            @if(!$isFullAccess)
                                <span>{{ count($perms ?? []) }} Perms</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-base-100 rounded-xl border border-dashed border-base-300">
                    <div class="bg-base-200 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                        <x-mary-icon name="o-user-group" class="w-6 h-6 text-gray-400" />
                    </div>
                    <h3 class="font-bold">No users assigned</h3>
                    <p class="text-sm text-gray-500">Use the search bar above to grant access to this meeting type.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Edit Permissions Modal --}}
    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-lg">
        @if($selectedUser)
            @php 
                $u = \App\Models\User::find($selectedUser); 
            @endphp
            
            @if($u)
                <x-mary-header :title="$u->name" :subtitle="$u->email" separator>
                    {{-- Modal Header Avatar --}}
                    <x-slot:middle>
                        <div class="avatar {{ $u->avatar ? '' : 'placeholder' }}">
                            @if($u->avatar)
                                <div class="w-10 rounded-full">
                                    <img src="{{ $u->avatar }}" />
                                </div>
                            @else
                                <div class="bg-primary text-primary-content rounded-full w-10 content-center grid place-items-center">
                                    <span class="text-sm font-bold">{{ substr($u->name, 0, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </x-slot:middle>
                    <x-slot:actions>
                        <x-mary-button 
                            label="Toggle All" 
                            class="btn-xs btn-ghost" 
                            icon="o-adjustments-horizontal" 
                            wire:click="toggleAll" 
                        />
                    </x-slot:actions>
                </x-mary-header>

                <div class="grid gap-3 py-2">
                    @foreach($availablePermissions as $perm)
                        <label class="flex items-center justify-between p-3 rounded-xl border border-base-200 cursor-pointer hover:bg-base-100 transition-colors {{ in_array($perm, $permissions) ? 'bg-primary/5 border-primary/30' : '' }}">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg {{ in_array($perm, $permissions) ? 'bg-primary text-primary-content' : 'bg-base-200 text-gray-500' }}">
                                    <x-mary-icon :name="$permissionIcons[$perm]" class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="font-bold text-sm capitalize">{{ $perm }}</div>
                                    <div class="text-xs opacity-60">{{ $permissionDescriptions[$perm] }}</div>
                                </div>
                            </div>
                            <input type="checkbox" value="{{ $perm }}" wire:model="permissions" class="checkbox checkbox-primary checkbox-sm" />
                        </label>
                    @endforeach
                </div>

                <x-slot:actions>
                    <div class="flex w-full justify-between items-center">
                        <x-mary-button 
                            label="Revoke Access" 
                            class="btn-ghost text-error btn-sm" 
                            wire:click="remove({{ $selectedUser }})" 
                            wire:confirm="This will remove the user from this meeting type completely. Continue?" 
                        />
                        <div class="flex gap-2">
                            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                            <x-mary-button label="Save Changes" class="btn-primary" wire:click="save" spinner="save" />
                        </div>
                    </div>
                </x-slot:actions>
            @endif
        @endif
    </x-mary-modal>
</div>