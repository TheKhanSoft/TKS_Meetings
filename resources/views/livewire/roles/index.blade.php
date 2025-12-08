<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
        $this->authorize('viewAny', Role::class);

        // Load all permissions once
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
            ->orderBy('name')
            ->get();
    }

    public function updatedSearch() { $this->loadRoles(); }

    public function create()
    {
        $this->authorize('create', Role::class);

        $this->reset(['id', 'name', 'selectedPermissions', 'permissionSearch']);
        $this->resetValidation(); // Clear previous validation errors
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit(Role $role)
    {
        $this->authorize('update', $role);

        $this->id = $role->id;
        $this->name = $role->name;
        
        // CRITICAL FIX: Load existing permissions for this role
        $this->selectedPermissions = $role->permissions()->pluck('id')->toArray();
        
        $this->permissionSearch = '';
        $this->resetValidation();
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
        
        // Fetch Permission objects to ensure type safety with Spatie
        $permissions = Permission::whereIn('id', $validated['selectedPermissions'] ?? [])->get();

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
        // Get IDs of all permissions in this group
        $groupPermissions = $this->groupedPermissions[$group]->pluck('id')->toArray();
        
        if ($state) {
            // Merge and Unique
            $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $groupPermissions));
        } else {
            // Diff and Re-index
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $groupPermissions));
        }
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', Role::find($id));

        $this->roleToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $role = Role::find($this->roleToDeleteId);
        $this->authorize('delete', $role);
        
        // Security check
        if ($role->name === 'Super Admin') {
            $this->error('The Super Admin role cannot be deleted.');
            $this->showDeleteModal = false;
            return;
        }

        if ($role->users()->exists()) {
            $this->error('Cannot delete role because it is assigned to users.');
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
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'permissions_count', 'label' => 'Permissions', 'class' => 'hidden md:table-cell'], // Hide on mobile for space
                ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-1 text-end'],
            ]
        ];
    }

    // Computed property for grouping permissions efficiently
    public function getGroupedPermissionsProperty()
    {
        $permissions = $this->permissions;

        // Filter by search if active
        if ($this->permissionSearch) {
            $permissions = $permissions->filter(function($permission) {
                return stripos($permission->name, $this->permissionSearch) !== false;
            });
        }

        return $permissions->groupBy(function($permission) {
            // Assuming format "action entity" (e.g., "create users", "view dashboard")
            $parts = explode(' ', $permission->name);
            $entity = $parts[1] ?? 'Other';
            
            // Handle 3-word permissions like "view agenda items" -> Group "Agenda Items"
            if (count($parts) > 2) {
                $entity = $parts[1] . ' ' . $parts[2];
            }
            
            // Special grouping overrides
            if (Str::contains($permission->name, ['settings', 'maintenance', 'dashboard'])) {
                return 'System';
            }
            
            return Str::headline($entity); // Converts "agenda_items" or "agenda items" to "Agenda Items"
        })->sortKeys();
    }
}; ?>

<?php
// ... (Your PHP class code remains the same)
?>

