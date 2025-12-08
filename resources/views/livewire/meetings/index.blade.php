<?php

use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\User;
use App\Http\Requests\MeetingRequest;
use App\Services\MeetingService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Carbon\Carbon;
use App\Models\Setting;

new class extends Component {
    use Toast, WithFileUploads, WithPagination;

    public string $pageTitle = 'Meetings Management';
    public string $pageSubtitle = 'Manage all meetings including agenda and minutes records.';

    // public $meetings; // Removed for pagination
    public string $search = '';
    public $meetingTypes;
    public $creatableMeetingTypes = [];
    
    // Settings
    public $perPage = 15;
    public $dateFormat = 'M d, Y';
    public $timeFormat = 'H:i';
    
    // Dropdown Options
    public $directors;
    public $registrars;
    public $vcs;
    
    // UI State
    public bool $showModal = false;
    public bool $showImportModal = false;
    public bool $showOptionsModal = false;
    public bool $showDeleteModal = false;
    public bool $showExportModal = false;
    public bool $drawer = false;
    
    // Filters
    public bool $showDeleted = false;
    public $filterMeetingType = '';
    public $filterDateStart = '';
    public $filterDateEnd = '';
    public string $filterStatus = 'all';
    public string $filterTimeframe = 'all';
    public $filterKeyPerson = '';

    public $keyPeopleOptions;
    public array $statusOptions = [];
    public array $timeframeOptions = [];

    // Import
    public $file;
    public bool $hasHeader = true;

    // Form
    public bool $editMode = false;
    public bool $viewMode = false;

    // Form Fields
    public $id;
    public $title;
    public $number;
    public $meeting_type_id;
    public $date;
    public $time;
    public $is_last = false;
    public $director_id;
    public $registrar_id;
    public $vc_id;

    // View Models
    public $director;
    public $registrar;
    public $vc;

    // Participants Data
    public $allParticipants = [];
    
    // Selected Participants
    public $members = [];
    public $attendees = [];

    // Participant Creation
    public bool $showParticipantModal = false;
    public string $newParticipantName = '';
    public string $newParticipantEmail = '';
    public string $newParticipantDesignation = '';
    public string $newParticipantOrganization = '';

    // Counts
    public $agenda_items_count = 0;
    public $minutes_count = 0;

    // Action Data
    public string $optionsTitle = '';
    public string $optionsType = ''; 
    public $selectedMeetingId;
    public $meetingToDeleteId;


    public function mount()
    {
        view()->share('page_title', $this->pageTitle);
        view()->share('page_subtitle', $this->pageSubtitle);

        $this->authorize('viewAny', Meeting::class);

        $user = auth()->user();
        $allTypes = MeetingType::where('is_active', true)->orderBy('id')->get();

        // Filter for View/Filter
        $this->meetingTypes = $allTypes->filter(function($type) use ($user) {
             if ($user->hasRole('Super Admin')) return true;
             return $user->can('access meetings') || $user->hasMeetingPermission($type->id, 'view');
        });

        // Filter for Create
        $this->creatableMeetingTypes = $allTypes->filter(function($type) use ($user) {
             if ($user->hasRole('Super Admin')) return true;
             return $user->hasMeetingPermission($type->id, 'create');
        });
        
        // Load users by Role
        $this->directors = User::role('Director')->orderBy('name')->get();
        $this->registrars = User::role('Registrar')->orderBy('name')->get();
        $this->vcs = User::role('VC')->orderBy('name')->get();

        $this->keyPeopleOptions = $this->directors
            ->merge($this->registrars)
            ->merge($this->vcs)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $this->statusOptions = [
            ['id' => 'all', 'name' => 'All Meetings'],
            ['id' => 'final', 'name' => 'Final Meetings'],
            ['id' => 'non-final', 'name' => 'In-progress Meetings'],
        ];

        $this->timeframeOptions = [
            ['id' => 'all', 'name' => 'All Dates'],
            ['id' => 'upcoming', 'name' => 'Upcoming Only'],
            ['id' => 'past', 'name' => 'Past Only'],
            ['id' => 'this-week', 'name' => 'This Week'],
            ['id' => 'this-month', 'name' => 'This Month'],
            ['id' => 'custom', 'name' => 'Custom Range'],
        ];

        $this->perPage = Setting::get('pagination_size', 15);
        $this->dateFormat = Setting::get('date_format', 'M d, Y');
        $this->timeFormat = Setting::get('time_format', 'H:i');

        $this->allParticipants = \App\Models\Participant::orderBy('name')->get();
    }

    public function getMeetingsQuery()
    {
        $query = Meeting::query();

        // Filter by allowed meeting types
        $allowedTypeIds = $this->meetingTypes->pluck('id');
        $query->whereIn('meeting_type_id', $allowedTypeIds);

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if ($this->filterMeetingType) {
            $query->where('meeting_type_id', $this->filterMeetingType);
        }

        if ($this->filterStatus === 'final') {
            $query->where('is_last', true);
        } elseif ($this->filterStatus === 'non-final') {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('is_last')->orWhere('is_last', false);
            });
        }

        if ($this->filterKeyPerson) {
            $filterValue = $this->filterKeyPerson;
            $query->where(function ($participantQuery) use ($filterValue) {
                $participantQuery
                    ->where('director_id', $filterValue)
                    ->orWhere('registrar_id', $filterValue)
                    ->orWhere('vc_id', $filterValue);
            });
        }

        $today = Carbon::today();
        $now = Carbon::now();

        switch ($this->filterTimeframe) {
            case 'upcoming':
                $query->whereDate('date', '>=', $today);
                break;
            case 'past':
                $query->whereDate('date', '<', $today);
                break;
            case 'this-week':
                $query->whereBetween('date', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
                break;
            case 'this-month':
                $query->whereBetween('date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                break;
        }

        if ($this->filterDateStart) {
            $query->whereDate('date', '>=', $this->filterDateStart);
        }

        if ($this->filterDateEnd) {
            $query->whereDate('date', '<=', $this->filterDateEnd);
        }

        if (trim($this->search) !== '') {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($searchQuery) use ($searchTerm) {
                $searchQuery
                    ->where('title', 'like', $searchTerm)
                    ->orWhere('number', 'like', $searchTerm)
                    ->orWhereHas('meetingType', fn ($type) => $type->where('name', 'like', $searchTerm))
                    ->orWhereHas('director', fn ($user) => $user->where('name', 'like', $searchTerm))
                    ->orWhereHas('registrar', fn ($user) => $user->where('name', 'like', $searchTerm))
                    ->orWhereHas('vc', fn ($user) => $user->where('name', 'like', $searchTerm));
            });
        }

        $direction = 'asc';
        if (in_array($this->filterTimeframe, ['past', 'all'], true)) {
            $direction = 'desc';
        }

        return $query
            ->with(['meetingType', 'director', 'registrar', 'vc'])
            ->withCount(['agendaItems', 'minutes'])
            ->orderBy('date', $direction)
            ->orderBy('time', $direction);
    }

    // --- Live Updates ---
    public function updated($property): void
    {
        if (in_array($property, ['filterDateStart', 'filterDateEnd'], true) && $this->filterDateStart && $this->filterDateEnd) {
            if ($this->filterDateStart > $this->filterDateEnd) {
                [$this->filterDateStart, $this->filterDateEnd] = [$this->filterDateEnd, $this->filterDateStart];
            }
        }

        if ($property === 'filterTimeframe' && $this->filterTimeframe !== 'custom') {
            $this->filterDateStart = '';
            $this->filterDateEnd = '';
        }

        $watchedProperties = [
            'search',
            'showDeleted',
            'filterMeetingType',
            'filterDateStart',
            'filterDateEnd',
            'filterStatus',
            'filterTimeframe',
            'filterKeyPerson',
        ];

        if (in_array($property, $watchedProperties, true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'showDeleted',
            'filterMeetingType',
            'filterDateStart',
            'filterDateEnd',
            'filterStatus',
            'filterTimeframe',
            'filterKeyPerson',
        ]);

        // Defaults after reset
        $this->filterStatus = 'all';
        $this->filterTimeframe = 'all';
        $this->resetPage();
    }

    // --- CRUD Actions ---
    public function create()
    {
        $this->authorize('create', Meeting::class);

        if ($this->creatableMeetingTypes->isEmpty()) {
            $this->error('You do not have permission to create any meetings.');
            return;
        }

        $this->reset(['id', 'title', 'number', 'meeting_type_id', 'date', 'time', 'is_last', 'director_id', 'registrar_id', 'vc_id', 'members', 'attendees']);
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function edit(Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        $this->fillForm($meeting);
        $this->editMode = true;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function view(Meeting $meeting)
    {
        $this->fillForm($meeting);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function fillForm(Meeting $meeting)
    {
        $this->id = $meeting->id;
        $this->title = $meeting->title;
        $this->number = $meeting->number;
        $this->meeting_type_id = $meeting->meeting_type_id;
        $this->date = $meeting->date ? $meeting->date->format('Y-m-d') : null;
        $this->time = $meeting->time ? $meeting->time->format('H:i') : null;
        $this->is_last = $meeting->is_last;
        $this->director_id = $meeting->director_id;
        $this->registrar_id = $meeting->registrar_id;
        $this->vc_id = $meeting->vc_id;

        $this->director = $meeting->director;
        $this->registrar = $meeting->registrar;
        $this->vc = $meeting->vc;

        $this->agenda_items_count = $meeting->agendaItems()->count();
        $this->minutes_count = $meeting->minutes()->count();

        // Load Participants
        $meeting->load(['participants']);

        $this->members = $meeting->participants
            ->where('pivot.type', 'member')
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();

        $this->attendees = $meeting->participants
            ->where('pivot.type', 'attendee')
            ->pluck('id')
            ->map(fn($id) => (string)$id)
            ->toArray();
    }

    public function save(MeetingService $service)
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $validated = $this->validate((new MeetingRequest())->rules());

        $participantData = [
            'members' => $this->members,
            'attendees' => $this->attendees,
        ];

        if ($this->editMode) {
            $meeting = Meeting::find($this->id);
            $service->updateMeeting($meeting, $validated, $participantData);
            $this->success('Meeting updated successfully.');
        } else {
            $service->createMeeting($validated, $participantData);
            $this->success('Meeting created successfully.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', Meeting::find($id));

        $this->meetingToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(MeetingService $service)
    {
        $meeting = Meeting::find($this->meetingToDeleteId);
        $this->authorize('delete', $meeting);
        $service->deleteMeeting($meeting);
        $this->success('Meeting deleted successfully.');
        $this->showDeleteModal = false;
        $this->resetPage();
    }

    public function restore($id)
    {
        $meeting = Meeting::withTrashed()->find($id);
        $this->authorize('restore', $meeting);
        $meeting->restore();
        $this->success('Meeting restored successfully.');
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $meeting = Meeting::find($id);
        $this->authorize('update', $meeting);
        $meeting->is_last = !$meeting->is_last;
        $meeting->save();
        $this->success('Status updated.');
   
        $this->resetPage();
    }

    public function openOptions($id, $type)
    {
        $this->selectedMeetingId = $id;
        $this->optionsType = $type;
        $this->optionsTitle = $type === 'agenda' ? 'Agenda Options' : 'Minutes Options';
        $this->showOptionsModal = true;
    }

    public function openParticipantModal()
    {
        $this->reset(['newParticipantName', 'newParticipantEmail', 'newParticipantDesignation', 'newParticipantOrganization']);
        $this->showParticipantModal = true;
    }

    public function saveParticipant()
    {
        $this->validate([
            'newParticipantName' => 'required|string|max:255',
            'newParticipantEmail' => 'nullable|email|max:255',
            'newParticipantDesignation' => 'nullable|string|max:255',
            'newParticipantOrganization' => 'nullable|string|max:255',
        ]);

        \App\Models\Participant::create([
            'name' => $this->newParticipantName,
            'email' => $this->newParticipantEmail,
            'designation' => $this->newParticipantDesignation,
            'organization' => $this->newParticipantOrganization,
        ]);

        $this->allParticipants = \App\Models\Participant::orderBy('name')->get();
        $this->showParticipantModal = false;
        $this->success('Participant created successfully.');
    }

    // --- Export Logic ---
    public function export($format = 'pdf')
    {
        $headers = ['Title', 'Number', 'Meeting Type', 'Date', 'Time', 'Is Last', 'Director', 'Registrar', 'VC'];
        $meetings = $this->getMeetingsQuery()->get();
        $data = [];

        foreach ($meetings as $meeting) {
            $data[] = [
                $meeting->title,
                $meeting->number,
                $meeting->meetingType->name ?? '',
                $meeting->date ? $meeting->date->format('Y-m-d') : '',
                $meeting->time ? $meeting->time->format('H:i') : '',
                $meeting->is_last ? 'Yes' : 'No',
                $meeting->director->name ?? '',
                $meeting->registrar->name ?? '',
                $meeting->vc->name ?? ''
            ];
        }

        if ($format === 'csv') {
            $callback = function() use ($data, $headers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $headers);
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=meetings.csv",
            ]);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', ['title' => 'Meetings', 'headers' => $headers, 'rows' => $data]);
            return response()->streamDownload(fn() =>  $pdf->output(), 'meetings.pdf');
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Meetings List', ['size' => 16, 'bold' => true]);
            $table = $section->addTable(['borderSize' => 6]);
            $table->addRow();
            foreach ($headers as $header) $table->addCell(2000)->addText($header, ['bold' => true]);
            foreach ($data as $row) {
                $table->addRow();
                foreach ($row as $cell) $table->addCell(2000)->addText($cell);
            }
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            return response()->streamDownload(fn() => $objWriter->save('php://output'), 'meetings.docx');
        } elseif ($format === 'doc') {
             $content = view('exports.pdf', ['title' => 'Meetings', 'headers' => $headers, 'rows' => $data])->render();
            return response()->streamDownload(fn() =>  $content, 'meetings.doc', ['Content-Type' => 'application/msword']);
        }
        $this->showExportModal = false;
    }

    public function downloadTemplate()
    {
        $headers = ['Title', 'Number', 'Meeting Type ID', 'Date', 'Time', 'Is Last', 'Director ID', 'Registrar ID', 'VC ID'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };
        return response()->stream($callback, 200, ["Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=minutes_template.csv"]);
    }

    public function import()
    {
        $maxSize = \App\Models\Setting::get('max_upload_size', 10240);
        $this->validate(['file' => "required|mimes:csv,txt|max:$maxSize"]);
        $path = $this->file->getRealPath();
        $file = fopen($path, 'r');
        if ($this->hasHeader) fgetcsv($file);
        while (($row = fgetcsv($file)) !== false) {
            try {
                Meeting::create([
                    'title' => $row[0], 
                    'number' => $row[1], 
                    'meeting_type_id' => $row[2], 
                    'date' => $row[3],
                    'time' => $row[4], 
                    'is_last' => strtolower($row[5]) === 'yes' || $row[5] == '1',
                    'director_id' => $row[6],
                    'registrar_id' => $row[7],
                    'vc_id' => $row[8], 
                    'entry_by_id' => auth()->id(),
                ]);
            } catch (\Exception $e) {}
        }
        fclose($file);
        $this->success('Import successful.');
        $this->showImportModal = false;
        $this->resetPage();
    }

    // --- Table Configuration ---
    public function with(): array
    {
        return [
            'meetings' => $this->getMeetingsQuery()->paginate($this->perPage),
            'headers' => [
                ['key' => 'title', 'label' => 'MEETING DETAILS', 'class' => 'w-5/12'],
                ['key' => 'date', 'label' => 'SCHEDULE', 'class' => 'w-2/12 hidden md:table-cell'], 
                ['key' => 'participants', 'label' => 'KEY PEOPLE', 'class' => 'w-2/12 hidden lg:table-cell', 'sortable' => false],
                ['key' => 'is_last', 'label' => 'LAST MEETING', 'class' => 'w-1/12 text-center'],
                ['key' => 'actions', 'label' => '', 'class' => 'w-1/12 text-right'],
            ]
        ];
    }
}; ?>

<div class="p-4 md:p-4 max-w-7xl mx-auto">
    @php
        $hasActiveFilters = $filterMeetingType || $filterDateStart || $filterDateEnd || $filterStatus !== 'all' || $filterTimeframe !== 'all' || $filterKeyPerson || $showDeleted;
        $timeframeLabelMap = collect($timeframeOptions)->pluck('name', 'id');
        $statusLabelMap = collect($statusOptions)->pluck('name', 'id');
        $meetingTypeLabelMap = collect($meetingTypes)->pluck('name', 'id');
        $keyPeopleLabelMap = collect($keyPeopleOptions)->pluck('name', 'id');
        $activeFilterBadges = [];

        if ($filterMeetingType) {
            $activeFilterBadges[] = 'Type: ' . ($meetingTypeLabelMap[$filterMeetingType] ?? 'Unknown');
        }

        if ($filterStatus !== 'all') {
            $activeFilterBadges[] = 'Status: ' . ($statusLabelMap[$filterStatus] ?? ucfirst($filterStatus));
        }

        if ($filterKeyPerson) {
            $activeFilterBadges[] = 'Key Person: ' . ($keyPeopleLabelMap[$filterKeyPerson] ?? 'Unknown');
        }

        if (trim($search) !== '') {
            $activeFilterBadges[] = 'Search: "' . $search . '"';
        }

        if ($filterTimeframe === 'custom') {
            if ($filterDateStart || $filterDateEnd) {
                $rangeStart = $filterDateStart ? Carbon::parse($filterDateStart)->format('M d, Y') : 'Any';
                $rangeEnd = $filterDateEnd ? Carbon::parse($filterDateEnd)->format('M d, Y') : 'Any';
                $activeFilterBadges[] = 'Dates: ' . $rangeStart . ' - ' . $rangeEnd;
            }
        } elseif ($filterTimeframe !== 'all') {
            $activeFilterBadges[] = 'Dates: ' . ($timeframeLabelMap[$filterTimeframe] ?? ucfirst(str_replace('-', ' ', $filterTimeframe)));
        }

        if ($showDeleted) {
            $activeFilterBadges[] = 'Trash: Showing deleted';
        }
    @endphp

    {{-- HEADER --}}
    <x-mary-header  title="Meetings Management" subtitle="Manage schedules, agendas, and minutes." separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input 
                icon="o-magnifying-glass" 
                placeholder="Search meetings..." 
                wire:model.live.debounce="search" 
                class="border-base-300 focus:border-primary w-full md:w-80"
            />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-funnel" wire:click="$toggle('drawer')" class="btn-ghost" badge="{{ $hasActiveFilters ? '!' : '' }}" tooltip="{{ $hasActiveFilters ? 'Filters applied' : 'Filter' }}" />
            
            <div class="flex items-center gap-2">
                 {{-- Bulk Actions Dropdown --}}
                <x-mary-dropdown icon="o-ellipsis-horizontal" class="btn-ghost dropdown-end">
                    <x-mary-menu-item title="Export Data" icon="o-arrow-up-tray" @click="$wire.showExportModal = true" />
                    <x-mary-menu-item title="Import CSV" icon="o-arrow-down-tray" @click="$wire.showImportModal = true" />
                    <x-mary-menu-separator />
                    <x-mary-menu-item title="Download Template" icon="o-document-arrow-down" wire:click="downloadTemplate" />
                </x-mary-dropdown>

                @can('create meetings')
                    <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="New Meeting" responsive />
                @endcan
            </div>
        </x-slot:actions>
    </x-mary-header>

    {{-- FILTER DRAWER --}}
    <x-mary-drawer wire:model="drawer" title="Filter Options" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="grid gap-5 p-4">
            <div class="space-y-3">
                <x-mary-select
                    label="Date Range"
                    wire:model.live="filterTimeframe"
                    :options="$timeframeOptions"
                    option-label="name"
                    option-value="id"
                    placeholder="Select timeframe"
                    icon="o-calendar-days"
                />

                @if($filterTimeframe === 'custom')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-mary-datetime label="Start Date" wire:model.live="filterDateStart" icon="o-calendar" />
                        <x-mary-datetime label="End Date" wire:model.live="filterDateEnd" icon="o-calendar" />
                    </div>
                @else
                    <div class="text-xs text-gray-500 bg-base-200 px-3 py-2 rounded-lg">
                        Select "Custom Range" to set specific dates.
                    </div>
                @endif
            </div>

            <x-mary-select label="Meeting Type" wire:model.live="filterMeetingType" :options="$meetingTypes" option-label="name" option-value="id" placeholder="All types" icon="o-tag" />

            <x-mary-select label="Meeting Status" wire:model.live="filterStatus" :options="$statusOptions" option-label="name" option-value="id" placeholder="All statuses" icon="o-adjustments-horizontal" />

            <x-mary-select label="Key Participant" wire:model.live="filterKeyPerson" :options="$keyPeopleOptions" option-label="name" option-value="id" placeholder="Any key person" icon="o-user-group" searchable />

            <div class="p-4 bg-base-200 rounded-lg space-y-3">
                <x-mary-toggle label="Show Deleted Items" wire:model.live="showDeleted" class="toggle-error" right />
                <x-mary-button label="Reset Filters" icon="o-arrow-path" class="btn-outline w-full" wire:click="resetFilters" />
            </div>
        </div>
    </x-mary-drawer>

    @if(count($activeFilterBadges))
        <div class="flex flex-wrap items-center gap-2 mb-4 p-3 bg-base-200/60 border border-base-200 rounded-2xl">
            <span class="text-xs font-semibold uppercase tracking-widest text-gray-500">Active Filters</span>
            @foreach($activeFilterBadges as $filterBadge)
                <x-mary-badge :value="$filterBadge" class="badge-outline badge-sm px-3 py-2" />
            @endforeach
            <x-mary-button icon="o-x-mark" label="Clear" class="btn-ghost btn-xs" wire:click="resetFilters" />
        </div>
    @endif

    {{-- MAIN TABLE --}}
    <x-mary-card shadow class="rounded-2xl bg-base-100 overflow-visible">
        @if($meetings->count() > 0)
            <x-mary-table 
                :headers="$headers" 
                :rows="$meetings" 
                striped 
                @row-click="$wire.view($event.detail.row.id)" 
                class="hover-row-cursor overflow-visible"
            >
                
                {{-- 1. Rich Title Column --}}
                @scope('cell_title', $meeting)
                    <div class="flex items-start gap-3 py-2">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <x-mary-icon name="o-calendar" class="w-5 h-5" />
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-base text-base-content leading-tight">{{ $meeting->title }}</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="font-mono text-xs text-gray-400 bg-base-200 px-1.5 rounded">#{{ $meeting->number }}</span>
                                <x-mary-badge :value="$meeting->meetingType->name ?? 'N/A'" class="badge-ghost badge-sm text-xs" />
                            </div>
                        </div>
                    </div>
                @endscope

                {{-- 2. Schedule Column --}}
                @scope('cell_date', $meeting)
                    <div class="flex flex-col text-sm">
                        <span class="font-semibold">{{ $meeting->date ? $meeting->date->format($this->dateFormat) : '-' }}</span>
                        <span class="text-xs text-gray-500">{{ $meeting->time ? $meeting->time->format($this->timeFormat) : '' }}</span>
                    </div>
                @endscope

                {{-- 3. Participants Column --}}
                @scope('cell_participants', $meeting)
                    <div class="avatar-group -space-x-4 rtl:space-x-reverse">
                        @if($meeting->director_id)
                            <div class="tooltip avatar placeholder border-2 border-base-100 cursor-help" data-tip="Director: {{ $meeting->director->name ?? 'N/A' }}">
                                <div class="bg-indigo-500 text-white w-8"><span>D</span></div>
                            </div>
                        @endif
                        @if($meeting->registrar_id)
                            <div class="tooltip avatar placeholder border-2 border-base-100 cursor-help" data-tip="Registrar: {{ $meeting->registrar->name ?? 'N/A' }}">
                                <div class="bg-emerald-500 text-white w-8"><span>R</span></div>
                            </div>
                        @endif
                        @if($meeting->vc_id)
                            <div class="tooltip avatar placeholder border-2 border-base-100 cursor-help" data-tip="VC: {{ $meeting->vc->name ?? 'N/A' }}">
                                <div class="bg-purple-500 text-white w-8"><span>V</span></div>
                            </div>
                        @endif
                        @if(!$meeting->director_id && !$meeting->registrar_id && !$meeting->vc_id)
                            <span class="text-xs text-gray-400 italic">No assignments</span>
                        @endif
                    </div>
                @endscope

                {{-- 4. Status Column --}}
                @scope('cell_is_last', $meeting)
                    <div 
                        wire:click.stop="toggleStatus({{ $meeting->id }})" 
                        class="flex justify-center cursor-pointer select-none group tooltip"
                        data-tip="Toggle Status"
                    >
                        @if($meeting->is_last)
                            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-200 shadow-sm transition-all group-hover:shadow-md group-hover:bg-blue-100">
                                <div class="relative flex items-center justify-center w-2 h-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-blue-500"></span>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest">Yes</span>
                            </div>
                        @else
                            <div class="flex items-center justify-center w-10 py-1 rounded-md bg-gray-100 text-gray-400 border border-gray-200 transition-all group-hover:bg-gray-200 group-hover:border-gray-300 group-hover:text-gray-600">
                                <span class="text-xs font-bold tracking-widest">No</span>
                            </div>
                        @endif
                    </div>
                @endscope

                {{-- 5. Actions Column --}}
                @scope('actions', $meeting)
                    <div class="flex justify-end">
                        @if($meeting->trashed())
                            <x-mary-button icon="o-arrow-path" wire:click.stop="restore({{ $meeting->id }})" class="btn-sm btn-ghost text-success" tooltip="Restore" />
                        @else
                            <x-mary-dropdown icon="o-ellipsis-vertical" class="btn-sm btn-ghost" no-x-anchor right>
                                <div class="px-3 py-1 text-xs font-bold text-gray-400 uppercase">Manage</div>
                                <x-mary-menu-item title="View Details" icon="o-eye" wire:click.stop="view({{ $meeting->id }})" />
                                
                                @can('edit meetings')
                                    <x-mary-menu-item title="Edit Details" icon="o-pencil" wire:click.stop="edit({{ $meeting->id }})" />
                                @endcan
                                
                                @if($meeting->agenda_items_count > 0 || $meeting->minutes_count > 0)
                                    <div class="divider my-1"></div>
                                    <div class="px-3 py-1 text-xs font-bold text-gray-400 uppercase">Documents</div>
                                    @if($meeting->agenda_items_count > 0)
                                        <x-mary-menu-item title="Agenda" icon="o-calendar-days" wire:click.stop="openOptions({{ $meeting->id }}, 'agenda')" />
                                    @endif
                                    @if($meeting->minutes_count > 0)
                                        <x-mary-menu-item title="Minutes" icon="o-clipboard-document-check" wire:click.stop="openOptions({{ $meeting->id }}, 'minutes')" />
                                    @endif
                                @endif
                                
                                @can('delete meetings')
                                    <div class="divider my-1"></div>
                                    <x-mary-menu-item title="Delete" icon="o-trash" wire:click.stop="confirmDelete({{ $meeting->id }})" class="text-error" />
                                @endcan
                            </x-mary-dropdown>
                        @endif
                    </div>
                @endscope
            </x-mary-table>
            
            <div class="mt-4">
                {{ $meetings->links() }}
            </div>
        @else
            {{-- Empty State (No changes needed) --}}
            <div class="flex flex-col items-center justify-center py-16">
                <div class="bg-base-200 rounded-full p-4 mb-4">
                    <x-mary-icon name="o-calendar" class="w-8 h-8 text-gray-400" />
                </div>
                <div class="text-lg font-bold text-gray-600">No meetings found</div>
                <div class="text-sm text-gray-400 mb-6">Create a new meeting or adjust your filters.</div>
                @can('create meetings')
                    <x-mary-button label="Create Meeting" icon="o-plus" class="btn-primary" wire:click="create" />
                @endcan
            </div>
        @endif
    </x-mary-card>

    {{-- MODAL: VIEW / CREATE / EDIT --}}
    <x-mary-modal wire:model="showModal" class="backdrop-blur-md" box-class="w-11/12 max-w-4xl bg-base-100 shadow-2xl">
        @if($viewMode)
            {{-- VIEW MODE UI --}}
            <div class="relative">
                {{-- Header --}}
                <div class="flex justify-between items-start border-b border-base-200 pb-6 mb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <x-mary-badge :value="$meetingTypes->firstWhere('id', $meeting_type_id)?->name ?? 'Meeting'" class="badge-primary badge-outline" />
                            <span class="text-gray-400 text-sm font-mono">#{{ $number }}</span>
                        </div>
                        <h2 class="text-3xl font-black text-base-content">{{ $title }}</h2>
                    </div>
                    @if($is_last) <x-mary-badge value="Final Meeting" class="badge-warning font-bold" /> @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    {{-- Logistics Column --}}
                    <div class="md:col-span-1 space-y-4">
                        <div class="bg-base-200/50 p-5 rounded-2xl border border-base-200">
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Logistics</div>
                            <div class="space-y-4">
                                <div>
                                    <div class="text-sm text-gray-500 mb-1">Date</div>
                                    <div class="font-bold text-lg flex items-center gap-2">
                                        <x-mary-icon name="o-calendar" class="w-5 h-5 text-primary" />
                                        {{ $date ? \Carbon\Carbon::parse($date)->format($this->dateFormat) : '-' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500 mb-1">Time</div>
                                    <div class="font-bold text-lg flex items-center gap-2">
                                        <x-mary-icon name="o-clock" class="w-5 h-5 text-primary" />
                                        {{ $time ? \Carbon\Carbon::parse($time)->format($this->timeFormat) : '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-base-200/50 p-5 rounded-2xl border border-base-200">
                             <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Downloads</div>
                             <div class="flex flex-col gap-2">
                                @if($agenda_items_count > 0)
                                    <x-mary-button label="Agenda" icon="o-calendar-days" class="btn-sm btn-ghost justify-start" wire:click="openOptions({{ $id }}, 'agenda')" />
                                @endif
                                @if($minutes_count > 0)
                                    <x-mary-button label="Minutes" icon="o-clipboard-document-check" class="btn-sm btn-ghost justify-start" wire:click="openOptions({{ $id }}, 'minutes')" />
                                @endif
                             </div>
                        </div>
                    </div>

                    {{-- Participants Column --}}
                    <div class="md:col-span-2">
                         <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Key Stakeholders</div>
                         <div class="grid gap-4">
                             {{-- Custom Card for Person --}}
                             @foreach([
                                 ['role' => 'Director', 'color' => 'indigo', 'user' => $director],
                                 ['role' => 'Registrar', 'color' => 'emerald', 'user' => $registrar],
                                 ['role' => 'Vice Chancellor', 'color' => 'purple', 'user' => $vc]
                             ] as $person)
                                <div class="flex items-center gap-4 p-4 bg-base-100 border border-base-200 rounded-xl hover:shadow-md transition duration-300">
                                    <div class="w-12 h-12 rounded-full bg-{{ $person['color'] }}-100 text-{{ $person['color'] }}-600 flex items-center justify-center font-bold text-lg">
                                        {{ substr($person['role'], 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-xs text-{{ $person['color'] }}-500 font-bold uppercase tracking-wider">{{ $person['role'] }}</div>
                                        <div class="font-bold text-lg">{{ $person['user']->name ?? 'Not Assigned' }}</div>
                                        <div class="text-xs text-gray-400">{{ $person['user']->email ?? '' }}</div>
                                    </div>
                                </div>
                             @endforeach
                         </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-2 pt-6 border-t border-base-200">
                    <x-mary-button label="Close" class="btn-ghost" @click="$wire.showModal = false" />
                    @can('edit meetings')
                        <x-mary-button label="Edit Meeting" icon="o-pencil" class="btn-primary" wire:click="edit({{ $id }})" />
                    @endcan
                </div>
            </div>
        @else
            {{-- FORM UI --}}
            <x-mary-header :title="$editMode ? 'Edit Meeting' : 'Create New Meeting'" separator />

            <x-mary-form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
                    <div class="md:col-span-8">
                        <x-mary-input label="Meeting Title" wire:model="title" placeholder="Meeting Title e.g. Annual Board Review" icon="o-bookmark" inline />
                    </div>
                    <div class="md:col-span-4">
                        <x-mary-input label="Meeting Number" wire:model="number" prefix="#" placeholder="Meeting Number, e.g. 101" inline />
                    </div>
                </div>

                <div class="bg-base-200/50 rounded-xl border border-base-200">
                    <div class="text-sm font-bold text-gray-500 uppercase tracking-wide pb-5">Schedule & Type</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3 md:mb-3">
                        <div class="grid grid-cols-1 md:grid-cols-1">
                            <x-mary-select label="Type" wire:model="meeting_type_id" :options="$creatableMeetingTypes" option-label="name" option-value="id" placeholder="Select Type"  inline />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <x-mary-datetime label="Date" wire:model="date"  inline />
                            <x-mary-datetime label="Time" wire:model="time" icon="o-calendar" type="time" inline />
                            <div class="mt-4 flex items-center">
                                <x-mary-checkbox label="Is Current" wire:model="is_last" hint="Is it the latest meeting?" left />
                            </div>
                        </div>
                    </div>
                    
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <x-mary-select label="Director" wire:model="director_id" :options="$directors" option-label="name" option-value="id" placeholder="Search..." searchable inline />
                    <x-mary-select label="Registrar" wire:model="registrar_id" :options="$registrars" option-label="name" option-value="id" placeholder="Search..." searchable inline />
                    <x-mary-select label="Vice Chancellor" wire:model="vc_id" :options="$vcs" option-label="name" option-value="id" placeholder="Search..." searchable inline />
                </div>

                <div class="bg-base-200/50 rounded-xl border border-base-200">
                    <div class="flex justify-between items-center mb-2">
                        <div class="text-sm font-bold text-gray-500 uppercase tracking-wide">Participants Management</div>
                        <x-mary-button label="Add New Person" icon="o-plus" class="btn-xs btn-ghost" wire:click="openParticipantModal" inline />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 border-b border-base-300">
                                <x-mary-icon name="o-users" class="w-5 h-5 text-primary" />
                                <span class="font-bold text-base-content">Members</span>
                                <span class="text-xs text-gray-500">(Committee / Regulars)</span>
                            </div>
                            
                            <x-mary-choices 
                                wire:model="members" 
                                :options="$allParticipants" 
                                option-label="name" 
                                option-value="id" 
                                icon="o-user-group"
                                searchable 
                            />
                        </div>

                        {{-- Attendees Column --}}
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 border-b border-base-300">
                                <x-mary-icon name="o-ticket" class="w-5 h-5 text-secondary" />
                                <span class="font-bold text-base-content">Attendees</span>
                                <span class="text-xs text-gray-500">(Guests / One-time)</span>
                            </div>

                            <x-mary-choices 
                                wire:model="attendees" 
                                :options="$allParticipants" 
                                option-label="name" 
                                option-value="id" 
                                icon="o-user"
                                searchable 
                            />
                        </div>
                    </div>
                </div>

                <x-slot:actions>
                    <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                    <x-mary-button label="{{ $editMode ? 'Update' : 'Create' }}" class="btn-primary" type="submit" spinner="save" icon="o-check" />
                </x-slot:actions>
            </x-mary-form>
        @endif
    </x-mary-modal>

    {{-- OPTIONS MODAL --}}
    <x-mary-modal wire:model="showOptionsModal" :title="$optionsTitle" class="backdrop-blur-sm">
        <div class="grid gap-2">
            @if($optionsType === 'agenda')
                <x-mary-button label="View Online" icon="o-eye" link="{{ route('meetings.agenda.view', $selectedMeetingId) }}" external target="_blank" class="btn-ghost justify-start" />
                <x-mary-button label="Download PDF" icon="o-document-text" link="{{ route('meetings.agenda.download', ['id' => $selectedMeetingId, 'format' => 'pdf']) }}" external class="btn-ghost justify-start" />
                <x-mary-button label="Download DOCX" icon="o-document" link="{{ route('meetings.agenda.download', ['id' => $selectedMeetingId, 'format' => 'docx']) }}" external class="btn-ghost justify-start" />
            @elseif($optionsType === 'minutes')
                <x-mary-button label="View Online" icon="o-eye" link="{{ route('meetings.minutes.view', $selectedMeetingId) }}" external target="_blank" class="btn-ghost justify-start" />
                <x-mary-button label="Download PDF" icon="o-document-text" link="{{ route('meetings.minutes.download', ['id' => $selectedMeetingId, 'format' => 'pdf']) }}" external class="btn-ghost justify-start" />
                <x-mary-button label="Download DOCX" icon="o-document" link="{{ route('meetings.minutes.download', ['id' => $selectedMeetingId, 'format' => 'docx']) }}" external class="btn-ghost justify-start" />
            @endif
        </div>
        <x-slot:actions>
            <x-mary-button label="Close" @click="$wire.showOptionsModal = false" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- EXPORT MODAL --}}
    <x-mary-modal wire:model="showExportModal" title="Export Data" class="backdrop-blur-sm" box-class="max-w-sm">
        <div class="text-sm text-gray-500 mb-4">Select format to export the current list.</div>
        <div class="grid gap-2">
            <x-mary-button label="Export as PDF" icon="o-document-text" class="btn-outline justify-start" wire:click="export('pdf')" spinner />
            <x-mary-button label="Export as CSV" icon="o-table-cells" class="btn-outline justify-start" wire:click="export('csv')" spinner />
            <x-mary-button label="Export as Word" icon="o-document" class="btn-outline justify-start" wire:click="export('docx')" spinner />
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showExportModal = false" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- DELETE MODAL --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Meeting" class="backdrop-blur-sm">
        <div class="text-center p-4">
            <div class="bg-red-50 text-red-500 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6" />
            </div>
            <div class="font-bold text-lg">Delete this meeting?</div>
            <div class="text-gray-500 mt-1">This item will be moved to trash.</div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>

    {{-- CREATE PARTICIPANT MODAL --}}
    <x-mary-modal wire:model="showParticipantModal" title="Add New Participant" class="backdrop-blur-sm">
        <div class="grid gap-4">
            <x-mary-input label="Name" wire:model="newParticipantName" placeholder="Full Name" />
            <x-mary-input label="Email" wire:model="newParticipantEmail" placeholder="Email Address" />
            <x-mary-input label="Designation" wire:model="newParticipantDesignation" placeholder="e.g. Professor" />
            <x-mary-input label="Organization" wire:model="newParticipantOrganization" placeholder="e.g. University of X" />
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showParticipantModal = false" />
            <x-mary-button label="Save" class="btn-primary" wire:click="saveParticipant" spinner="saveParticipant" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- IMPORT MODAL --}}
    <x-mary-modal wire:model="showImportModal" title="Import Meetings" class="backdrop-blur-sm">
        <div class="bg-blue-50 text-blue-800 p-4 rounded-lg mb-4 text-sm flex gap-2">
            <x-mary-icon name="o-information-circle" class="w-5 h-5 shrink-0" />
            <span>Use the CSV template to ensure correct formatting.</span>
        </div>
        <x-mary-file wire:model="file" label="Upload CSV" accept=".csv" />
        <div class="mt-2">
             <x-mary-checkbox label="File contains header row" wire:model="hasHeader" />
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showImportModal = false" />
            <x-mary-button label="Import" wire:click="import" class="btn-primary" spinner="import" />
        </x-slot:actions>
    </x-mary-modal>
</div>