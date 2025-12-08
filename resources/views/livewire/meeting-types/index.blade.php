<?php

use App\Models\MeetingType;
use App\Services\MeetingTypeService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

new class extends Component {
    use Toast, WithFileUploads;

    public $meetingTypes;
    public string $search = '';
    public bool $showModal = false;
    public bool $showExportModal = false;
    public bool $showImportModal = false;
    public $file;
    public bool $hasHeader = true;
    public bool $editMode = false;
    public bool $viewMode = false;
    public bool $drawer = false;
    public bool $showDeleted = false;
    
    public $filterActive = null;

    public $id;
    public $name;
    public $code;
    public $description;
    public $is_active = true;

    // Delete Modal
    public bool $showDeleteModal = false;
    public $typeToDeleteId;

    public function mount(MeetingTypeService $service)
    {
        $this->authorize('viewAny', MeetingType::class);
        $this->loadMeetingTypes($service);
    }

    public function loadMeetingTypes(MeetingTypeService $service)
    {
        $query = MeetingType::query();

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if ($this->filterActive !== null && $this->filterActive !== '') {
            $query->where('is_active', $this->filterActive);
        }

        $this->meetingTypes = $query
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->get();
    }

    public function updatedSearch()
    {
        $this->loadMeetingTypes(app(MeetingTypeService::class));
    }

    public function updatedShowDeleted()
    {
        $this->loadMeetingTypes(app(MeetingTypeService::class));
    }

    public function updatedFilterActive()
    {
        $this->loadMeetingTypes(app(MeetingTypeService::class));
    }

    public function create()
    {
        $this->authorize('create', MeetingType::class);
        $this->reset(['id', 'name', 'code', 'description', 'is_active']);
        $this->is_active = true;
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function edit(MeetingType $meetingType)
    {
        $this->authorize('update', $meetingType);
        $this->fillForm($meetingType);
        $this->showModal = true;
    }

    public function view(MeetingType $meetingType)
    {
        $this->fillForm($meetingType);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function fillForm(MeetingType $meetingType)
    {
        $this->id = $meetingType->id;
        $this->name = $meetingType->name;
        $this->code = $meetingType->code;
        $this->description = $meetingType->description;
        $this->is_active = $meetingType->is_active;
    }

    public function save(MeetingTypeService $service)
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:meeting_types,code,' . $this->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        $validated = $this->validate($rules);

        if ($this->editMode) {
            $meetingType = MeetingType::find($this->id);
            $service->updateMeetingType($meetingType, $validated);
            $this->success('Meeting Type updated successfully.');
        } else {
            $service->createMeetingType($validated);
            $this->success('Meeting Type created successfully.');
        }

        $this->showModal = false;
        $this->loadMeetingTypes($service);
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', MeetingType::find($id));
        $this->typeToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(MeetingTypeService $service)
    {
        $meetingType = MeetingType::find($this->typeToDeleteId);
        $this->authorize('delete', $meetingType);
        $service->deleteMeetingType($meetingType);
        $this->loadMeetingTypes($service);
    }

   
    public function restore($id)
    {
        $meetingType = MeetingType::withTrashed()->find($id);
        $this->authorize('restore', $meetingType);
        $meetingType->restore();
        $this->success('Meeting Type restored successfully.');
        $this->loadMeetingTypes(app(MeetingTypeService::class));
    }
   
    public function toggleStatus($id)
    {
        $meetingType = MeetingType::find($id);
        $this->authorize('update', $meetingType);
        $meetingType->is_active = !$meetingType->is_active;
        $meetingType->save();
        $this->success('Status updated.');
        $this->loadMeetingTypes(app(MeetingTypeService::class));
    }

    public function export($format = 'pdf')
    {
        $headers = ['Name', 'Code', 'Description', 'Active'];
        $meetingTypes = $this->meetingTypes;
        $data = [];

        foreach ($meetingTypes as $type) {
            $data[] = [
                $type->name,
                $type->code,
                $type->description,
                $type->is_active ? 'Yes' : 'No'
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
                "Content-Disposition" => "attachment; filename=meeting-types-" . date('Y-m-d') . ".csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', [
                'title' => 'Meeting Types',
                'headers' => $headers,
                'rows' => $data
            ]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'meeting-types-' . date('Y-m-d') . '.pdf');
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Meeting Types', ['size' => 16, 'bold' => true]);
            
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
            }, 'meeting-types-' . date('Y-m-d') . '.docx');
        } elseif ($format === 'doc') {
             $content = view('exports.pdf', [
                'title' => 'Meeting Types',
                'headers' => $headers,
                'rows' => $data
            ])->render();
            
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, 'meeting-types-' . date('Y-m-d') . '.doc', [
                'Content-Type' => 'application/msword'
            ]);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['Name', 'Code', 'Description', 'Is Active (Yes/No)'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Board Meeting', 'BM', 'Regular board meeting', 'Yes']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=meeting_types_template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        $path = $this->file->getRealPath();
        $file = fopen($path, 'r');
        
        if ($this->hasHeader) {
            fgetcsv($file); // Skip header
        }

        while (($row = fgetcsv($file)) !== false) {
            try {
                MeetingType::create([
                    'name' => $row[0],
                    'code' => $row[1],
                    'description' => $row[2] ?? null,
                    'is_active' => strtolower($row[3]) === 'yes' || $row[3] == '1',
                ]);
            } catch (\Exception $e) {
                // Skip duplicates or errors
            }
        }
        fclose($file);
        $this->success('Meeting Types imported successfully.');
        $this->showImportModal = false;
        $this->loadMeetingTypes(app(MeetingTypeService::class));
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'is_active', 'label' => 'Active', 'class' => 'w-20 text-center'],
            ]
        ];
    }
}; ?>

