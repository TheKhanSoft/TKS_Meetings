<?php

use App\Models\AgendaItem;
use App\Models\AgendaItemType;
use App\Models\Meeting;
use App\Models\User;
use App\Models\Keyword;
use App\Http\Requests\AgendaItemRequest;
use App\Services\AgendaItemService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use Toast, WithFileUploads;

    public $agendaItems;
    public string $search = '';
    public $meetings;
    public $agendaItemTypes;
    public $users;
    public $keywords = [];
    public $selectedKeywords = [];
    public array $selected = [];
    
    // UI State
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public bool $showExportModal = false;
    public bool $showImportModal = false;
    public bool $drawer = false;
    public bool $editMode = false;
    public bool $viewMode = false;
    
    // Filters
    public bool $showDeleted = false;
    public $filterMeeting = '';
    public $filterType = '';
    public $filterStatus = '';
    public $filterLeftOver = null;

    // Form Fields
    public $id;
    public $meeting_id;
    public $agenda_item_type_id;
    public $sequence_number;
    public $title;
    public $details = ''; 
    public $owner_user_id;
    public $discussion_status = 'pending';
    public $is_left_over = false;

    // Actions
    public $itemToDeleteId;
    public $file;
    public bool $hasHeader = true;

    public function mount(AgendaItemService $service)
    {
        $this->authorize('viewAny', AgendaItem::class);

        $this->meetings = Meeting::all();
        $this->agendaItemTypes = AgendaItemType::where('is_active', true)->get();
        $this->users = User::all();
        $this->keywords = Keyword::orderBy('name')->get();
        $this->loadAgendaItems($service);

        if (request()->has('view')) {
            $item = AgendaItem::find(request('view'));
            if ($item) {
                $this->view($item);
            }
        }
    }

    public function loadAgendaItems(AgendaItemService $service)
    {
        $query = AgendaItem::query()->with(['meeting', 'agendaItemType', 'owner']);

        if ($this->search) $query->where('title', 'like', '%' . $this->search . '%');
        if ($this->showDeleted) $query->onlyTrashed();
        if ($this->filterMeeting) $query->where('meeting_id', $this->filterMeeting);
        if ($this->filterType) $query->where('agenda_item_type_id', $this->filterType);
        if ($this->filterStatus) $query->where('discussion_status', $this->filterStatus);
        if ($this->filterLeftOver !== null && $this->filterLeftOver !== '') $query->where('is_left_over', $this->filterLeftOver);

        $this->agendaItems = 
            $query
                ->orderBy('meeting_id', 'desc')
                ->orderBy('agenda_item_type_id', 'asc')
                ->orderBy('sequence_number', 'asc')
                ->get();
    }

    public function searchKeywords($value = '')
    {
        $selected = Keyword::whereIn('id', $this->selectedKeywords)->get();

        $query = Keyword::query();

        if (!empty($value)) {
            $query->where('name', 'like', "%{$value}%");
        }

        $results = $query->orderBy('name')->take(20)->get();
        
        // Merge selected keywords to ensure they are always in the list
        $this->keywords = $selected->merge($results)->toBase();

        // Check for exact match
        $exactMatch = $this->keywords->contains(function ($k) use ($value) {
            return strcasecmp($k['name'], $value) === 0;
        });

        if (!empty($value) && !$exactMatch) {
            $this->keywords->push([
                'id' => "new:{$value}",
                'name' => "Create \"$value\"",
            ]);
        }
    }

    // --- Live Updates ---
    public function updatedSearch() { $this->loadAgendaItems(app(AgendaItemService::class)); }
    public function updatedShowDeleted() { $this->loadAgendaItems(app(AgendaItemService::class)); }
    public function updatedFilterMeeting() { $this->loadAgendaItems(app(AgendaItemService::class)); }
    public function updatedFilterType() { $this->loadAgendaItems(app(AgendaItemService::class)); }
    public function updatedFilterStatus() { $this->loadAgendaItems(app(AgendaItemService::class)); }
    public function updatedFilterLeftOver() { $this->loadAgendaItems(app(AgendaItemService::class)); }

    public function create()
    {
        $this->authorize('create', AgendaItem::class);

        $this->reset(['id', 'meeting_id', 'agenda_item_type_id', 'sequence_number', 'title', 'details', 'owner_user_id', 'discussion_status', 'is_left_over', 'selectedKeywords']);
        $this->discussion_status = 'pending';
        // Auto-suggest next sequence if creating
        $this->sequence_number = AgendaItem::max('sequence_number') + 1; 
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function edit(AgendaItem $agendaItem)
    {
        $this->authorize('update', $agendaItem);

        $this->fillForm($agendaItem);
    }

    public function view(AgendaItem $agendaItem)
    {
        $this->fillForm($agendaItem);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function fillForm(AgendaItem $agendaItem)
    {
        $this->id = $agendaItem->id;
        $this->meeting_id = $agendaItem->meeting_id;
        $this->agenda_item_type_id = $agendaItem->agenda_item_type_id;
        $this->sequence_number = $agendaItem->sequence_number;
        $this->title = $agendaItem->title;
        $this->details = $agendaItem->details;
        $this->owner_user_id = $agendaItem->owner_user_id;
        $this->discussion_status = $agendaItem->discussion_status;
        $this->is_left_over = $agendaItem->is_left_over;
        $this->selectedKeywords = $agendaItem->keywords->pluck('id')->toArray();
    }

    public function save(AgendaItemService $service)
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $validated = $this->validate((new AgendaItemRequest())->rules());

        // Process keywords (create if new)
        $keywordIds = [];
        foreach ($this->selectedKeywords as $keyword) {
            if (Str::startsWith($keyword, 'new:')) {
                $name = Str::after($keyword, 'new:');
                $newKeyword = Keyword::firstOrCreate(['name' => $name]);
                $keywordIds[] = $newKeyword->id;
            } else {
                $keywordIds[] = $keyword;
            }
        }
        // Refresh keywords list for UI
        $this->keywords = Keyword::orderBy('name')->get();

        if ($this->editMode) {
            $agendaItem = AgendaItem::find($this->id);
            $service->updateAgendaItem($agendaItem, $validated);
            $agendaItem->keywords()->sync($keywordIds);
            $this->success('Item updated successfully.');
        } else {
            $agendaItem = $service->createAgendaItem($validated);
            $agendaItem->keywords()->sync($keywordIds);
            $this->success('Item created successfully.');
        }

        $this->showModal = false;
        $this->loadAgendaItems($service);
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', AgendaItem::find($id));

        $this->itemToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(AgendaItemService $service)
    {
        $item = AgendaItem::find($this->itemToDeleteId);
        $this->authorize('delete', $item);
        $service->deleteAgendaItem($item);
        $this->success('Item deleted.');
        $this->showDeleteModal = false;
        $this->loadAgendaItems($service);
    }

    public function restore($id)
    {
        $item = AgendaItem::withTrashed()->find($id);
        $this->authorize('restore', $item);
        $item->restore();
        $this->success('Item restored.');
        $this->loadAgendaItems(app(AgendaItemService::class));
    }

    public function toggleStatus($id)
    {
        $agendaItem = AgendaItem::find($id);
        $this->authorize('update', $agendaItem);
        $agendaItem->is_left_over = !$agendaItem->is_left_over;
        $agendaItem->save();
    }

    public function bulkDelete()
    {   $items = AgendaItem::whereIn('id', $this->selected)->get();
        foreach ($items as $item) {
            $this->authorize('delete', $item);
        }

        AgendaItem::whereIn('id', $this->selected)->delete();
        $this->success('Selected items deleted.');
        $this->selected = [];
        $this->loadAgendaItems(app(AgendaItemService::class));
    }

    public function bulkRestore()
    {
        $items = AgendaItem::withTrashed()->whereIn('id', $this->selected)->get();
        foreach ($items as $item) {
            $this->authorize('restore', $item);
        }

        AgendaItem::withTrashed()->whereIn('id', $this->selected)->restore();
        $this->success('Selected items restored.');
        $this->selected = [];
        $this->loadAgendaItems(app(AgendaItemService::class));
    }

    public function bulkStatus($status)
    {
        $items = AgendaItem::whereIn('id', $this->selected)->get();
        foreach ($items as $item) {
            $this->authorize('update', $item);
        }

        AgendaItem::whereIn('id', $this->selected)->update(['discussion_status' => $status]);
        $this->success('Status updated for selected items.');
        $this->selected = [];
        $this->loadAgendaItems(app(AgendaItemService::class));
    }
    // (Kept Export/Import/Download for brevity - they remain unchanged)
    public function export($format = 'pdf')
    {
        $headers = ['Title', 'Meeting', 'Type', 'Status', 'Sequence', 'Owner', 'Details'];
        $data = [];
        
        foreach ($this->agendaItems as $item) {
            $data[] = [
                $item->title,
                $item->meeting->title ?? '',
                $item->agendaItemType->name ?? '',
                ucfirst($item->discussion_status),
                $item->sequence_number,
                $item->owner->name ?? '',
                strip_tags($item->details)
            ];
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', ['title' => 'Agenda Items', 'headers' => $headers, 'rows' => $data]);
            return response()->streamDownload(fn() => print($pdf->output()), 'agenda-items-' . date('Y-m-d') . '.pdf');
        } elseif ($format === 'csv') {
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
                "Content-Disposition" => "attachment; filename=agenda-items-" . date('Y-m-d') . ".csv",
            ]);
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Agenda Items List', ['size' => 16, 'bold' => true]);
            $table = $section->addTable(['borderSize' => 6]);
            $table->addRow();
            foreach ($headers as $header) $table->addCell(2000)->addText($header, ['bold' => true]);
            foreach ($data as $row) {
                $table->addRow();
                foreach ($row as $cell) $table->addCell(2000)->addText($cell);
            }
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            return response()->streamDownload(fn() => $objWriter->save('php://output'), 'agenda-items-' . date('Y-m-d') . '.docx');
        } elseif ($format === 'doc') {
             $content = view('exports.pdf', ['title' => 'Agenda Items', 'headers' => $headers, 'rows' => $data])->render();
            return response()->streamDownload(fn() =>  print($content), 'agenda-items-' . date('Y-m-d') . '.doc', ['Content-Type' => 'application/msword']);
        }
        
        $this->showExportModal = false;
    }

    public function downloadTemplate()
    {
        $headers = ['Meeting ID', 'Agenda Item Type ID', 'Sequence Number', 'Status', 'Title', 'Details', 'Owner User ID', 'Is Left Over (Yes/No)', 'Keywords (semicolon separated)'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['1', '1', '1', 'pending', 'Sample Title', 'Details here...', '1', 'No', 'Budget;Planning']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            "Content-type" => "text/csv", 
            "Content-Disposition" => "attachment; filename=agenda_items_template.csv"
        ]);
    }

    public function import()
    {
        $maxSize = \App\Models\Setting::get('max_upload_size', 10240);
        $this->validate(['file' => "required|mimes:csv,txt|max:$maxSize"]);
        
        $path = $this->file->getRealPath();
        $file = fopen($path, 'r');
        
        if ($this->hasHeader) {
            fgetcsv($file);
        }
        
        $count = 0;
        $errors = 0;
        $firstError = '';

        while (($row = fgetcsv($file)) !== false) {
            try {
                // Basic validation/sanitization could go here
                if (count($row) < 5) continue;

                $agendaItem = AgendaItem::create([
                    'meeting_id' => $row[0],
                    'agenda_item_type_id' => $row[1],
                    'sequence_number' => !empty($row[2]) ? $row[2] : 0,
                    'discussion_status' => !empty($row[3]) ? $row[3] : 'pending',
                    'title' => $row[4],
                    'details' => $row[5] ?? '',
                    'owner_user_id' => !empty($row[6]) ? $row[6] : null,
                    'is_left_over' => isset($row[7]) && (strtolower($row[7]) === 'yes' || $row[7] == '1'),
                ]);

                if (!empty($row[8])) {
                    $keywords = array_filter(array_map('trim', explode(';', $row[8])));
                    $keywordIds = [];
                    foreach ($keywords as $keywordName) {
                        $keyword = Keyword::firstOrCreate(['name' => $keywordName]);
                        $keywordIds[] = $keyword->id;
                    }
                    $agendaItem->keywords()->sync($keywordIds);
                }

                $count++;
            } catch (\Exception $e) {
                $errors++;
                if ($errors === 1) {
                    $firstError = $e->getMessage();
                }
                Log::error("Import Error: " . $e->getMessage());
            }
        }
        
        fclose($file);
        
        if ($errors > 0) {
            $this->warning("Imported $count items. $errors items failed. First error: " . Str::limit($firstError, 100));
        } else {
            $this->success("Imported $count items successfully.");
        }
        
        $this->showImportModal = false;
        $this->loadAgendaItems(app(AgendaItemService::class));
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'title', 'label' => 'AGENDA TOPIC', 'class' => 'w-6/12'],
                ['key' => 'meeting_context', 'label' => 'MEETING & TYPE', 'class' => 'w-4/12 hidden md:table-cell'],
                ['key' => 'status', 'label' => 'STATUS', 'class' => 'text-center'],
                ['key' => 'actions', 'label' => '', 'class' => 'text-right'],
            ],
            'statuses' => [
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'discussed', 'name' => 'Discussed'],
                ['id' => 'approved', 'name' => 'Approved'],
                ['id' => 'rejected', 'name' => 'Rejected'],
                ['id' => 'deferred', 'name' => 'Deferred'],
                ['id' => 'withdrawn', 'name' => 'Withdrawn'],
            ]
        ];
    }
}; ?>

