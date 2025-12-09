<?php

use App\Models\Announcement;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

new class extends Component {
    use Toast, WithPagination;

    // Filter & Search
    public string $search = '';
    
    // UI State
    public bool $showModal = false;
    public bool $showViewModal = false;
    public bool $editMode = false;
    public bool $showDeleteModal = false;
    public int $step = 1;
    
    // Data Containers
    public $viewAnnouncement;
    public $announcementToDeleteId;
    public $users_search = [];

    // Form fields
    public $id;
    public $title;
    public $content;
    public $published_at;
    public $expires_at;
    public $is_active = true;
    
    // Expires In Logic
    public $expires_in;
    public $expires_in_unit = 'days';

    // Audience Fields
    public $audience_type = 'all';
    public $target_users = [];
    public $excluded_users = [];

    public function mount()
    {
        $this->authorize('viewAny', Announcement::class);
        $this->searchUsers(); // Preload initial list
    }

    public function searchUsers(?string $term = '')
    {
        $this->users_search = User::query()
            ->when($term, fn(Builder $q) => $q->where('name', 'like', "%$term%")->orWhere('email', 'like', "%$term%"))
            ->orderBy('name')
            ->take(10)
            ->get();
    }

    public function updatedSearch() { $this->resetPage(); }

    // Auto-calculate expiration date
    public function updatedExpiresIn() { $this->calculateExpiration(); }
    public function updatedExpiresInUnit() { $this->calculateExpiration(); }
    public function updatedPublishedAt() { $this->calculateExpiration(); }

    public function calculateExpiration()
    {
        if (empty($this->expires_in)) {
            if ($this->expires_in === '' || $this->expires_in === null) {
                 $this->expires_at = null;
            }
            return;
        }

        if (!$this->published_at) {
            $this->published_at = now()->format('Y-m-d\TH:i');
        }

        try {
            $base = Carbon::parse($this->published_at);
            $this->expires_at = $base->add($this->expires_in_unit, (int)$this->expires_in)->format('Y-m-d\TH:i');
        } catch (\Exception $e) {
            // Ignore invalid dates/units
        }
    }

    public function with(): array
    {
        $query = Announcement::visible();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'announcements' => $query->orderBy('created_at', 'desc')->paginate(9)
        ];
    }

    public function create()
    {
        $this->authorize('create', Announcement::class);
        $this->reset(['id', 'title', 'content', 'published_at', 'expires_at', 'is_active', 'audience_type', 'target_users', 'excluded_users', 'expires_in', 'expires_in_unit']);
        $this->is_active = true;
        $this->audience_type = 'all';
        $this->published_at = now()->format('Y-m-d\TH:i');
        $this->editMode = false;
        $this->step = 1;
        $this->showModal = true;
    }

    public function edit(Announcement $announcement)
    {
        $this->authorize('update', $announcement);
        $this->id = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        
        // Ensure HTML5 date format compatibility
        $this->published_at = $announcement->published_at?->format('Y-m-d\TH:i');
        $this->expires_at = $announcement->expires_at?->format('Y-m-d\TH:i');
        
        // Reset helper fields
        $this->expires_in = null;
        $this->expires_in_unit = 'days';

        $this->is_active = $announcement->is_active;
        $this->audience_type = $announcement->audience_type;
        $this->target_users = $announcement->targetUsers->pluck('id')->toArray();
        $this->excluded_users = $announcement->excludedUsers->pluck('id')->toArray();
        
        $this->editMode = true;
        $this->step = 1;
        $this->showModal = true;
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'is_active' => 'boolean',
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'published_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:published_at',
            ]);
        }
        
        $this->step++;
    }

    public function prevStep()
    {
        $this->step--;
    }

    public function save()
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'is_active' => 'boolean',
            'audience_type' => 'required|in:all,users',
            'target_users' => 'array',
            'excluded_users' => 'array',
        ]);

        $data = collect($validated)->except(['target_users', 'excluded_users'])->toArray();

        if ($this->editMode) {
            $announcement = Announcement::find($this->id);
            $announcement->update($data);
            $announcement->targetUsers()->sync($this->audience_type === 'users' ? $this->target_users : []);
            $announcement->excludedUsers()->sync($this->excluded_users);
            $this->success('Announcement updated.');
        } else {
            $data['created_by'] = auth()->id();
            $announcement = Announcement::create($data);
            $announcement->targetUsers()->sync($this->audience_type === 'users' ? $this->target_users : []);
            $announcement->excludedUsers()->sync($this->excluded_users);
            $this->success('Broadcast published.');
        }

        $this->showModal = false;
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', Announcement::find($id));
        $this->announcementToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $announcement = Announcement::find($this->announcementToDeleteId);
        $this->authorize('delete', $announcement);
        $announcement->delete();
        $this->success('Announcement deleted.');
        $this->showDeleteModal = false;
    }

    public function toggleActive($id)
    {
        $announcement = Announcement::find($id);
        $this->authorize('update', $announcement);
        $announcement->is_active = !$announcement->is_active;
        $announcement->save();
        $this->success('Status updated.');
    }

    public function view($id)
    {
        $this->viewAnnouncement = Announcement::find($id);
        $this->authorize('view', $this->viewAnnouncement);
        $this->showViewModal = true;
    }
}; ?>