<div>
    <x-mary-header title="Meeting Types" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-arrow-up-tray" class="btn-ghost" @click="$wire.showExportModal = true" tooltip="Export" />
            <x-mary-button icon="o-arrow-down-tray" class="btn-ghost" @click="$wire.showImportModal = true" tooltip="Import" />
            <x-mary-button icon="o-funnel" wire:click="$toggle('drawer')" class="btn-ghost" tooltip="Filter" />
            @can('create', App\Models\MeetingType::class)
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" tooltip="Create Meeting Type" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-drawer wire:model="drawer" title="Filters" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="grid gap-5">
            <x-mary-select label="Status" wire:model.live="filterActive" :options="[['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Inactive']]" placeholder="All" />
            <x-mary-toggle label="Show Deleted" wire:model.live="showDeleted" />
        </div>
    </x-mary-drawer>

    <x-mary-card shadow class="rounded-2xl">
        <x-mary-table :headers="$headers" :rows="$meetingTypes" striped @row-click="$wire.view($event.detail.row.id)">
            @scope('cell_is_active', $meetingType)
                @if($meetingType->is_active)
                    @can('update', $meetingType)
                        <x-mary-button icon="o-check-circle" class="btn-circle btn-ghost btn-sm text-success" wire:click.stop="toggleStatus({{ $meetingType->id }})" />
                    @else
                        <x-mary-icon name="o-check-circle" class="w-6 h-6 text-success" />
                    @endcan
                @else
                    @can('update', $meetingType)
                        <x-mary-button icon="o-x-circle" class="btn-circle btn-ghost btn-sm text-error" wire:click.stop="toggleStatus({{ $meetingType->id }})" />
                    @else
                        <x-mary-icon name="o-x-circle" class="w-6 h-6 text-error" />
                    @endcan
                @endif
            @endscope
            @scope('actions', $meetingType)
                <div class="flex gap-0">
                    @if($meetingType->trashed())
                        @can('restore', $meetingType)
                            <x-mary-button icon="o-arrow-path" wire:click.stop="restore({{ $meetingType->id }})" spinner class="btn-sm btn-ghost text-green-500 px-1" tooltip="Restore" />
                        @endcan
                    @else
                        <x-mary-button icon="o-eye" wire:click.stop="view({{ $meetingType->id }})" spinner class="btn-sm btn-ghost px-1" tooltip="View" />
                        
                        @can('update', $meetingType)
                            <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $meetingType->id }})" spinner class="btn-sm btn-ghost text-blue-500 px-1" tooltip="Edit" />
                            <x-mary-button icon="o-key" link="{{ route('meeting-types.permissions', $meetingType) }}" class="btn-sm btn-ghost text-warning px-1" tooltip="Permissions" />
                        @endcan
                        
                        @can('delete', $meetingType)
                            <x-mary-button icon="o-trash" wire:click.stop="confirmDelete({{ $meetingType->id }})" spinner class="btn-sm btn-ghost text-red-500 px-1" tooltip="Delete" />
                        @endcan
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Export Modal --}}
    <x-export-modal wire:model="showExportModal" />

    <x-mary-modal wire:model="showImportModal" title="Import Meeting Types" class="backdrop-blur">
        <div class="bg-base-200 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <div class="font-bold mb-1">CSV Format Instructions</div>
                    <div class="text-sm opacity-70">
                        Columns: Name, Code, Description, Is Active
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

    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-2xl">
        <x-mary-header 
            :title="$viewMode ? 'Meeting Type Details' : ($editMode ? 'Edit Meeting Type' : 'Create Meeting Type')"
            :subtitle="$viewMode ? $name : 'Fill in the details below'"
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
                    <div>
                        <div class="text-xl font-bold text-gray-800 leading-tight">{{ $name }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ $description }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Code</div>
                        <div class="text-2xl font-black text-gray-200">{{ $code }}</div>
                    </div>
                </div>

                {{-- Status Widget --}}
                <div class="flex items-center gap-4 px-4 py-3 bg-base-200/50 rounded-xl border border-base-200/50">
                    <div class="p-2 bg-white rounded-full shadow-sm">
                        <x-mary-icon name="o-check-circle" class="w-5 h-5 text-primary" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase">Status</div>
                        <div class="flex items-center gap-2 mt-1">
                            @if($is_active)
                                <x-mary-badge value="Active" class="badge-success" />
                            @else
                                <x-mary-badge value="Inactive" class="badge-error" />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.showModal = false" />
            </x-slot:actions>
        @else
            <x-mary-form wire:submit="save">
                {{-- Row 1: Name & Code --}}
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <x-mary-input label="Name" wire:model="name" placeholder="e.g. Board Meeting" />
                    </div>
                    <div class="w-full md:w-1/3">
                        <x-mary-input label="Code" wire:model="code" placeholder="e.g. BM" />
                    </div>
                </div>

                {{-- Row 2: Description --}}
                <x-mary-textarea label="Description" wire:model="description" placeholder="Brief description of the meeting type..." rows="3" />

                {{-- Row 3: Status --}}
                <div class="pb-3 pl-1">
                    <label class="cursor-pointer flex items-center gap-2" for="toggle-active">
                        <span class="text-xs font-bold text-gray-500 uppercase">Active?</span>
                        <x-mary-toggle id="toggle-active" wire:model="is_active" class="toggle-success toggle-sm" />
                    </label>
                </div>

                <x-slot:actions>
                    <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                    <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        @endif
    </x-mary-modal>

    <x-mary-modal wire:model="showDeleteModal" title="Delete Confirmation" class="backdrop-blur" box-class="bg-base-100 border-error border w-full max-w-md">
        <div class="text-base mb-4">
            Are you sure you want to delete this meeting type? This action cannot be undone.
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
