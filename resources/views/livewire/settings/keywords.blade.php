<?php

use Livewire\Volt\Component;
use App\Models\Keyword;
use Mary\Traits\Toast;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new class extends Component {
    use Toast, WithPagination;

    public $search = '';
    public $modal = false;
    public $editing = null;
    public $name = '';

    public function with(): array
    {
        $this->authorize('viewAny', Keyword::class);
        return [
            'keywords' => Keyword::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->withCount(['agendaItems', 'minutes']) 
                ->orderBy('name', 'asc')
                ->paginate(20)
        ];
    }

    public function create()
    {
        $this->authorize('create', Keyword::class);
        $this->reset(['name', 'editing']);
        $this->resetValidation();
        $this->modal = true;
    }

    public function edit(Keyword $keyword)
    {
        $this->authorize('update', $keyword);
        $this->editing = $keyword;
        $this->name = $keyword->name;
        $this->resetValidation();
        $this->modal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('keywords')->ignore($this->editing->id ?? null)
            ],
        ]);

        if ($this->editing) {
            $this->authorize('update', $this->editing);
            $this->editing->update(['name' => $this->name]);
            $this->success('Keyword updated successfully.');
        } else {
            $this->authorize('create', Keyword::class);
            Keyword::create(['name' => $this->name]);
            $this->success('Keyword created successfully.');
        }

        $this->modal = false;
    }

    public function delete(Keyword $keyword)
    {
        $this->authorize('delete', $keyword);
        
        // Optional: Check usage before delete
        if($keyword->agenda_items_count > 0 || $keyword->minutes_count > 0) {
            $this->error("Cannot delete keyword currently in use.");
            return;
        }

        $keyword->delete();
        $this->success('Keyword deleted.');
    }
}; ?>

<div>
    {{-- Consistent Header Component --}}
    <x-mary-header title="Keywords Library" subtitle="Manage tags and their usage across system" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search keywords..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            @can('create keywords')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="Add Keyword" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- Grid Layout --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($keywords as $keyword)
            <div 
                wire:key="keyword-{{ $keyword->id }}"
                wire:click="edit({{ $keyword->id }})"
                class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-all duration-200 group flex flex-col justify-between cursor-pointer"
            >
                {{-- Card Top: Content --}}
                <div class="card-body p-5">
                    <div class="flex justify-between items-start">
                        <div class="flex gap-3 items-center w-full">
                            {{-- Visual "Hash" Icon for Tag feel --}}
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                                <span class="font-bold text-lg">#</span>
                            </div>
                            
                            {{-- Keyword Name --}}
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-base-content text-lg truncate" title="{{ $keyword->name }}">
                                    {{ $keyword->name }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    ID: {{ $keyword->id }}
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Card Bottom: Usage Stats --}}
                <div class="bg-base-200/50 border-t border-base-200 py-3 flex justify-between items-center rounded-b-xl text-xs font-medium text-gray-500">
                    <div class="flex px-5 gap-4">
                        <span class="flex items-center gap-1.5" title="Used in {{ $keyword->agenda_items_count }} Agendas">
                            <x-mary-icon name="o-document-text" class="w-3.5 h-3.5 text-blue-500" />
                            {{ $keyword->agenda_items_count }} Agendas
                        </span>
                        <span class="flex items-center gap-1.5" title="Used in {{ $keyword->minutes_count }} Minutes">
                            <x-mary-icon name="o-clock" class="w-3.5 h-3.5 text-orange-500" />
                            {{ $keyword->minutes_count }} Minutes
                        </span>
                    </div>
                    
                    <div class="flex px-0">
                    {{-- Quick Edit Action on Hover --}}
                        @can('edit keywords')
                        <div class="lg:opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-mary-button 
                                icon="o-pencil" 
                                class="btn-ghost btn-xs text-primary" 
                                wire:click="edit({{ $keyword->id }})" 
                            />
                        </div>
                        @endcan
                        @can('delete keywords')
                        <div class="lg:opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-mary-button 
                                icon="o-trash" 
                                class="btn-ghost btn-xs text-error" 
                                wire:click="delete({{ $keyword->id }})" 
                            />
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-16 text-center bg-base-100 rounded-xl border border-dashed border-base-300">
                <div class="bg-base-200 rounded-full p-4 mb-3">
                    <x-mary-icon name="o-tag" class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="font-bold text-lg">No keywords found</h3>
                <p class="text-gray-500 text-sm max-w-xs mx-auto mb-4">
                    Get started by adding tags to organize your meeting agendas and minutes.
                </p>
                <x-mary-button label="Add First Keyword" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $keywords->links() }}
    </div>

    {{-- Create/Edit Modal --}}
    <x-mary-modal wire:model="modal" class="backdrop-blur">
        <x-mary-header :title="$editing ? 'Edit Keyword' : 'New Keyword'" :subtitle="$editing ? 'Update tag name' : 'Create a new tag'" separator />
        
        <x-mary-form wire:submit="save"> 
            <x-mary-input label="Keyword Name" wire:model="name" placeholder="e.g. Academic Council" icon="o-tag" />
            
            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.modal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>