<?php

use App\Models\Announcement;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Component {
    use Toast;

    public $announcements;
    public string $search = '';
    public bool $showModal = false;
    public bool $editMode = false;
    public bool $showDeleteModal = false;
    public $announcementToDeleteId;

    // Form fields
    public $id;
    public $title;
    public $content;
    public $published_at;
    public $expires_at;
    public $is_active = true;

    public function mount()
    {
        if (!auth()->user()->can('view announcements')) {
            $this->error('Unauthorized access to announcements.');
            return $this->redirect(route('dashboard'), navigate: true);
        }
        $this->loadAnnouncements();
    }

    public function loadAnnouncements()
    {
        $this->announcements = Announcement::query()
            ->when($this->search, function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updatedSearch()
    {
        $this->loadAnnouncements();
    }

    public function create()
    {
        if (!auth()->user()->can('create announcements')) {
            abort(403);
        }
        $this->reset(['id', 'title', 'content', 'published_at', 'expires_at', 'is_active']);
        $this->is_active = true;
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit(Announcement $announcement)
    {
        if (!auth()->user()->can('edit announcements')) {
            abort(403);
        }
        $this->id = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->published_at = $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : null;
        $this->expires_at = $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\TH:i') : null;
        $this->is_active = $announcement->is_active;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        if ($this->editMode) {
            $announcement = Announcement::find($this->id);
            $announcement->update($validated);
            $this->success('Announcement updated successfully.');
        } else {
            Announcement::create($validated);
            $this->success('Announcement created successfully.');
        }

        $this->showModal = false;
        $this->loadAnnouncements();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('delete announcements')) {
            abort(403);
        }
        $this->announcementToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!auth()->user()->can('delete announcements')) {
            abort(403);
        }
        Announcement::find($this->announcementToDeleteId)->delete();
        $this->success('Announcement deleted successfully.');
        $this->showDeleteModal = false;
        $this->loadAnnouncements();
    }

    public function toggleActive($id)
    {
        if (!auth()->user()->can('edit announcements')) {
            abort(403);
        }
        $announcement = Announcement::find($id);
        $announcement->is_active = !$announcement->is_active;
        $announcement->save();
        $this->loadAnnouncements();
        $this->success('Status updated.');
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'title', 'label' => 'Title'],
                ['key' => 'status', 'label' => 'Status', 'sortable' => false],
                ['key' => 'dates', 'label' => 'Duration', 'sortable' => false],
                ['key' => 'created_by', 'label' => 'Created By'],
            ]
        ];
    }
}; ?>

<div class="p-4 md:p-8 max-w-7xl mx-auto">
    <x-mary-header title="Announcements" subtitle="Manage system-wide announcements." separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input 
                icon="o-magnifying-glass" 
                placeholder="Search..." 
                wire:model.live.debounce="search" 
                class="border-base-300 focus:border-primary w-full md:w-80"
            />
        </x-slot:middle>
        <x-slot:actions>
            @can('create announcements')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="New Announcement" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="rounded-2xl bg-base-100 overflow-hidden">
        @if($announcements->count() > 0)
            <x-mary-table :headers="$headers" :rows="$announcements" striped class="hover-row-cursor">
                @scope('cell_title', $announcement)
                    <div>
                        <div class="font-bold text-base">{{ $announcement->title }}</div>
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($announcement->content, 50) }}</div>
                    </div>
                @endscope
                @scope('cell_status', $announcement)
                    <x-mary-badge :value="$announcement->is_active ? 'Active' : 'Inactive'" class="{{ $announcement->is_active ? 'badge-success' : 'badge-ghost' }} badge-sm" />
                @endscope
                @scope('cell_dates', $announcement)
                    <div class="text-xs">
                        @if($announcement->published_at)
                            <div>From: {{ $announcement->published_at->format('M d, Y') }}</div>
                        @endif
                        @if($announcement->expires_at)
                            <div>To: {{ $announcement->expires_at->format('M d, Y') }}</div>
                        @endif
                        @if(!$announcement->published_at && !$announcement->expires_at)
                            <span class="text-gray-400">Always visible</span>
                        @endif
                    </div>
                @endscope
                @scope('cell_created_by', $announcement)
                    <div class="text-sm">{{ $announcement->creator->name ?? 'Unknown' }}</div>
                @endscope
                @scope('actions', $announcement)
                    <div class="flex justify-end gap-2">
                        @can('edit announcements')
                            <x-mary-button icon="{{ $announcement->is_active ? 'o-eye' : 'o-eye-slash' }}" wire:click="toggleActive({{ $announcement->id }})" class="btn-sm btn-ghost" tooltip="Toggle Status" />
                            <x-mary-button icon="o-pencil" wire:click="edit({{ $announcement->id }})" class="btn-sm btn-ghost" tooltip="Edit" />
                        @endcan
                        @can('delete announcements')
                            <x-mary-button icon="o-trash" wire:click="confirmDelete({{ $announcement->id }})" class="btn-sm btn-ghost text-error" tooltip="Delete" />
                        @endcan
                    </div>
                @endscope
            </x-mary-table>
        @else
            <div class="flex flex-col items-center justify-center py-16">
                <div class="bg-base-200 rounded-full p-4 mb-4">
                    <x-mary-icon name="o-megaphone" class="w-8 h-8 text-gray-400" />
                </div>
                <div class="text-lg font-bold text-gray-600">No announcements found</div>
                @can('create announcements')
                    <x-mary-button label="Create Announcement" icon="o-plus" class="btn-primary mt-4" wire:click="create" />
                @endcan
            </div>
        @endif
    </x-mary-card>

    {{-- MODAL --}}
    <x-mary-modal wire:model="showModal" title="{{ $editMode ? 'Edit Announcement' : 'New Announcement' }}" class="backdrop-blur-md">
        <x-mary-form wire:submit="save">
            <x-mary-input label="Title" wire:model="title" placeholder="Announcement Title" />
            <x-mary-textarea label="Content" wire:model="content" placeholder="Write your announcement here..." rows="5" />
            
            <div class="grid grid-cols-2 gap-4">
                <x-mary-datetime label="Publish Date" wire:model="published_at" type="datetime-local" />
                <x-mary-datetime label="Expiration Date" wire:model="expires_at" type="datetime-local" />
            </div>

            <x-mary-toggle label="Active" wire:model="is_active" hint="Visible to users" />

            <x-slot:actions>
                <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- DELETE MODAL --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Announcement" class="backdrop-blur-sm">
        <div class="text-center p-4">
            <div class="bg-red-50 text-red-500 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6" />
            </div>
            <div class="font-bold text-lg">Delete this announcement?</div>
            <div class="text-gray-500 mt-1">This action cannot be undone.</div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
