<?php

use App\Models\AgendaItemType;
use App\Services\AgendaItemTypeService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

new class extends Component {
    use Toast, WithFileUploads;

    public $agendaItemTypes;
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
    public $is_active = true;

    // Delete Modal
    public bool $showDeleteModal = false;
    public $typeToDeleteId;

    public function mount(AgendaItemTypeService $service)
    {
        $this->authorize('viewAny', AgendaItemType::class);
        $this->loadAgendaItemTypes($service);
    }

    public function loadAgendaItemTypes(AgendaItemTypeService $service)
    {
        $query = AgendaItemType::query();

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if ($this->filterActive !== null && $this->filterActive !== '') {
            $query->where('is_active', $this->filterActive);
        }

        $this->agendaItemTypes = $query
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->get();
    }

    public function updatedSearch()
    {
        $this->loadAgendaItemTypes(app(AgendaItemTypeService::class));
    }

    public function updatedShowDeleted()
    {
        $this->loadAgendaItemTypes(app(AgendaItemTypeService::class));
    }

    public function updatedFilterActive()
    {
        $this->loadAgendaItemTypes(app(AgendaItemTypeService::class));
    }

    public function create()
    {
        $this->authorize('create', AgendaItemType::class);
        $this->reset(['id', 'name', 'is_active']);
        $this->is_active = true;
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function edit(AgendaItemType $agendaItemType)
    {
        $this->authorize('update', $agendaItemType);
        $this->fillForm($agendaItemType);
        $this->editMode = true;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function view(AgendaItemType $agendaItemType)
    {
        $this->fillForm($agendaItemType);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function fillForm(AgendaItemType $agendaItemType)
    {
        $this->id = $agendaItemType->id;
        $this->name = $agendaItemType->name;
        $this->is_active = $agendaItemType->is_active;
    }

    public function save(AgendaItemTypeService $service)
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ];

        $validated = $this->validate($rules);

        if ($this->editMode) {
            $agendaItemType = AgendaItemType::find($this->id);
            $service->updateAgendaItemType($agendaItemType, $validated);
            $this->success('Agenda Item Type updated successfully.');
        } else {
            $service->createAgendaItemType($validated);
            $this->success('Agenda Item Type created successfully.');
        }

        $this->showModal = false;
        $this->loadAgendaItemTypes($service);
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete', AgendaItemType::find($id));
        $this->typeToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(AgendaItemTypeService $service)
    {
        $agendaItemType = AgendaItemType::find($this->typeToDeleteId);
        $this->authorize('delete', $agendaItemType);
        $service->deleteAgendaItemType($agendaItemType);
        $this->success('Agenda Item Type deleted successfully.');
        $this->showDeleteModal = false;
        $this->loadAgendaItemTypes($service);
    }

    public function restore($id)
    {
        $agendaItemType = AgendaItemType::withTrashed()->find($id);
        $this->authorize('restore', $agendaItemType);
        $agendaItemType->restore();
        $this->success('Agenda Item Type restored successfully.');
        $this->loadAgendaItemTypes(app(AgendaItemTypeService::class));
    }

    public function toggleStatus($id)
    {
        $agendaItemType = AgendaItemType::find($id);
        $this->authorize('update', $agendaItemType);
        $agendaItemType->is_active = !$agendaItemType->is_active;
        $agendaItemType->save();
        $this->success('Agenda Item Type status updated successfully.');
        $this->loadAgendaItemTypes(app(AgendaItemTypeService::class));
    }

    public function export($format = 'pdf')
    {
        $headers = ['Name', 'Active'];
        $agendaItemTypes = $this->agendaItemTypes;
        $data = [];

        foreach ($agendaItemTypes as $type) {
            $data[] = [
                $type->name,
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
                "Content-Disposition" => "attachment; filename=agenda-item-types-" . date('Y-m-d') . ".csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', [
                'title' => 'Agenda Item Types',
                'headers' => $headers,
                'rows' => $data
            ]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'agenda-item-types-' . date('Y-m-d') . '.pdf');
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Agenda Item Types', ['size' => 16, 'bold' => true]);
            
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
            }, 'agenda-item-types-' . date('Y-m-d') . '.docx');
        } elseif ($format === 'doc') {
             $content = view('exports.pdf', [
                'title' => 'Agenda Item Types',
                'headers' => $headers,
                'rows' => $data
            ])->render();
            
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, 'agenda-item-types-' . date('Y-m-d') . '.doc', [
                'Content-Type' => 'application/msword'
            ]);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['Name', 'Is Active (Yes/No)'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Regular Item', 'Yes']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=agenda_item_types_template.csv",
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
                AgendaItemType::create([
                    'name' => $row[0],
                    'is_active' => strtolower($row[1]) === 'yes' || $row[1] == '1',
                ]);
            } catch (\Exception $e) {
                // Skip duplicates or errors
            }
        }
        fclose($file);
        $this->success('Agenda Item Types imported successfully.');
        $this->showImportModal = false;
        $this->loadAgendaItemTypes(app(AgendaItemTypeService::class));
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'is_active', 'label' => 'Active', 'class' => 'w-20 text-center'],
            ]
        ];
    }
}; ?>