<div>
    {{-- Header --}}
    <x-mary-header title="Roles & Permissions" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search roles..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            @can('create roles')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="Create Role" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- Roles Card Collection --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mt-5">
        @forelse($roles as $role)
            {{-- Custom Card for each Role --}}
            <x-mary-card 
                shadow 
                class="rounded-xl border border-base-300 transition-all duration-200 hover:shadow-lg hover:shadow-primary/20 cursor-pointer h-full flex flex-col justify-between"
                wire:click="edit({{ $role->id }})"
            >
                {{-- Role Header & Name --}}
                <div class="flex items-center justify-between mb-3 border-b border-base-200 pb-3">
                    <h2 class="text-xl font-bold text-primary truncate">{{ $role->name }}</h2>
                    <x-mary-badge :value="$role->permissions->count() . ' Perms'" class="badge-sm badge-neutral" />
                </div>
                
                {{-- Permissions Preview (Optional: Show up to 5 permissions) --}}
                <div class="flex-grow space-y-1 mb-4">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Key Permissions:</p>
                    @forelse($role->permissions->take(5) as $permission)
                        <div class="flex items-center text-sm">
                            <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success mr-2 flex-shrink-0" />
                            <span class="truncate text-gray-700">{{ Str::headline($permission->name) }}</span>
                        </div>
                    @empty
                        <span class="text-sm italic text-gray-500">No permissions assigned.</span>
                    @endforelse
                    
                    @if($role->permissions->count() > 5)
                        <p class="text-xs text-gray-500 pt-1">+{{ $role->permissions->count() - 5 }} more...</p>
                    @endif
                </div>

                {{-- Card Actions --}}
                <div class="card-actions justify-end pt-3 border-t border-base-200">
                    @can('edit roles')
                        <x-mary-button 
                            icon="o-pencil" 
                            label="Edit" 
                            wire:click.stop="edit({{ $role->id }})" 
                            spinner 
                            class="btn-sm btn-outline btn-primary"
                        />
                    @endcan
                    
                    @can('delete roles')
                        @if($role->name !== 'Super Admin')
                            <x-mary-button 
                                icon="o-trash" 
                                label="Delete" 
                                wire:click.stop="confirmDelete({{ $role->id }})" 
                                spinner 
                                class="btn-sm btn-outline btn-error"
                            />
                        @endif
                    @endcan
                </div>
            </x-mary-card>
        @empty
            {{-- Empty State --}}
            <div class="col-span-full text-center py-10">
                <p class="text-lg text-gray-500">No roles found matching "{{ $search }}".</p>
                <x-mary-button label="Create Role" icon="o-plus" class="btn-sm btn-link mt-2" wire:click="create" />
            </div>
        @endforelse
    </div>

    {{-- Create/Edit Modal (The modal UI remains great and is untouched) --}}
    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-6xl h-[90vh]">
        <x-mary-header 
            :title="$editMode ? 'Edit Role' : 'Create Role'"
            :subtitle="$editMode ? 'Update role details and permissions' : 'Define a new role'"
            separator
        />
        
        <x-mary-form wire:submit="save" class="h-full flex flex-col">
            {{-- Role Name --}}
            <div class="px-1">
                <x-mary-input label="Role Name" wire:model="name" placeholder="e.g. Branch Manager" icon="o-shield-check" />
            </div>
            
            {{-- Permissions Grid Header --}}
            <div class="mt-6 flex justify-between items-end mb-2 px-1">
                <div>
                    <span class="label-text font-bold text-lg">Permissions</span>
                    <div class="text-xs text-gray-500">Select permissions to assign to this role</div>
                </div>
                <x-mary-input wire:model.live.debounce="permissionSearch" placeholder="Filter permissions..." class="input-sm w-64" icon="o-magnifying-glass" />
            </div>
            
            {{-- Permissions Grid (Scrollable Area) --}}
            <div class="flex-1 overflow-y-auto border border-base-300 p-4 rounded-xl bg-base-200/30">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @forelse($this->groupedPermissions as $group => $permissions)
                        <div class="card bg-base-100 shadow-sm border border-base-200 h-fit break-inside-avoid">
                            <div class="card-body p-4">
                                {{-- Group Header --}}
                                <div class="flex justify-between items-center border-b border-base-200 pb-2 mb-2">
                                    <h3 class="font-bold text-primary uppercase text-xs tracking-wider">
                                        {{ $group }}
                                    </h3>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="toggleGroup('{{ $group }}', true)" class="text-[10px] font-bold text-primary hover:underline">ALL</button>
                                        <span class="text-base-300">|</span>
                                        <button type="button" wire:click="toggleGroup('{{ $group }}', false)" class="text-[10px] font-bold text-gray-400 hover:text-gray-600 hover:underline">NONE</button>
                                    </div>
                                </div>
                                
                                {{-- Checkboxes --}}
                                <div class="space-y-2">
                                    @foreach($permissions as $permission)
                                        <x-mary-checkbox 
                                            :label="ucfirst($permission->name)" 
                                            wire:model="selectedPermissions" 
                                            :value="$permission->id" 
                                            class="checkbox-sm checkbox-primary"
                                        />
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-10 text-gray-500">
                            No permissions found matching "{{ $permissionSearch }}"
                        </div>
                    @endforelse
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                <x-mary-button label="Save Changes" class="btn-primary" type="submit" spinner="save" icon="o-check" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- Delete Confirmation --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Role?" class="backdrop-blur">
        <div class="text-base mb-4">
            Are you sure you want to delete this role? Users assigned to this role will lose their access rights immediately.
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete Role" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>