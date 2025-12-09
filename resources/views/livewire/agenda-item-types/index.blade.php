<?php

use App\Models\AgendaItemType;
use App\Services\AgendaItemTypeService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Validation\Rule;

new class extends Component {
    use Toast, WithFileUploads, WithPagination;

    // Filter Properties
    public string $search = '';
    public bool $showDeleted = false;
    public $filterActive = null;
    
    // UI State
    public bool $showModal = false;
    public bool $showExportModal = false;
    public bool $showImportModal = false;
    public bool $drawer = false;
    
    // Form/Model Properties
    public bool $editMode = false;
    public bool $viewMode = false;
    public $id;
    public $name;
    public $is_active = true;

    // Import/Export
    public $file;
    public bool $hasHeader = true;

    // Delete Modal
    public bool $showDeleteModal = false;
    public $typeToDeleteId;

    public function mount()
    {
        $this->authorize('viewAny', AgendaItemType::class);
    }

    // Reset pagination when filters change
    public function updatedSearch() { $this->resetPage(); }
    public function updatedShowDeleted() { $this->resetPage(); }
    public function updatedFilterActive() { $this->resetPage(); }

    public function with(): array
    {
        $query = AgendaItemType::query();

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if ($this->filterActive !== null && $this->filterActive !== '') {
            $query->where('is_active', $this->filterActive);
        }

        $agendaItemTypes = $query
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id')
            ->paginate(12); // Grid layout pagination

        return [
            'agendaItemTypes' => $agendaItemTypes
        ];
    }

    public function create()
    {
        if (!auth()->user()->can('create agenda item types')) { abort(403); }
        
        $this->reset(['id', 'name', 'is_active']);
        $this->is_active = true;
        $this->editMode = false;
        $this->viewMode = false;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(AgendaItemType $agendaItemType)
    {
        if (!auth()->user()->can('edit agenda item types')) { abort(403); }
        
        $this->fillForm($agendaItemType);
        $this->editMode = true;
        $this->viewMode = false;
        $this->resetValidation();
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
            'name' => ['required', 'string', 'max:255', Rule::unique('agenda_item_types')->ignore($this->id)],
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
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('delete agenda item types')) { abort(403); }
        
        $this->typeToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(AgendaItemTypeService $service)
    {
        if (!auth()->user()->can('delete agenda item types')) { abort(403); }
        
        $service->deleteAgendaItemType(AgendaItemType::find($this->typeToDeleteId));
        $this->success('Agenda Item Type deleted successfully.');
        $this->showDeleteModal = false;
    }

    public function restore($id)
    {
        if (!auth()->user()->can('delete agenda item types')) { abort(403); }
        
        AgendaItemType::withTrashed()->find($id)->restore();
        $this->success('Agenda Item Type restored successfully.');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit agenda item types')) { abort(403); }
        
        $agendaItemType = AgendaItemType::find($id);
        $agendaItemType->is_active = !$agendaItemType->is_active;
        $agendaItemType->save();
        $this->success('Status updated.');
    }

    // --- Export Logic (Preserved) ---
    public function export($format = 'pdf')
    {
        // Re-fetch data without pagination for export
        $query = AgendaItemType::query();
        if ($this->showDeleted) { $query->onlyTrashed(); }
        if ($this->filterActive !== null && $this->filterActive !== '') { $query->where('is_active', $this->filterActive); }
        $items = $query->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->get();

        $headers = ['Name', 'Active'];
        $data = [];
        foreach ($items as $type) {
            $data[] = [$type->name, $type->is_active ? 'Yes' : 'No'];
        }

        // CSV
        if ($format === 'csv') {
            $callback = function() use ($data, $headers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $headers);
                foreach ($data as $row) { fputcsv($file, $row); }
                fclose($file);
            };
            return response()->stream($callback, 200, [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=agenda-item-types.csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        } 
        // PDF
        elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', [
                'title' => 'Agenda Item Types', 'headers' => $headers, 'rows' => $data
            ]);
            return response()->streamDownload(fn() => $pdf->output(), 'agenda-item-types.pdf');
        }
        // DOCX/DOC (Simplified for brevity, assuming libs installed)
        elseif ($format === 'docx' || $format === 'doc') {
             $phpWord = new PhpWord();
             $section = $phpWord->addSection();
             $section->addText('Agenda Item Types', ['size' => 16, 'bold' => true]);
             $table = $section->addTable(['borderSize' => 6]);
             $table->addRow();
             foreach ($headers as $h) { $table->addCell(2000)->addText($h, ['bold' => true]); }
             foreach ($data as $row) {
                 $table->addRow();
                 foreach ($row as $cell) { $table->addCell(2000)->addText($cell); }
             }
             $writer = IOFactory::createWriter($phpWord, 'Word2007');
             return response()->streamDownload(fn() => $writer->save('php://output'), "agenda-item-types.{$format}");
        }
    }

    // --- Import Logic (Preserved) ---
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
            "Content-Disposition" => "attachment; filename=template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    public function import()
    {
        $this->validate(['file' => 'required|mimes:csv,txt']);
        $path = $this->file->getRealPath();
        $file = fopen($path, 'r');
        if ($this->hasHeader) { fgetcsv($file); }

        while (($row = fgetcsv($file)) !== false) {
            try {
                AgendaItemType::create([
                    'name' => $row[0],
                    'is_active' => strtolower($row[1] ?? '') === 'yes' || ($row[1] ?? '') == '1',
                ]);
            } catch (\Exception $e) {}
        }
        fclose($file);
        $this->success('Import successful.');
        $this->showImportModal = false;
    }
}; ?>

