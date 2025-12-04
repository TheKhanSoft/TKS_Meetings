<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Validation\Rule;

new class extends Component {
    use Toast;

    public $roles;
    public $permissions;
    public string $search = '';
    public bool $showModal = false;
    public bool $editMode = false;

    public $id;
    public $name;
    public $selectedPermissions = [];
    public string $permissionSearch = '';

    // Delete Modal
    public bool $showDeleteModal = false;
    public $roleToDeleteId;

    public function mount()
    {
        if (!auth()->user()->can('view roles')) {
            $this->error('Unauthorized access. Redirecting to dashboard...');
            return $this->redirect(route('dashboard'), navigate: true);
        }

        $this->permissions = Permission::all();
        $this->loadRoles();
    }

    public function loadRoles()
    {
        $query = Role::query()->with('permissions');

        // Hide Super Admin role if current user is not Super Admin
        if (auth()->check() && !auth()->user()->hasRole('Super Admin')) {
            $query->where('name', '!=', 'Super Admin');
        }

        $this->roles = $query
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->get();
    }

    public function updatedSearch() { $this->loadRoles(); }

    public function create()
    {
        if (!auth()->user()->can('create roles')) {
            $this->error('You do not have permission to create roles.');
            return;
        }

        $this->reset(['id', 'name', 'selectedPermissions', 'permissionSearch']);
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit(Role $role)
    {
        if (!auth()->user()->can('edit roles')) {
            $this->error('You do not have permission to edit roles.');
            return;
        }

        // Prevent editing Super Admin if not Super Admin
        if ($role->name === 'Super Admin' && !auth()->user()->hasRole('Super Admin')) {
            $this->error('You cannot edit the Super Admin role.');
            return;
        }

        $this->id = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        $this->permissionSearch = '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($this->id)],
            'selectedPermissions' => 'array',
        ];

        $validated = $this->validate($rules);
        
        // Fix: Fetch Permission objects to avoid "PermissionDoesNotExist" error when passing IDs
        $permissions = Permission::whereIn('id', $validated['selectedPermissions'])->get();

        if ($this->editMode) {
            $role = Role::find($this->id);
            
            // Protect Super Admin Name
            if ($role->name === 'Super Admin' && $validated['name'] !== 'Super Admin') {
                $this->error('Cannot change the name of the Super Admin role.');
                return;
            }

            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($permissions);
            $this->success('Role updated successfully.');
        } else {
            $role = Role::create(['name' => $validated['name']]);
            $role->syncPermissions($permissions);
            $this->success('Role created successfully.');
        }

        $this->showModal = false;
        $this->loadRoles();
    }

    public function toggleGroup($group, $state)
    {
        $groupPermissions = $this->groupedPermissions[$group]->pluck('id')->toArray();
        
        if ($state) {
            $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $groupPermissions));
        } else {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $groupPermissions));
        }
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('delete roles')) {
            $this->error('You do not have permission to delete roles.');
            return;
        }

        $this->roleToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $role = Role::find($this->roleToDeleteId);
        
        if ($role->name === 'Super Admin') {
            $this->error('Cannot delete the Super Admin role.');
            $this->showDeleteModal = false;
            return;
        }

        if ($role->users()->exists()) {
            $this->error('Cannot delete role with assigned users.');
            $this->showDeleteModal = false;
            return;
        }

        $role->delete();
        $this->success('Role deleted successfully.');
        $this->showDeleteModal = false;
        $this->loadRoles();
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'permissions_count', 'label' => 'Permissions'],
            ]
        ];
    }

    public function getGroupedPermissionsProperty()
    {
        $permissions = $this->permissions;

        if ($this->permissionSearch) {
            $permissions = $permissions->filter(function($permission) {
                return stripos($permission->name, $this->permissionSearch) !== false;
            });
        }

        return $permissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            $action = $parts[0];
            $entity = isset($parts[1]) ? $parts[1] : 'Other';
            
            // Handle multi-word entities (e.g., "agenda items")
            if (count($parts) > 2) {
                $entity = $parts[1] . ' ' . $parts[2];
            }
            
            // Custom grouping for specific permissions
            if (in_array($permission->name, ['view settings', 'edit settings', 'bypass maintenance', 'view dashboard'])) {
                return 'System';
            }
            
            return ucfirst(str_replace('_', ' ', $entity));
        })->sortKeys();
    }
}; ?>

<div>
    <x-mary-header title="Roles" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            @can('create roles')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" tooltip="Create Role" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="rounded-2xl">
        <x-mary-table :headers="$headers" :rows="$roles" striped @row-click="$wire.edit($event.detail.row.id)">
            @scope('cell_permissions_count', $role)
                <x-mary-badge :value="$role->permissions->count()" class="badge-neutral" />
            @endscope
            @scope('actions', $role)
                <div class="flex gap-0">
                    @can('edit roles')
                        <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $role->id }})" spinner class="btn-sm btn-ghost text-blue-500 px-1" tooltip="Edit" />
                    @endcan
                    
                    @can('delete roles')
                        <x-mary-button icon="o-trash" wire:click.stop="confirmDelete({{ $role->id }})" spinner class="btn-sm btn-ghost text-red-500 px-1" tooltip="Delete" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-5xl">
        <x-mary-header 
            :title="$editMode ? 'Edit Role' : 'Create Role'"
            separator
        />
        
        <x-mary-form wire:submit="save">
            <x-mary-input label="Name" wire:model="name" placeholder="e.g. Manager" />
            
            <div class="mt-6">
                <div class="flex justify-between items-end mb-2">
                    <span class="label-text font-bold text-lg">Permissions</span>
                    <x-mary-input wire:model.live.debounce="permissionSearch" placeholder="Search permissions..." class="input-sm w-64" icon="o-magnifying-glass" />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-h-[60vh] overflow-y-auto border p-6 rounded-xl bg-base-200/30">
                    @foreach($this->groupedPermissions as $group => $permissions)
                        <div class="bg-base-100 p-4 rounded-lg shadow-sm border border-base-200 break-inside-avoid">
                            <div class="flex justify-between items-center mb-3 border-b border-base-200 pb-2">
                                <div class="font-bold text-primary uppercase tracking-wider text-xs">
                                    {{ $group }}
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="toggleGroup('{{ $group }}', true)" class="text-[10px] font-bold text-primary hover:underline">ALL</button>
                                    <button type="button" wire:click="toggleGroup('{{ $group }}', false)" class="text-[10px] font-bold text-gray-400 hover:text-gray-600 hover:underline">NONE</button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                @foreach($permissions as $permission)
                                    <x-mary-checkbox 
                                        :label="ucfirst($permission->name)" 
                                        wire:model="selectedPermissions" 
                                        :value="$permission->id" 
                                        class="checkbox-sm"
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="showDeleteModal" title="Delete Confirmation" class="backdrop-blur" box-class="bg-base-100 border-error border w-full max-w-md">
        <div class="text-base mb-4">
            Are you sure you want to delete this role? This action cannot be undone.
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