<div class="space-y-8 p-2">
    
    {{-- Main Header --}}
    <x-mary-header title="Announcements" subtitle="System-wide updates and notifications" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" class="w-full md:w-72" />
        </x-slot:middle>
        <x-slot:actions>
            @can('create announcements')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="New Announcement" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($announcements as $announcement)
            @php
                $status = $announcement->status;
                $isTargeted = $announcement->audience_type === 'users';
                
                // Status Visuals
                $statusConfig = match($status) {
                    'Active' => ['class' => 'bg-success/15 text-success border-success/20', 'icon' => 'o-check-circle'],
                    'Scheduled' => ['class' => 'bg-info/15 text-info border-info/20', 'icon' => 'o-clock'],
                    'Expired' => ['class' => 'bg-base-200 text-gray-500 border-base-300', 'icon' => 'o-archive-box'],
                    'Inactive' => ['class' => 'bg-warning/15 text-warning border-warning/20', 'icon' => 'o-eye-slash'],
                    default => ['class' => 'bg-base-200 text-gray-500', 'icon' => 'o-question-mark-circle']
                };
            @endphp

            <div wire:key="ann-{{ $announcement->id }}" class="card bg-base-100 border border-base-200/80 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between h-full">
                
                <div class="p-5 flex-1 cursor-pointer" wire:click="view({{ $announcement->id }})">
                    {{-- Status Bar --}}
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-2 px-2.5 py-1 rounded-full border text-xs font-bold uppercase tracking-wide {{ $statusConfig['class'] }}">
                            <x-mary-icon :name="$statusConfig['icon']" class="w-3.5 h-3.5" />
                            {{ $status }}
                        </div>
                        
                        {{-- Quick Actions --}}
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity" wire:click.stop>
                            @can('update', $announcement)
                                <x-mary-button icon="o-pencil" class="btn-xs btn-ghost btn-circle" wire:click="edit({{ $announcement->id }})" />
                                <x-mary-button 
                                    icon="{{ $announcement->is_active ? 'o-no-symbol' : 'o-check' }}" 
                                    class="btn-xs btn-ghost btn-circle {{ $announcement->is_active ? 'text-warning' : 'text-success' }}" 
                                    wire:click="toggleActive({{ $announcement->id }})" 
                                />
                            @endcan
                            @can('delete', $announcement)
                                <x-mary-button icon="o-trash" class="btn-xs btn-ghost btn-circle text-error" wire:click="confirmDelete({{ $announcement->id }})" />
                            @endcan
                        </div>
                    </div>

                    {{-- Title & Excerpt --}}
                    <h3 class="font-bold text-lg leading-tight line-clamp-2 mb-2 group-hover:text-primary transition-colors">
                        {{ $announcement->title }}
                    </h3>
                    <p class="text-sm text-gray-500 line-clamp-3 leading-relaxed">
                        {{ Str::limit($announcement->content, 120) }}
                    </p>
                </div>

                {{-- Footer Info --}}
                <div class="px-5 py-3 border-t border-base-100 bg-base-50/50 flex justify-between items-center rounded-b-xl text-xs">
                    <div class="flex items-center gap-3 text-gray-500">
                        {{-- Audience Badge --}}
                        <div class="flex items-center gap-1 font-medium {{ $isTargeted ? 'text-purple-600' : 'text-blue-600' }}">
                            <x-mary-icon :name="$isTargeted ? 'o-user-group' : 'o-globe-alt'" class="w-3.5 h-3.5" />
                            <span>{{ $isTargeted ? 'Targeted' : 'Public' }}</span>
                        </div>
                        
                        {{-- Separator --}}
                        <div class="h-3 w-px bg-base-300"></div>
                        
                        {{-- Date --}}
                        <div class="flex items-center gap-1">
                            <x-mary-icon name="o-calendar" class="w-3.5 h-3.5" />
                            {{ $announcement->published_at?->format('M d') ?? 'Draft' }}
                        </div>
                    </div>
                    
                    {{-- Creator Initials --}}
                    <div class="tooltip tooltip-left" data-tip="By {{ $announcement->creator->name ?? 'System' }}">
                        @php
                            $creator = $announcement->creator;
                            $avatar = $creator?->profile_photo_path 
                                ? (Str::startsWith($creator->profile_photo_path, ['http', 'https']) 
                                    ? $creator->profile_photo_path 
                                    : asset('storage/' . $creator->profile_photo_path)) 
                                : null;
                        @endphp

                        <div class="avatar {{ $avatar ? '' : 'placeholder' }}">
                            @if($avatar)
                                <div class="w-8 h-8 rounded-full">
                                    <img src="{{ $avatar }}" alt="{{ $creator->name ?? 'User' }}" class="object-cover w-full h-full" />
                                </div>
                            @else
                                <div class="bg-base-300 text-base-content rounded-full w-8 h-8 text-xs grid place-items-center">
                                    <span class="font-bold">{{ substr($creator->name ?? 'S', 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 flex flex-col items-center justify-center text-center bg-base-100 rounded-2xl border-2 border-dashed border-base-200">
                <div class="bg-base-200 p-4 rounded-full mb-3 text-base-content/40">
                    <x-mary-icon name="o-megaphone" class="w-8 h-8" />
                </div>
                <h3 class="font-bold text-lg text-base-content">No Announcements Found</h3>
                <p class="text-gray-500 text-sm max-w-sm mx-auto mb-4">Get started by creating a new system-wide broadcast.</p>
                <x-mary-button label="Create Announcement" icon="o-plus" class="btn-primary" wire:click="create" />
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $announcements->links() }}
    </div>

    {{-- 
        ADD/EDIT MODAL
        Multi-step Wizard
    --}}
    <x-mary-modal wire:model="showModal" class="backdrop-blur-sm" box-class="w-11/12 max-w-3xl">
        <x-mary-header class="backdrop-blur-sm !mb-2"  :title="$editMode ? 'Edit Announcement' : 'New Announcement'" separator />
        
        <x-mary-form wire:submit="save">

            <x-mary-steps wire:model="step" class="border-b border-base-200" stepper-classes="w-full bg-base-200">
                <x-mary-step step="1" text="Details">
                    <div class="space-y-5 animate-fade-in">
                        {{-- Title & Active Row --}}
                        <div class="flex gap-4 items-start">
                            <div class="flex-1">
                                <x-mary-input label="Title" wire:model="title" placeholder="e.g., System Maintenance" icon="o-tag" />
                            </div>
                            <div class="pt-8">
                                <x-mary-toggle label="Active" wire:model="is_active" class="toggle-success" right />
                            </div>
                        </div>

                        {{-- Content --}}
                        <x-mary-textarea label="Content" wire:model="content" placeholder="Write your announcement details here..." rows="12" hint="Markdown is supported" />
                    </div>
                </x-mary-step>

                <x-mary-step step="2" text="Schedule">
                    <div class="space-y-6 animate-fade-in">
                        <div class="bg-base-200/30 p-5 rounded-xl border border-base-200">
                            <div class="flex items-center gap-2 mb-5 text-primary font-bold text-sm uppercase tracking-wide">
                                <x-mary-icon name="o-clock" class="w-4 h-4" /> Schedule Configuration
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-mary-datetime label="Publish Date" wire:model.live="published_at" type="datetime-local" />
                                
                                <x-mary-datetime label="Expiration Date" wire:model="expires_at" type="datetime-local" hint="Calculated automatically or set manually" />
                            </div>

                            <div class="divider">OR QUICK SET</div>

                            {{-- Expires In --}}
                            <div class="flex gap-2 items-end">
                                <div class="flex-1">
                                    <x-mary-input label="Expires In" wire:model.live="expires_in" type="number" min="1" placeholder="Permanent" />
                                </div>
                                <div class="w-32">
                                    <x-mary-select label="Unit" wire:model.live="expires_in_unit" :options="[['id'=>'days', 'name'=>'Days'], ['id'=>'weeks', 'name'=>'Weeks'], ['id'=>'months', 'name'=>'Months']]" />
                                </div>
                            </div>
                        </div>
                    </div>
                </x-mary-step>

                <x-mary-step step="3" text="Audience">
                    <div class="space-y-6 animate-fade-in">
                        <div class="bg-base-200/30 p-5 rounded-xl border border-base-200">
                            <div class="flex items-center gap-2 mb-3 text-primary font-bold text-sm uppercase tracking-wide">
                                <x-mary-icon name="o-users" class="w-4 h-4" /> Target Audience
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="cursor-pointer border rounded-xl p-4 text-center transition-all {{ $audience_type === 'all' ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary shadow-md' : 'border-base-300 hover:border-base-content/30 hover:bg-base-100' }}" wire:click="$set('audience_type', 'all')">
                                    <x-mary-icon name="o-globe-alt" class="w-8 h-8 mb-2 mx-auto" />
                                    <div class="font-bold">All Users</div>
                                    <div class="text-xs opacity-60 mt-1">Visible to everyone</div>
                                </div>
                                <div class="cursor-pointer border rounded-xl p-4 text-center transition-all {{ $audience_type === 'users' ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary shadow-md' : 'border-base-300 hover:border-base-content/30 hover:bg-base-100' }}" wire:click="$set('audience_type', 'users')">
                                    <x-mary-icon name="o-user-group" class="w-8 h-8 mb-2 mx-auto" />
                                    <div class="font-bold">Specific Users</div>
                                    <div class="text-xs opacity-60 mt-1">Select individuals</div>
                                </div>
                            </div>

                            {{-- Conditional Target Users --}}
                            @if($audience_type === 'users')
                                <div class="mb-4 animate-fade-in">
                                    <x-mary-choices 
                                        label="Include Users" 
                                        wire:model="target_users" 
                                        :options="$users_search" 
                                        search-function="searchUsers" 
                                        icon="o-check"
                                        class="bg-base-100"
                                    />
                                </div>
                            @endif

                            {{-- Exceptions (Always Visible) --}}
                            <div>
                                <x-mary-choices 
                                    label="Exceptions (Exclude)" 
                                    wire:model="excluded_users" 
                                    :options="$users_search" 
                                    search-function="searchUsers" 
                                    icon="o-no-symbol"
                                    class="bg-base-100"
                                    hint="Users selected here will NOT see the post."
                                />
                            </div>
                        </div>
                    </div>
                </x-mary-step>
            </x-mary-steps>

            <x-slot:actions>
                @if($step > 1)
                    <x-mary-button label="Back" wire:click="prevStep" />
                @else
                    <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                @endif

                @if($step < 3)
                    <x-mary-button label="Next" class="btn-primary" wire:click="nextStep" icon-right="o-arrow-right" />
                @else
                    <x-mary-button label="Save Announcement" class="btn-primary" type="submit" spinner="save" icon="o-check" />
                @endif
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    {{-- VIEW DETAILS MODAL --}}
    <x-mary-modal wire:model="showViewModal" class="backdrop-blur" box-class="w-11/12 max-w-3xl">
        @if($viewAnnouncement)
            <x-mary-header :title="$viewAnnouncement->title" separator>
                <x-slot:subtitle>
                    <span class="opacity-60">Posted on {{ $viewAnnouncement->created_at->format('F d, Y') }}</span>
                </x-slot:subtitle>
                <x-slot:actions>
                    @php
                        $vStatus = $viewAnnouncement->status;
                        $vColor = match($vStatus) { 'Active' => 'badge-success', 'Scheduled' => 'badge-info', 'Expired' => 'badge-warning', default => 'badge-ghost' };
                    @endphp
                    <x-mary-badge :value="$vStatus" class="{{ $vColor }}" />
                </x-slot:actions>
            </x-mary-header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Main Content --}}
                <div class="md:col-span-2">
                    <div class="prose prose-sm max-w-none">
                        {!! Str::markdown($viewAnnouncement->content) !!}
                    </div>
                </div>

                {{-- Sidebar Details --}}
                <div class="space-y-6 text-sm">
                    {{-- Timeline --}}
                    <div class="bg-base-200/50 p-4 rounded-xl border border-base-200">
                        <div class="font-bold text-gray-500 uppercase text-xs mb-2">Duration</div>
                        <div class="flex justify-between mb-1">
                            <span>Start:</span>
                            <span class="font-mono">{{ $viewAnnouncement->published_at?->format('M d, Y H:i') ?? 'Immediate' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>End:</span>
                            <span class="font-mono">{{ $viewAnnouncement->expires_at?->format('M d, Y H:i') ?? 'Never' }}</span>
                        </div>
                    </div>

                    {{-- Audience Breakdown --}}
                    <div class="bg-base-200/50 p-4 rounded-xl border border-base-200">
                        <div class="font-bold text-gray-500 uppercase text-xs mb-2">Visibility</div>
                        
                        <div class="mb-2">
                            <span class="block text-gray-500 text-xs mb-1">Target:</span>
                            @if($viewAnnouncement->audience_type === 'all')
                                <div class="badge badge-neutral">All Users</div>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($viewAnnouncement->targetUsers->take(5) as $u)
                                        <div class="badge badge-neutral badge-xs">{{ $u->name }}</div>
                                    @endforeach
                                    @if($viewAnnouncement->targetUsers->count() > 5)
                                        <span class="text-xs opacity-50">+{{ $viewAnnouncement->targetUsers->count() - 5 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if($viewAnnouncement->excludedUsers->count() > 0)
                            <div>
                                <span class="block text-error text-xs mb-1">Excluded:</span>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($viewAnnouncement->excludedUsers->take(5) as $u)
                                        <div class="badge badge-error badge-outline badge-xs">{{ $u->name }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.showViewModal = false" />
            </x-slot:actions>
        @endif
    </x-mary-modal>

    {{-- Delete Modal --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Broadcast" class="backdrop-blur-sm">
        <div class="text-center p-6">
            <x-mary-icon name="o-trash" class="w-12 h-12 text-error mx-auto mb-3 opacity-50" />
            <p class="text-lg font-medium">Are you sure you want to delete this?</p>
            <p class="text-sm text-gray-500">This action cannot be undone.</p>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>