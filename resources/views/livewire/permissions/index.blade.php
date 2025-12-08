<?php

use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Validation\Rule;

new class extends Component {
    use Toast;

    public $permissions;
    public string $search = '';
    public bool $drawer = false;
    
    // CRUD
    public bool $showModal = false;
    public bool $editMode = false;
    public bool $showDeleteModal = false;
    public $permissionToDeleteId;

    public $id;
    public $name;

    public function mount()
    {
        $this->authorize('viewAny', Permission::class);
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        $this->permissions = Permission::query()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();
    }

    public function updatedSearch() { $this->loadPermissions(); }

    public function create()
    {
        $this->authorize('create', Permission::class);
        $this->reset(['id', 'name']);
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit(Permission $permission)
    {
        $this->authorize('update', $permission);
        $this->id = $permission->id;
        $this->name = $permission->name;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($this->id)],
        ]);

        if ($this->editMode) {
            $permission = Permission::find($this->id);
            $permission->update($validated);
            $this->success('Permission updated successfully.');
        } else {
            Permission::create($validated);
            $this->success('Permission created successfully.');
        }

        $this->showModal = false;
        $this->loadPermissions();
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', Permission::find($id));
        $this->permissionToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $permission = Permission::find($this->permissionToDeleteId);
        $this->authorize('delete', $permission);
        $permission->delete();
        $this->showDeleteModal = false;
        $this->loadPermissions();
    }

    public function getGroupedPermissionsProperty()
    {
        return $this->permissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            $action = $parts[0];
            $entity = isset($parts[1]) ? $parts[1] : 'Other';
            
            if (count($parts) > 2) {
                $entity = $parts[1] . ' ' . $parts[2];
            }
            
            if (in_array($permission->name, ['view settings', 'edit settings', 'bypass maintenance', 'view dashboard'])) {
                return 'System';
            }
            
            return ucfirst(str_replace('_', ' ', $entity));
        })->sortKeys();
    }
}; ?>

<div>
    <x-mary-header title="Permissions" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            @can('create permissions')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="New Permission" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->groupedPermissions as $group => $permissions)
            <div class="bg-base-100 rounded-xl shadow-sm border border-base-200 overflow-hidden flex flex-col h-full">
                <div class="bg-base-200/50 px-4 py-3 border-b border-base-200 flex justify-between items-center">
                    <div class="font-bold text-primary uppercase tracking-wider text-sm">
                        {{ $group }}
                    </div>
                    <x-mary-badge :value="$permissions->count()" class="badge-ghost badge-sm" />
                </div>
                <div class="p-4 flex-1">
                    <div class="space-y-2">
                        @foreach($permissions as $permission)
                            <div class="flex items-center justify-between gap-2 text-sm group hover:bg-base-50 p-1 rounded transition-colors">
                                <div class="flex items-center gap-2">
                                    <x-mary-icon name="o-check-circle" class="w-4 h-4 text-success opacity-50 group-hover:opacity-100" />
                                    <span class="text-base-content/80">{{ ucfirst($permission->name) }}</span>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @can('edit permissions')
                                        <x-mary-button icon="o-pencil" wire:click="edit({{ $permission->id }})" class="btn-xs btn-ghost text-blue-500" />
                                    @endcan
                                    @can('delete permissions')
                                        <x-mary-button icon="o-trash" wire:click="confirmDelete({{ $permission->id }})" class="btn-xs btn-ghost text-red-500" />
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    @if($this->groupedPermissions->isEmpty())
        <div class="text-center py-12">
            <div class="bg-base-200 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                <x-mary-icon name="o-shield-exclamation" class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-lg font-bold text-gray-600">No permissions found</h3>
            <p class="text-gray-400">Try adjusting your search query.</p>
        </div>
    @endif

    <x-mary-modal wire:model="showModal" title="{{ $editMode ? 'Edit Permission' : 'Create Permission' }}" class="backdrop-blur-md">
        <div class="mb-4 text-sm text-warning flex gap-2 items-start">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 shrink-0" />
            <div>
                <strong>Warning:</strong> Permissions are often hardcoded in the application logic. 
                Renaming or creating permissions without updating the code may result in broken functionality.
            </div>
        </div>

        <x-mary-form wire:submit="save">
            <x-mary-input label="Permission Name" wire:model="name" placeholder="e.g. view reports" hint="Use lowercase and spaces" />
            
            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-mary-modal wire:model="showDeleteModal" title="Delete Permission" class="backdrop-blur-sm">
        <div class="text-center p-4">
            <div class="bg-red-50 text-red-500 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6" />
            </div>
            <div class="font-bold text-lg">Delete this permission?</div>
            <div class="text-gray-500 mt-1">This will remove it from all roles and users.</div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
