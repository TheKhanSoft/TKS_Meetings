<?php

use Livewire\Volt\Component;
use App\Models\Keyword;
use Mary\Traits\Toast;
use Livewire\WithPagination;

new class extends Component {
    use Toast, WithPagination;

    public $search = '';
    public $modal = false;
    public $editing = null;
    public $name = '';

    // Sort by usage count (descending) so 'hot' keywords appear first
    public $sortBy = ['column' => 'usage_count', 'direction' => 'desc']; 

    public function keywords()
    {
        return Keyword::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount(['agendaItems', 'minutes'])
            ->orderBy('name', 'asc')
            ->paginate(20);
    }

    public function create()
    {
        $this->reset(['name', 'editing']);
        $this->modal = true;
    }

    public function edit(Keyword $keyword)
    {
        $this->editing = $keyword;
        $this->name = $keyword->name;
        $this->modal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:keywords,name,' . ($this->editing->id ?? 'NULL'),
        ]);

        if ($this->editing) {
            $this->editing->update(['name' => $this->name]);
            $this->success('Keyword updated.');
        } else {
            Keyword::create(['name' => $this->name]);
            $this->success('Keyword created.');
        }

        $this->modal = false;
    }

    public function delete(Keyword $keyword)
    {
        $keyword->delete();
        $this->success('Keyword deleted.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-end md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Keywords Library</h1>
            <p class="text-sm text-gray-500">Manage tags and their usage across agendas</p>
        </div>
        <div class="flex gap-2 w-full md:w-auto">
            <x-mary-input 
                icon="o-magnifying-glass" 
                placeholder="Search..." 
                wire:model.live.debounce="search" 
                class="w-full md:w-64"
            />
            <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="Add New" responsive />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        
        @forelse($this->keywords() as $keyword)
            <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow group">
                <div class="card-body px-4 py-3">
                    
                    <div class="flex justify-between items-start h-14">
                        <div class="flex items-start gap-2 w-full">
                            <div class="w-1.5 h-8 bg-primary rounded-full shrink-0 mt-1"></div> 
                            <h2 class="card-title text-lg font-bold text-base-content line-clamp-2 leading-tight" title="{{ $keyword->name }}">
                                {{ $keyword->name }}
                            </h2>
                        </div>
                        
                        <x-mary-dropdown class="btn-sm btn-circle btn-ghost -mr-2 -mt-2" no-x-anchor left>
                            <x-slot:trigger>
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5 text-gray-400 group-hover:text-gray-600" />
                            </x-slot:trigger>
                            <x-mary-menu-item title="Edit" icon="o-pencil" wire:click="edit({{ $keyword->id }})" />
                            <x-mary-menu-item title="Delete" icon="o-trash" class="text-error" wire:click="delete({{ $keyword->id }})" wire:confirm="Delete this keyword?" />
                        </x-mary-dropdown>
                    </div>

                    <div class="divider my-0"></div>

                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="flex flex-col items-center bg-base-200/50 p-2 rounded-lg" title="Overall Usage">
                            <x-mary-icon name="o-chart-bar" class="w-4 h-4 text-primary mb-1" />
                            <span class="font-bold">{{ $keyword->agenda_items_count + $keyword->minutes_count }}</span>
                        </div>
                        <div class="flex flex-col items-center bg-base-200/50 p-2 rounded-lg" title="Agenda Items">
                            <x-mary-icon name="o-calendar" class="w-4 h-4 text-secondary mb-1" />
                            <span class="font-bold">{{ $keyword->agenda_items_count }}</span>
                        </div>
                        <div class="flex flex-col items-center bg-base-200/50 p-2 rounded-lg" title="Minutes">
                            <x-mary-icon name="o-clock" class="w-4 h-4 text-accent mb-1" />
                            <span class="font-bold">{{ $keyword->minutes_count }}</span>
                        </div>
                    </div>

                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center p-12 border-2 border-dashed border-base-200 rounded-xl bg-base-50/50">
                <div class="bg-base-200 p-4 rounded-full mb-4">
                    <x-mary-icon name="o-rectangle-stack" class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-bold text-gray-600">No keywords found</h3>
                <p class="text-gray-400 text-sm mb-6 text-center max-w-xs">Create your first keyword to start tracking usage across your system.</p>
                <x-mary-button icon="o-plus" class="btn-primary" label="Create First Keyword" wire:click="create" />
            </div>
        @endforelse
    </div>

    <div class="flex justify-center mt-6">
        {{ $this->keywords()->links() }}
    </div>

    <x-mary-modal wire:model="modal" :title="$editing ? 'Edit Keyword' : 'New Keyword'">
        <x-mary-form wire:submit="save">
            <x-mary-input label="Name" wire:model="name" />
            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.modal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>