<div class="p-4 md:p-8 max-w-7xl mx-auto">
    {{-- HEADER --}}
    <x-mary-header title="Agenda Items" subtitle="Manage meeting topics." separator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-arrow-up-tray" class="btn-ghost" @click="$wire.showExportModal = true" tooltip="Export" />
            <x-mary-button icon="o-arrow-down-tray" class="btn-ghost" @click="$wire.showImportModal = true" tooltip="Import" />
            <x-mary-button icon="o-funnel" wire:click="$toggle('drawer')" class="btn-ghost" tooltip="Filter" />
            
            @can('create agenda items')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="New Item" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- FILTERS DRAWER --}}
    <x-mary-drawer wire:model="drawer" title="Filter Options" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="grid gap-5 p-4">
            <x-mary-select label="Meeting" wire:model.live="filterMeeting" :options="$meetings" option-label="title" option-value="id" placeholder="All" />
            <x-mary-select label="Type" wire:model.live="filterType" :options="$agendaItemTypes" option-label="name" option-value="id" placeholder="All" />
            <x-mary-select label="Status" wire:model.live="filterStatus" :options="$statuses" option-label="name" option-value="id" placeholder="All" />
            <x-mary-button label="Clear Filters" icon="o-x-mark" class="btn-outline" @click="$wire.filterMeeting = ''; $wire.filterType = ''; $wire.filterStatus = '';" />
        </div>
    </x-mary-drawer>

    {{-- MAIN TABLE --}}
    <x-mary-card shadow class="rounded-2xl bg-base-100 overflow-visible">
        
        @if(count($selected) > 0)
            <div class="flex justify-between items-center bg-base-200 p-2 rounded-lg mb-2">
                <div class="font-bold text-sm ml-2">{{ count($selected) }} selected</div>
                <div class="flex gap-2">
                    @if($showDeleted)
                        <x-mary-button label="Restore" icon="o-arrow-path" class="btn-sm btn-success" wire:click="bulkRestore" wire:confirm="Restore selected items?" />
                    @else
                        <x-mary-dropdown label="Status" class="btn-sm">
                            @foreach($statuses as $status)
                                <x-mary-menu-item title="{{ $status['name'] }}" wire:click="bulkStatus('{{ $status['id'] }}')" />
                            @endforeach
                        </x-mary-dropdown>
                        <x-mary-button label="Delete" icon="o-trash" class="btn-sm btn-error" wire:click="bulkDelete" wire:confirm="Delete selected items?" />
                    @endif
                </div>
            </div>
        @endif

        <x-mary-table :headers="$headers" :rows="$agendaItems" striped @row-click="$wire.view($event.detail.row.id)" class="hover-row-cursor overflow-visible" selectable wire:model.live="selected">
            
            @scope('cell_title', $agendaItem)
                <div class="flex flex-col py-1">
                    <span class="font-bold text-base">{{ $agendaItem->title }}</span>
                    <span class="text-xs text-gray-500 truncate max-w-sm">{{ Str::limit(strip_tags($agendaItem->details), 80) }}</span>
                    @if($agendaItem->is_left_over)
                        <span class="badge badge-warning badge-xs mt-1 font-mono uppercase text-[10px]">Leftover</span>
                    @endif
                </div>
            @endscope

            @scope('cell_meeting_context', $agendaItem)
                <div>
                    <div class="font-semibold text-sm">{{ $agendaItem->meeting->title ?? '-' }}</div>
                    <div class="flex gap-2 mt-1">
                        <x-mary-badge :value="'Seq: ' . $agendaItem->sequence_number" class="badge-ghost badge-sm text-xs" />
                        <span class="text-xs text-gray-400">{{ $agendaItem->agendaItemType->name ?? '' }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_status', $agendaItem)
                @php
                    $colors = ['approved' => 'badge-success', 'rejected' => 'badge-error', 'discussed' => 'badge-info', 'pending' => 'badge-warning', 'deferred' => 'badge-secondary', 'withdrawn' => 'badge-ghost'];
                    $c = $colors[$agendaItem->discussion_status] ?? 'badge-ghost';
                @endphp
                <x-mary-badge :value="ucfirst($agendaItem->discussion_status)" class="{{ $c }} badge-outline font-bold" />
            @endscope

            @scope('actions', $agendaItem)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-eye" wire:click.stop="view({{ $agendaItem->id }})" class="btn-sm btn-ghost" />
                    
                    @can('edit agenda items')
                        <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $agendaItem->id }})" class="btn-sm btn-ghost text-blue-500" />
                    @endcan

                    @can('delete agenda items')
                        <x-mary-button icon="o-trash" wire:click.stop="confirmDelete({{ $agendaItem->id }})" class="btn-sm btn-ghost text-red-500" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- MODAL --}}
    <x-mary-modal wire:model="showModal" class="backdrop-blur-md" box-class="w-11/12 max-w-5xl bg-base-100 shadow-2xl">
        
        @if($viewMode)
            {{-- VIEW MODE UI --}}
            <div class="relative">
                <div class="flex justify-between items-start border-b border-base-200 pb-4 mb-6">
                    <div class="w-3/4">
                        <div class="flex items-center gap-2 mb-2">
                             <x-mary-badge :value="$agendaItemTypes->firstWhere('id', $agenda_item_type_id)?->name" class="badge-neutral" />
                             @if($is_left_over) <x-mary-badge value="LEFTOVER" class="badge-warning" /> @endif
                        </div>
                        <h2 class="text-2xl font-black leading-tight">{{ $title }}</h2>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-bold text-gray-400 uppercase">Sequence</div>
                        <div class="text-2xl font-mono font-bold text-primary">#{{ $sequence_number }}</div>
                    </div>
                </div>

                <div class="bg-base-200/60 p-4 rounded-xl mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase">Meeting</div>
                        <div class="font-bold">{{ $meetings->firstWhere('id', $meeting_id)->title ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase">Owner</div>
                        <div class="font-bold">{{ $users->firstWhere('id', $owner_user_id)->name ?? 'Unassigned' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase">Status</div>
                        <div class="font-bold uppercase {{ $discussion_status == 'approved' ? 'text-success' : 'text-base-content' }}">
                            {{ ucfirst($discussion_status) }}
                        </div>
                    </div>
                </div>

                @if(!empty($selectedKeywords))
                    <div class="mb-6">
                        <div class="text-xs font-bold text-gray-500 uppercase mb-2">Keywords</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($keywords->whereIn('id', $selectedKeywords) as $k)
                                <x-mary-badge :value="$k->name" class="badge-neutral" />
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="prose prose-sm max-w-none text-gray-600 bg-white p-6 rounded-xl border border-base-200 shadow-sm min-h-[200px] prose-img:rounded-lg prose-a:text-primary">
                    {!! $details ?: '<span class="italic text-gray-400">No detailed description provided.</span>' !!}
                </div>

                <div class="mt-6 flex justify-end">
                    <x-mary-button label="Close" class="btn-ghost" @click="$wire.showModal = false" />
                    
                    @can('edit agenda items')
                        <x-mary-button label="Edit" icon="o-pencil" class="btn-primary" wire:click="edit({{ $id }})" />
                    @endcan
                </div>
            </div>

        @else
            {{-- EDIT / CREATE MODE UI --}}
            <x-mary-header :title="$editMode ? 'Edit Agenda Item' : 'New Agenda Item'" separator />
            
            <x-mary-form wire:submit="save">
                
                {{-- Row 1: Title (85-90%) & Sequence (10-15%) --}}
                <div class="flex gap-4">
                    <div class="flex-grow">
                         <x-mary-input label="Topic Title" wire:model="title" placeholder="Enter the main topic title..." icon="o-bookmark" />
                    </div>
                    <div class="w-24 shrink-0">
                         <x-mary-input label="Seq #" wire:model="sequence_number" type="number" />
                    </div>
                </div>

                {{-- Row 2: Meeting, Type, Status, Owner --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2">
                    <x-mary-select label="Meeting" wire:model="meeting_id" :options="$meetings" option-label="title" option-value="id" placeholder="Select Meeting" searchable />
                    <x-mary-select label="Type" wire:model="agenda_item_type_id" :options="$agendaItemTypes" option-label="name" option-value="id" placeholder="Type" />
                    <x-mary-select label="Status" wire:model="discussion_status" :options="$statuses" option-label="name" option-value="id" />
                    <x-mary-select label="Owner" wire:model="owner_user_id" :options="$users" option-label="name" option-value="id" placeholder="Owner" searchable />
                </div>

                {{-- Row 3: Leftover & Details --}}
                <div class="mt-4">
                     <div class="flex justify-between items-center mb-1">
                        <span class="label-text font-semibold ml-1">Detailed Description</span>
                        <x-mary-checkbox label="Is Leftover?" wire:model="is_left_over" class="checkbox-warning checkbox-sm" right />
                     </div>
                     <div class="border rounded-lg overflow-hidden mb-4">
                        <x-mary-editor wire:model="details" :config="['height' => 300, 'menubar' => false, 'statusbar' => false]" />
                     </div>
                     
                     <x-mary-choices label="Keywords" wire:model="selectedKeywords" :options="$keywords" option-label="name" option-value="id" searchable search-function="searchKeywords" />
                </div>

                <x-slot:actions>
                    <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                    <x-mary-button label="Save Changes" class="btn-primary" type="submit" spinner="save" icon="o-check" />
                </x-slot:actions>
            </x-mary-form>
        @endif
    </x-mary-modal>

    {{-- DELETE MODAL (Simple confirmation) --}}
    <x-mary-modal wire:model="showDeleteModal" title="Delete Item" class="backdrop-blur-sm">
        <div class="p-4">Are you sure you want to delete this?</div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Confirm" class="btn-error" wire:click="delete" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- IMPORT/EXPORT MODALS --}}
    <x-export-modal wire:model="showExportModal" />

    <x-mary-modal wire:model="showImportModal" title="Import Agenda Items" separator>
        <div class="bg-base-200 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <div class="font-bold mb-1">CSV Format Instructions</div>
                    <div class="text-sm opacity-70">
                        Columns: Meeting ID, Type ID, Sequence, Status, Title, Details, Owner ID, Is Left Over, Keywords (semicolon separated)
                    </div>
                </div>
                <x-mary-button label="Download Template" icon="o-arrow-down-tray" class="btn-sm btn-outline" wire:click="downloadTemplate" spinner="downloadTemplate" />
            </div>
        </div>

        <div class="grid gap-4">
            <x-mary-file wire:model="file" label="CSV File" accept=".csv" />
            <x-mary-checkbox label="File has header row?" wire:model="hasHeader" />
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showImportModal = false" />
            <x-mary-button label="Import" wire:click="import" class="btn-primary" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-mary-modal>
</div>