<div>
    {{-- Header --}}
    <x-mary-header title="Agenda Item Types" subtitle="Manage types for meeting agenda items" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search types..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            {{-- Tools Dropdown --}}
            <x-mary-dropdown label="Tools" icon="o-wrench-screwdriver" class="btn-ghost">
                <x-mary-menu-item title="Export" icon="o-arrow-up-tray" @click="$wire.showExportModal = true" />
                <x-mary-menu-item title="Import" icon="o-arrow-down-tray" @click="$wire.showImportModal = true" />
                <x-mary-menu-item title="Filter" icon="o-funnel" wire:click="$toggle('drawer')" />
            </x-mary-dropdown>

            @can('create agenda item types')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" label="Create" responsive />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    {{-- Filter Drawer --}}
    <x-mary-drawer wire:model="drawer" title="Filters" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="grid gap-5">
            <x-mary-select label="Status" wire:model.live="filterActive" :options="[['id' => 1, 'name' => 'Active'], ['id' => 0, 'name' => 'Inactive']]" placeholder="All Statuses" icon="o-flag" />
            <x-mary-toggle label="Show Deleted Records" wire:model.live="showDeleted" class="toggle-error" />
        </div>
    </x-mary-drawer>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($agendaItemTypes as $type)
            <div 
                wire:key="type-{{ $type->id }}"
                class="card border shadow-sm hover:shadow-md transition-all duration-200 group flex flex-col justify-between 
                {{ $type->trashed() ? 'bg-base-200/60 border-dashed border-gray-400 opacity-80' : 'bg-base-100 border-base-200' }}"
            >
                <div class="card-body p-5">
                    {{-- Card Header --}}
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $type->trashed() ? 'bg-gray-200 text-gray-500' : 'bg-primary/10 text-primary' }}">
                                <x-mary-icon name="o-tag" class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-base-content text-lg truncate leading-tight" title="{{ $type->name }}">
                                    {{ $type->name }}
                                </h3>
                                {{-- Status Badge --}}
                                <div class="mt-1">
                                    @if($type->trashed())
                                        <div class="badge badge-xs badge-error badge-outline gap-1">Deleted</div>
                                    @elseif($type->is_active)
                                        <div class="badge badge-xs badge-success gap-1 text-success-content bg-success/20 border-0">Active</div>
                                    @else
                                        <div class="badge badge-xs badge-ghost gap-1">Inactive</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Actions Dropdown --}}
                        <x-mary-dropdown class="btn-sm btn-circle btn-ghost -mr-3 -mt-3" no-x-anchor left>
                            <x-slot:trigger>
                                <x-mary-icon name="o-ellipsis-vertical" class="w-5 h-5 text-gray-400 group-hover:text-base-content transition-colors" />
                            </x-slot:trigger>
                            
                            @if($type->trashed())
                                @can('delete agenda item types')
                                    <x-mary-menu-item title="Restore" icon="o-arrow-path" wire:click="restore({{ $type->id }})" class="text-success" />
                                @endcan
                            @else
                                <x-mary-menu-item title="View Details" icon="o-eye" wire:click="view({{ $type->id }})" />
                                
                                @can('edit agenda item types')
                                    <x-mary-menu-item title="Edit" icon="o-pencil" wire:click="edit({{ $type->id }})" />
                                    <x-mary-menu-item 
                                        title="{{ $type->is_active ? 'Deactivate' : 'Activate' }}" 
                                        icon="{{ $type->is_active ? 'o-no-symbol' : 'o-check-circle' }}" 
                                        wire:click="toggleStatus({{ $type->id }})" 
                                    />
                                @endcan
                                
                                @can('delete agenda item types')
                                    <x-mary-menu-separator />
                                    <x-mary-menu-item title="Delete" icon="o-trash" wire:click="confirmDelete({{ $type->id }})" class="text-error" />
                                @endcan
                            @endif
                        </x-mary-dropdown>
                    </div>
                </div>

                {{-- Card Footer (Optional Meta info) --}}
                <div class="bg-base-200/30 border-t border-base-200 px-5 py-2 text-[10px] text-gray-400 flex justify-between rounded-b-xl">
                    <span>ID: {{ $type->id }}</span>
                    <span>Created: {{ $type->created_at->format('M d, Y') }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-16 text-center bg-base-100 rounded-xl border border-dashed border-base-300">
                <div class="bg-base-200 rounded-full p-4 mb-3">
                    <x-mary-icon name="o-inbox" class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="font-bold text-lg">No Item Types Found</h3>
                <p class="text-gray-500 text-sm max-w-xs mx-auto mb-4">
                    {{ $search ? 'Try adjusting your search query.' : 'Get started by creating a new agenda item type.' }}
                </p>
                @if(!$search)
                    <x-mary-button label="Create Type" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $agendaItemTypes->links() }}
    </div>

    {{-- Create/Edit/View Modal --}}
    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-lg">
        <x-mary-header 
            :title="$viewMode ? 'Item Type Details' : ($editMode ? 'Edit Type' : 'New Type')"
            :subtitle="$viewMode ? $name : 'Manage agenda item type configuration'"
            separator
        >
            <x-slot:actions>
                @if($viewMode && auth()->user()->can('edit agenda item types'))
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit({{ $id }})" tooltip="Edit this type" />
                @endif
            </x-slot:actions>
        </x-mary-header>
        
        @if($viewMode)
            <div class="space-y-4">
                <div class="flex justify-between border-b pb-2">
                    <span class="font-bold">Name:</span>
                    <span>{{ $name }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="font-bold">Status:</span>
                    <x-mary-badge :value="$is_active ? 'Active' : 'Inactive'" :class="$is_active ? 'badge-success' : 'badge-ghost'" />
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.showModal = false" />
            </x-slot:actions>
        @else
            <x-mary-form wire:submit="save">
                <x-mary-input label="Name" wire:model="name" placeholder="e.g. Regular Item" icon="o-tag" />
                
                <div class="bg-base-200/50 p-3 rounded-lg border border-base-200">
                    <x-mary-toggle label="Active Status" wire:model="is_active" class="toggle-success" right />
                    <div class="text-xs text-gray-500 pt-1">Inactive types cannot be selected in new agendas.</div>
                </div>

                <x-slot:actions>
                    <x-mary-button label="Cancel" @click="$wire.showModal = false" />
                    <x-mary-button label="Save" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        @endif
    </x-mary-modal>

    {{-- Import Modal --}}
    <x-mary-modal wire:model="showImportModal" title="Import Types" class="backdrop-blur">
        <div class="space-y-4">
            <div class="bg-blue-50 text-blue-700 p-3 rounded-lg text-sm flex gap-3">
                <x-mary-icon name="o-information-circle" class="w-5 h-5 shrink-0" />
                <div>
                    <strong>Instructions:</strong> Upload a CSV file with columns: <code>Name</code>, <code>Is Active</code>.
                </div>
            </div>
            <x-mary-file wire:model="file" label="Select CSV File" accept=".csv" />
            <div class="flex justify-between items-center">
                <x-mary-checkbox label="Has Header Row" wire:model="hasHeader" />
                <x-mary-button label="Template" icon="o-arrow-down-tray" class="btn-xs btn-ghost" wire:click="downloadTemplate" spinner />
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showImportModal = false" />
            <x-mary-button label="Import Data" wire:click="import" class="btn-primary" spinner="import" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Export Modal Component (Assuming this exists based on your code) --}}
    @if($showExportModal)
        <x-export-modal wire:model="showExportModal" />
    @endif

    {{-- Delete Modal --}}
    <x-mary-modal wire:model="showDeleteModal" title="Confirm Deletion" class="backdrop-blur">
        <div class="text-center p-4">
            <div class="bg-red-100 text-red-600 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                <x-mary-icon name="o-exclamation-triangle" class="w-6 h-6" />
            </div>
            <p>Are you sure you want to delete this item type?</p>
            <p class="text-sm text-gray-500 mt-1">This action can be undone later if needed.</p>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>