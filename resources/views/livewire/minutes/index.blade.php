<?php

use App\Models\AgendaItem;
use App\Models\Minute;
use App\Models\User;
use App\Models\Keyword;
use App\Http\Requests\MinuteRequest;
use App\Services\MinuteService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

new class extends Component {
    use Toast, WithFileUploads;

    public $minutes;
    public string $search = '';
    public $agendaItems;
    public $users;
    public $keywords = [];
    public $selectedKeywords = [];
    public array $selected = [];
    
    public bool $showModal = false;
    public bool $showExportModal = false;
    public bool $showImportModal = false;
    public $file;
    public bool $hasHeader = true;
    public bool $editMode = false;
    public bool $viewMode = false;
    public bool $drawer = false;
    public bool $showDeleted = false;
    
    public $filterStatus = '';
    public $filterResponsible = '';
    public $filterDueDateStart = '';
    public $filterDueDateEnd = '';

    public $id;
    public $agenda_item_id;
    public $decision;
    public $action_required;
    public $approval_status = 'draft';
    public $responsible_user_id;
    public $target_due_date;

    // Delete Modal
    public bool $showDeleteModal = false;
    public $minuteToDeleteId;

    public function mount(MinuteService $service)
    {
        $this->authorize('viewAny', Minute::class);

        $this->loadMinutes($service);
        $this->agendaItems = AgendaItem::all();
        $this->users = User::all();
        $this->keywords = Keyword::orderBy('name')->get();

        if (request()->has('view')) {
            $item = Minute::find(request('view'));
            if ($item) {
                $this->view($item);
            }
        }
    }

    public function loadMinutes(MinuteService $service)
    {
        $query = Minute::query()
            ->with('agendaItem');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('decision', 'like', '%' . $this->search . '%')
                  ->orWhereHas('agendaItem', function ($subQ) {
                      $subQ->where('title', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if ($this->filterStatus) {
            $query->where('approval_status', $this->filterStatus);
        }

        if ($this->filterResponsible) {
            $query->where('responsible_user_id', $this->filterResponsible);
        }

        if ($this->filterDueDateStart) {
            $query->whereDate('target_due_date', '>=', $this->filterDueDateStart);
        }

        if ($this->filterDueDateEnd) {
            $query->whereDate('target_due_date', '<=', $this->filterDueDateEnd);
        }

        $this->minutes = $query->get();
    }

    public function updatedSearch()
    {
        $this->loadMinutes(app(MinuteService::class));
    }

    public function updatedShowDeleted()
    {
        $this->loadMinutes(app(MinuteService::class));
    }

    public function updatedFilterStatus()
    {
        $this->loadMinutes(app(MinuteService::class));
    }

    public function updatedFilterResponsible()
    {
        $this->loadMinutes(app(MinuteService::class));
    }

    public function updatedFilterDueDateStart()
    {
        $this->loadMinutes(app(MinuteService::class));
    }

    public function updatedFilterDueDateEnd()
    {
        $this->loadMinutes(app(MinuteService::class));
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

    public function create()
    {
        $this->authorize('create', Minute::class);

        $this->reset(['id', 'agenda_item_id', 'decision', 'action_required', 'approval_status', 'responsible_user_id', 'target_due_date', 'selectedKeywords']);
        $this->approval_status = 'draft';
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function fillForm(Minute $minute)
    {
        $this->id = $minute->id;
        $this->agenda_item_id = $minute->agenda_item_id;
        $this->decision = $minute->decision;
        $this->action_required = $minute->action_required;
        $this->approval_status = $minute->approval_status;
        $this->responsible_user_id = $minute->responsible_user_id;
        $this->target_due_date = $minute->target_due_date ? $minute->target_due_date->format('Y-m-d') : null;
        $this->selectedKeywords = $minute->keywords->pluck('id')->toArray();
    }

    public function edit(Minute $minute)
    {
        $this->authorize('update', $minute);

        $this->fillForm($minute);
        $this->editMode = true;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function view(Minute $minute)
    {
        $this->fillForm($minute);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function save(MinuteService $service)
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $validated = $this->validate((new MinuteRequest())->rules());

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
            $minute = Minute::find($this->id);
            $service->updateMinute($minute, $validated);
            $minute->keywords()->sync($keywordIds);
            $this->success('Minute updated successfully.');
        } else {
            $minute = $service->createMinute($validated);
            $minute->keywords()->sync($keywordIds);
            $this->success('Minute created successfully.');
        }

        $this->showModal = false;
        $this->loadMinutes($service);
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', Minute::find($id));

        $this->minuteToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(MinuteService $service)
    {
        $minute = Minute::find($this->minuteToDeleteId);
        $this->authorize('delete', $minute);
        $service->deleteMinute($minute);
        $this->success('Minute deleted successfully.');
        $this->showDeleteModal = false;
        $this->loadMinutes($service);
    }

    public function restore($id)
    {
        $minute = Minute::withTrashed()->find($id);
        $this->authorize('restore', $minute);
        $minute->restore();
        $this->success('Minute restored successfully.');
        $this->loadMinutes(app(MinuteService::class));
    }

    public function toggleStatus($id)
    {
        $minute = Minute::find($id);
        $this->authorize('update', $minute);
        $minute->approval_status = $minute->approval_status === 'approved' ? 'draft' : 'approved';
        $minute->save();
    }

    public function bulkDelete()
    {
        $minutes = Minute::whereIn('id', $this->selected)->get();
        foreach ($minutes as $minute) {
            $this->authorize('delete', $minute);
        }

        Minute::whereIn('id', $this->selected)->delete();
        $this->success('Selected minutes deleted.');
        $this->selected = [];
        $this->loadMinutes(app(MinuteService::class));
    }

    public function bulkRestore()
    {
        $minutes = Minute::withTrashed()->whereIn('id', $this->selected)->get();
        foreach ($minutes as $minute) {
            $this->authorize('restore', $minute);
        }

        Minute::withTrashed()->whereIn('id', $this->selected)->restore();
        $this->success('Selected minutes restored.');
        $this->selected = [];
        $this->loadMinutes(app(MinuteService::class));
    }
    public function bulkStatus($status)
    {
        if (!auth()->user()->can('edit minutes')) {
            $this->error('Unauthorized.');
            return;
        }

        Minute::whereIn('id', $this->selected)->update(['approval_status' => $status]);
        $this->success('Status updated for selected minutes.');
        $this->selected = [];
        $this->loadMinutes(app(MinuteService::class));
    }

    public function export($format = 'pdf')
    {
        $headers = ['Agenda Item', 'Decision', 'Action Required', 'Status', 'Responsible User', 'Target Due Date'];
        $minutes = $this->minutes;
        $data = [];

        foreach ($minutes as $minute) {
            $data[] = [
                $minute->agendaItem->title ?? '',
                $minute->decision,
                $minute->action_required,
                $minute->approval_status,
                $minute->responsibleUser->name ?? '',
                $minute->target_due_date ? $minute->target_due_date->format('Y-m-d') : ''
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
                "Content-Disposition" => "attachment; filename=minutes-" . date('Y-m-d') . ".csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', [
                'title' => 'Minutes',
                'headers' => $headers,
                'rows' => $data
            ]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'minutes-' . date('Y-m-d') . '.pdf');
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Minutes', ['size' => 16, 'bold' => true]);
            
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
            $table->addRow();
            foreach ($headers as $header) {
                $table->addCell(2000)->addText($header, ['bold' => true]);
            }
            
            foreach ($data as $row) {
                $table->addRow();
                foreach ($row as $cell) {
                    $table->addCell(2000)->addText($cell);
                }
            }
            
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            return response()->streamDownload(function () use ($objWriter) {
                $objWriter->save('php://output');
            }, 'minutes-' . date('Y-m-d') . '.docx');
        } elseif ($format === 'doc') {
             $content = view('exports.pdf', [
                'title' => 'Minutes',
                'headers' => $headers,
                'rows' => $data
            ])->render();
            
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, 'minutes-' . date('Y-m-d') . '.doc', [
                'Content-Type' => 'application/msword'
            ]);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['Agenda Item ID', 'Decision', 'Action Required', 'Status (draft/approved)', 'Responsible User ID', 'Target Due Date (YYYY-MM-DD)', 'Keywords (semicolon separated)'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['1', 'Approved decision', 'Action needed', 'draft', '10', '2023-12-31', 'Urgent;Finance']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=minutes_template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    public function import()
    {
        $maxSize = \App\Models\Setting::get('max_upload_size', 10240);
        $this->validate([
            'file' => "required|mimes:csv,txt|max:$maxSize",
        ]);

        $path = $this->file->getRealPath();
        $file = fopen($path, 'r');
        
        if ($this->hasHeader) {
            fgetcsv($file); // Skip header
        }

        while (($row = fgetcsv($file)) !== false) {
            try {
                $minute = Minute::create([
                    'agenda_item_id' => $row[0],
                    'decision' => $row[1],
                    'action_required' => $row[2] ?: null,
                    'approval_status' => $row[3],
                    'responsible_user_id' => $row[4] ?: null,
                    'target_due_date' => $row[5] ? \Carbon\Carbon::parse($row[5]) : null,
                ]);

                if (!empty($row[6])) {
                    $keywords = array_filter(array_map('trim', explode(';', $row[6])));
                    $keywordIds = [];
                    foreach ($keywords as $keywordName) {
                        $keyword = Keyword::firstOrCreate(['name' => $keywordName]);
                        $keywordIds[] = $keyword->id;
                    }
                    $minute->keywords()->sync($keywordIds);
                }
            } catch (\Exception $e) {
                // Skip duplicates or errors
            }
        }
        fclose($file);
        $this->success('Minutes imported successfully.');
        $this->showImportModal = false;
        $this->loadMinutes(app(MinuteService::class));
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'agendaItem.title', 'label' => 'Agenda Item'],
                ['key' => 'approval_status', 'label' => 'Status'],
                ['key' => 'target_due_date', 'label' => 'Due Date'],
            ],
            'statuses' => [
                ['id' => 'draft', 'name' => 'Draft'],
                ['id' => 'approved', 'name' => 'Approved'],
            ]
        ];
    }
}; ?>

<div>
    <x-mary-header title="Minutes" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-arrow-up-tray" class="btn-ghost" @click="$wire.showExportModal = true" tooltip="Export" />
            <x-mary-button icon="o-arrow-down-tray" class="btn-ghost" @click="$wire.showImportModal = true" tooltip="Import" />
            <x-mary-button icon="o-funnel" wire:click="$toggle('drawer')" class="btn-ghost" tooltip="Filter" />
            
            @can('create minutes')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" tooltip="Create Minute" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-drawer wire:model="drawer" title="Filters" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="grid gap-5">
            <x-mary-select label="Status" wire:model.live="filterStatus" :options="$statuses" option-label="name" option-value="id" placeholder="All" />
            <x-mary-select label="Responsible User" wire:model.live="filterResponsible" :options="$users" option-label="name" option-value="id" placeholder="All" />
            <x-mary-datetime label="Due Date From" wire:model.live="filterDueDateStart" />
            <x-mary-datetime label="Due Date To" wire:model.live="filterDueDateEnd" />
            <x-mary-toggle label="Show Deleted" wire:model.live="showDeleted" />
        </div>
    </x-mary-drawer>

    <x-mary-card shadow class="rounded-2xl">
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

        <x-mary-table :headers="$headers" :rows="$minutes" striped @row-click="$wire.view($event.detail.row.id)" selectable wire:model.live="selected">
            @scope('cell_approval_status', $minute)
                @if($minute->approval_status == 'approved')
                    @can('edit minutes')
                        <x-mary-button icon="o-check-circle" class="btn-circle btn-ghost btn-sm text-success" wire:click.stop="toggleStatus({{ $minute->id }})" />
                    @else
                        <x-mary-icon name="o-check-circle" class="w-6 h-6 text-success" />
                    @endcan
                @else
                    @can('edit minutes')
                        <x-mary-button icon="o-x-circle" class="btn-circle btn-ghost btn-sm text-warning" wire:click.stop="toggleStatus({{ $minute->id }})" />
                    @else
                        <x-mary-icon name="o-x-circle" class="w-6 h-6 text-warning" />
                    @endcan
                @endif
            @endscope
            @scope('cell_target_due_date', $minute)
                {{ $minute->target_due_date ? $minute->target_due_date->format('d M Y') : '-' }}
            @endscope
            @scope('actions', $minute)
                <div class="flex flex-nowrap gap-0">
                    @if($minute->trashed())
                        <x-mary-button icon="o-arrow-path" wire:click.stop="restore({{ $minute->id }})" spinner class="btn-sm btn-ghost text-green-500 px-1" tooltip="Restore" />
                    @else
                        <x-mary-button icon="o-eye" wire:click.stop="view({{ $minute->id }})" spinner class="btn-sm btn-ghost px-1" tooltip="View" />
                        
                        @can('edit minutes')
                            <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $minute->id }})" spinner class="btn-sm btn-ghost text-blue-500 px-1" tooltip="Edit" />
                        @endcan
                        
                        @can('delete minutes')
                            <x-mary-button icon="o-trash" wire:click.stop="confirmDelete({{ $minute->id }})" spinner class="btn-sm btn-ghost text-red-500 px-1" tooltip="Delete" />
                        @endcan
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-3xl">
        <x-mary-header 
            :title="$viewMode ? 'Minute Details' : ($editMode ? 'Edit Minute' : 'Create Minute')"
            :subtitle="$viewMode ? 'Review the details below' : 'Fill in the details below'"
            separator
        >
            <x-slot:middle>
                @if(!$viewMode)
                    <div class="px-2 py-1 text-xs font-mono bg-base-200 rounded text-gray-500">
                        {{ $editMode ? 'EDITING MODE' : 'CREATION MODE' }}
                    </div>
                @endif
            </x-slot:middle>
        </x-mary-header>
        
        @if($viewMode)
            <div class="space-y-6">
                {{-- Header Info --}}
                <div class="flex justify-between items-start border-b border-base-200 pb-4">
                    <div class="w-3/4">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Agenda Item</div>
                        <div class="text-lg font-bold text-gray-800 leading-tight">{{ $agendaItems->firstWhere('id', $agenda_item_id)->title ?? '-' }}</div>
                        <div class="flex items-center gap-2 mt-3">
                            <x-mary-badge :value="ucfirst($approval_status)" class="{{ $approval_status == 'approved' ? 'badge-success' : 'badge-warning' }}" />
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Due Date</div>
                        <div class="text-lg font-medium text-gray-700">{{ $target_due_date ? \Carbon\Carbon::parse($target_due_date)->format('M d, Y') : '-' }}</div>
                    </div>
                </div>

                {{-- Decision & Action --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-base-100 p-4 rounded-lg border border-base-200">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Decision</div>
                        <div class="text-gray-700 leading-relaxed">{{ $decision }}</div>
                    </div>
                    <div class="bg-base-100 p-4 rounded-lg border border-base-200">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Action Required</div>
                        <div class="text-gray-700 leading-relaxed">{{ $action_required }}</div>
                    </div>
                </div>

                {{-- Responsible User --}}
                <div class="flex items-center gap-3 bg-base-200/50 p-3 rounded-xl border border-base-200/50">
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-full w-10">
                            <span class="text-xs">RU</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase">Responsible User</div>
                        <div class="font-medium">{{ $users->firstWhere('id', $responsible_user_id)->name ?? '-' }}</div>
                    </div>
                </div>

                @if(!empty($selectedKeywords))
                    <div class="mt-4">
                        <div class="text-xs font-bold text-gray-500 uppercase mb-2">Keywords</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($keywords->whereIn('id', $selectedKeywords) as $k)
                                <x-mary-badge :value="$k->name" class="badge-neutral" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.showModal = false" />
            </x-slot:actions>
        @else
            <x-mary-form wire:submit="save">
                {{-- Row 1: Agenda Item --}}
                <x-mary-select label="Agenda Item" wire:model="agenda_item_id" :options="$agendaItems" option-label="title" option-value="id" placeholder="Select Agenda Item..." searchable />
                
                {{-- Row 2: Decision & Action --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-textarea label="Decision" wire:model="decision" placeholder="Record the decision made..." rows="4" />
                    <x-mary-textarea label="Action Required" wire:model="action_required" placeholder="What needs to be done?" rows="4" />
                </div>
                
                {{-- Row 3: Status, Due Date, Responsible --}}
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <x-mary-select label="Status" wire:model="approval_status" :options="$statuses" option-label="name" option-value="id" />
                    </div>
                    <div class="flex-1">
                        <x-mary-datetime label="Target Due Date" wire:model="target_due_date" />
                    </div>
                    <div class="flex-1">
                        <x-mary-select label="Responsible User" wire:model="responsible_user_id" :options="$users" option-label="name" option-value="id" placeholder="Select User" searchable />
                    </div>
                </div>

                <x-mary-choices label="Keywords" wire:model="selectedKeywords" :options="$keywords" option-label="name" option-value="id" searchable search-function="searchKeywords" />

                <x-slot:actions>
                    <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                    <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        @endif
    </x-mary-modal>

    <x-mary-modal wire:model="showDeleteModal" title="Delete Confirmation" class="backdrop-blur" box-class="bg-base-100 border-error border w-full max-w-md">
        <div class="text-base mb-4">
            Are you sure you want to delete this minute? This action cannot be undone.
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Export Modal --}}
    <x-export-modal wire:model="showExportModal" />

    <x-mary-modal wire:model="showImportModal" title="Import Minutes" separator>
        <div class="bg-base-200 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <div class="font-bold mb-1">CSV Format Instructions</div>
                    <div class="text-sm opacity-70">
                        Columns: Agenda Item ID, Decision, Action Required, Status, Responsible User ID, Target Due Date, Keywords (semicolon separated)
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
