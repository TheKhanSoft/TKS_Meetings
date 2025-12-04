<?php

use App\Models\EmploymentStatus;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $statuses;
    public bool $showModal = false;
    public bool $editMode = false;

    public $id;
    public $name;
    public $code;
    public $description;

    public function mount()
    {
        if (!auth()->user()->can('view employment statuses')) {
            $this->error('Unauthorized access to employment statuses.');
            return $this->redirect(route('dashboard'), navigate: true);
        }
        $this->loadStatuses();
    }

    public function loadStatuses()
    {
        $this->statuses = EmploymentStatus::all();
    }

    public function create()
    {
        if (!auth()->user()->can('create employment statuses')) {
            abort(403);
        }
        $this->reset(['id', 'name', 'code', 'description']);
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit(EmploymentStatus $status)
    {
        if (!auth()->user()->can('edit employment statuses')) {
            abort(403);
        }
        $this->id = $status->id;
        $this->name = $status->name;
        $this->code = $status->code;
        $this->description = $status->description;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:employment_statuses,code,' . $this->id,
            'description' => 'nullable|string',
        ];

        $validated = $this->validate($rules);

        if ($this->editMode) {
            $status = EmploymentStatus::find($this->id);
            $status->update($validated);
            $this->success('Status updated successfully.');
        } else {
            EmploymentStatus::create($validated);
            $this->success('Status created successfully.');
        }

        $this->showModal = false;
        $this->loadStatuses();
    }

    public function delete($id)
    {
        if (!auth()->user()->can('delete employment statuses')) {
            abort(403);
        }
        EmploymentStatus::find($id)->delete();
        $this->success('Status deleted successfully.');
        $this->loadStatuses();
    }
    
    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'description', 'label' => 'Description'],
            ]
        ];
    }
}; ?>

<div class="p-4 md:p-8 max-w-7xl mx-auto">
    <x-mary-header title="Employment Statuses" subtitle="Manage user employment statuses." separator>
        <x-slot:actions>
            @can('create employment statuses')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="New Status" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="rounded-2xl bg-base-100">
        <x-mary-table :headers="$headers" :rows="$statuses" striped @row-click="$wire.edit($event.detail.row.id)" class="hover-row-cursor">
            @scope('actions', $status)
                @can('delete employment statuses')
                    <x-mary-button icon="o-trash" wire:click.stop="delete({{ $status->id }})" class="btn-sm btn-ghost text-error" />
                @endcan
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showModal" class="backdrop-blur-md">
        <x-mary-header :title="$editMode ? 'Edit Status' : 'Create Status'" separator />
        
        <x-mary-form wire:submit="save">
            <x-mary-input label="Name" wire:model="name" placeholder="Working" />
            <x-mary-input label="Code" wire:model="code" placeholder="working" hint="Unique identifier" />
            <x-mary-textarea label="Description" wire:model="description" placeholder="Optional description" />

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
