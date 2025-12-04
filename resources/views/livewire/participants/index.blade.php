<?php

use App\Models\Participant;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public bool $showModal = false;
    public bool $editMode = false;
    public bool $viewMode = false;

    // Form fields
    public $id;
    public $title;
    public $name;
    public $email;
    public $phone;
    public $address;
    public $designation;
    public $organization;

    public $titleOptions = [
        ['id' => 'Mr.', 'name' => 'Mr.'],
        ['id' => 'Ms.', 'name' => 'Ms.'],
        ['id' => 'Dr.', 'name' => 'Dr.'],
        ['id' => 'Prof.', 'name' => 'Prof.'],
        ['id' => 'Prof. Dr.', 'name' => 'Prof. Dr.'],
        ['id' => 'Syed', 'name' => 'Syed'],
        ['id' => 'Pir', 'name' => 'Pir'],
        ['id' => 'Engr.', 'name' => 'Engr.'],
        ['id' => 'Justice (R)', 'name' => 'Justice (R)'],
    ];

    public function rules()
    {
        return [
            'title' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'designation' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
        ];
    }

    public function with(): array
    {
        $query = Participant::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('organization', 'like', '%' . $this->search . '%')
                  ->orWhere('designation', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'participants' => $query->orderBy('name')->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'contact', 'label' => 'Contact Info'],
                ['key' => 'organization', 'label' => 'Organization'],
                ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
            ]
        ];
    }

    public function create()
    {
        $this->reset(['id', 'title', 'name', 'email', 'phone', 'address', 'designation', 'organization']);
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function edit(Participant $participant)
    {
        $this->fillForm($participant);
        $this->editMode = true;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function view(Participant $participant)
    {
        $this->fillForm($participant);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function fillForm(Participant $participant)
    {
        $this->id = $participant->id;
        $this->title = $participant->title;
        $this->name = $participant->name;
        $this->email = $participant->email;
        $this->phone = $participant->phone;
        $this->address = $participant->address;
        $this->designation = $participant->designation;
        $this->organization = $participant->organization;
    }

    public function save()
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $validated = $this->validate();

        if ($this->editMode) {
            $participant = Participant::find($this->id);
            $participant->update($validated);
            $this->success('Participant updated successfully.');
        } else {
            Participant::create($validated);
            $this->success('Participant created successfully.');
        }

        $this->showModal = false;
    }

    public function delete(Participant $participant)
    {
        $participant->delete();
        $this->success('Participant deleted successfully.');
    }
}; ?>

<div>
    <x-mary-header title="External Participants" subtitle="Manage external guests and attendees" separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="Add New" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="rounded-2xl">
        <x-mary-table :headers="$headers" :rows="$participants" striped @row-click="$wire.view($event.detail.row.id)">
            @scope('cell_name', $participant)
                <div class="font-bold">
                    @if($participant->title)
                        <span class="text-gray-500 mr-1">{{ $participant->title }}</span>
                    @endif
                    {{ $participant->name }}
                </div>
                <div class="text-xs text-gray-500">{{ $participant->designation }}</div>
            @endscope

            @scope('cell_contact', $participant)
                <div class="text-sm">{{ $participant->email }}</div>
                <div class="text-xs text-gray-400">{{ $participant->phone }}</div>
            @endscope

            @scope('actions', $participant)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-eye" wire:click.stop="view({{ $participant->id }})" class="btn-ghost btn-sm" />
                    <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $participant->id }})" class="btn-ghost btn-sm text-blue-500" />
                    <x-mary-button icon="o-trash" wire:click.stop="delete({{ $participant->id }})" class="btn-ghost btn-sm text-error" />
                </div>
            @endscope
        </x-mary-table>
        
        <div class="mt-4">
            {{ $participants->links() }}
        </div>
    </x-mary-card>

    <x-mary-modal wire:model="showModal" title="{{ $viewMode ? 'Participant Details' : ($editMode ? 'Edit Participant' : 'New Participant') }}">
        @if($viewMode)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Name</div>
                    <div class="font-bold text-lg">{{ $title }} {{ $name }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Organization</div>
                    <div>{{ $organization ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Designation</div>
                    <div>{{ $designation ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Email</div>
                    <div>{{ $email ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold text-gray-500 uppercase">Phone</div>
                    <div>{{ $phone ?? '-' }}</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-xs font-bold text-gray-500 uppercase">Address</div>
                    <div>{{ $address ?? '-' }}</div>
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.showModal = false" />
                <x-mary-button label="Edit" icon="o-pencil" class="btn-primary" wire:click="edit({{ $id }})" />
            </x-slot:actions>
        @else
            <x-mary-form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-1">
                        <x-mary-select label="Title" wire:model="title" :options="$titleOptions" option-label="name" option-value="id" placeholder="Select" />
                    </div>
                    <div class="md:col-span-3">
                        <x-mary-input label="Name" wire:model="name" />
                    </div>
                </div>
                <x-mary-input label="Email" wire:model="email" />
                <x-mary-input label="Phone" wire:model="phone" />
                <x-mary-input label="Organization" wire:model="organization" />
                <x-mary-input label="Designation" wire:model="designation" />
                <x-mary-textarea label="Address" wire:model="address" />
                
                <x-slot:actions>
                    <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                    <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        @endif
    </x-mary-modal>
</div>