<div>
    <x-mary-header title="Agenda Item Types" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-arrow-up-tray" class="btn-ghost" @click="$wire.showExportModal = true" tooltip="Export" />
            <x-mary-button icon="o-arrow-down-tray" class="btn-ghost" @click="$wire.showImportModal = true" tooltip="Import" />
            <x-mary-button icon="o-funnel" wire:click="$toggle('drawer')" class="btn-ghost" tooltip="Filter" />
            @can('create agenda item types')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" tooltip="Create Agenda Item Type" />
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
        <x-mary-table :headers="$headers" :rows="$agendaItemTypes" striped @row-click="$wire.view($event.detail.row.id)">
            @scope('cell_is_active', $agendaItemType)
                @if($agendaItemType->is_active)
                    @can('edit agenda item types')
                        <x-mary-button icon="o-check-circle" class="btn-circle btn-ghost btn-sm text-success" wire:click.stop="toggleStatus({{ $agendaItemType->id }})" />
                    @else
                        <x-mary-icon name="o-check-circle" class="w-6 h-6 text-success" />
                    @endcan
                @else
                    @can('edit agenda item types')
                        <x-mary-button icon="o-x-circle" class="btn-circle btn-ghost btn-sm text-error" wire:click.stop="toggleStatus({{ $agendaItemType->id }})" />
                    @else
                        <x-mary-icon name="o-x-circle" class="w-6 h-6 text-error" />
                    @endcan
                @endif
            @endscope
            @scope('actions', $agendaItemType)
                <div class="flex gap-0">
                    @if($agendaItemType->trashed())
                        @can('delete agenda item types')
                            <x-mary-button icon="o-arrow-path" wire:click.stop="restore({{ $agendaItemType->id }})" spinner class="btn-sm btn-ghost text-green-500 px-1" tooltip="Restore" />
                        @endcan
                    @else
                        <x-mary-button icon="o-eye" wire:click.stop="view({{ $agendaItemType->id }})" spinner class="btn-sm btn-ghost px-1" tooltip="View" />
                        
                        @can('edit agenda item types')
                            <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $agendaItemType->id }})" spinner class="btn-sm btn-ghost text-blue-500 px-1" tooltip="Edit" />
                        @endcan
                        
                        @can('delete agenda item types')
                            <x-mary-button icon="o-trash" wire:click.stop="confirmDelete({{ $agendaItemType->id }})" spinner class="btn-sm btn-ghost text-red-500 px-1" tooltip="Delete" />
                        @endcan
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Export Modal --}}
    <x-export-modal wire:model="showExportModal" />

    <x-mary-modal wire:model="showImportModal" title="Import Agenda Item Types" class="backdrop-blur">
        <div class="bg-base-200 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <div class="font-bold mb-1">CSV Format Instructions</div>
                    <div class="text-sm opacity-70">
                        Columns: Name, Is Active
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

    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-xl">
        <x-mary-header 
            :title="$viewMode ? 'Agenda Item Type Details' : ($editMode ? 'Edit Agenda Item Type' : 'Create Agenda Item Type')"
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
                {{-- Row 1: Name --}}
                <x-mary-input label="Name" wire:model="name" placeholder="e.g. Regular Item" />

                {{-- Row 2: Status --}}
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
            Are you sure you want to delete this agenda item type? This action cannot be undone.
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
