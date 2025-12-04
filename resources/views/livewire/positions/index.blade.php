<?php

use App\Models\Position;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

new class extends Component {
    use Toast, WithFileUploads;

    public $positions;
    public $roles;
    public string $search = '';
    public bool $showModal = false;
    public bool $showImportModal = false;
    public $file;
    public bool $hasHeader = true;

    public bool $editMode = false;
    public bool $viewMode = false;
    public bool $drawer = false;
    public bool $showDeleted = false;
    
    public $filterUnique = null;

    public $id;
    public $name;
    public $code;
    public $is_unique = false;
    public $role_id;

    // Delete Modal
    public bool $showDeleteModal = false;
    public $positionToDeleteId;

    // Export Modal
    public bool $showExportModal = false;

    public function mount()
    {
        if (!auth()->user()->can('view positions')) {
            $this->error('Unauthorized access. Redirecting to dashboard...');
            return $this->redirect(route('dashboard'), navigate: true);
        }

        $this->roles = Role::all();
        $this->loadPositions();
    }

    public function loadPositions()
    {
        $query = Position::with('role');

        if ($this->showDeleted) {
            // Position model doesn't have SoftDeletes by default yet, but good to have structure
            // $query->onlyTrashed(); 
        }

        if ($this->filterUnique !== null && $this->filterUnique !== '') {
            $query->where('is_unique', $this->filterUnique);
        }

        $this->positions = $query
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->get();
    }

    public function updatedSearch() { $this->loadPositions(); }
    public function updatedFilterUnique() { $this->loadPositions(); }

    public function create()
    {
        if (!auth()->user()->can('create positions')) {
            $this->error('You do not have permission to create positions.');
            return;
        }

        $this->reset(['id', 'name', 'code', 'is_unique', 'role_id']);
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function edit(Position $position)
    {
        if (!auth()->user()->can('edit positions')) {
            $this->error('You do not have permission to edit positions.');
            return;
        }

        $this->fillForm($position);
        $this->editMode = true;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function view(Position $position)
    {
        $this->fillForm($position);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function fillForm(Position $position)
    {
        $this->id = $position->id;
        $this->name = $position->name;
        $this->code = $position->code;
        $this->is_unique = $position->is_unique;
        $this->role_id = $position->role_id;
    }

    public function save()
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:255', Rule::unique('positions')->ignore($this->id)],
            'is_unique' => 'boolean',
            'role_id' => 'nullable|exists:roles,id',
        ];

        $validated = $this->validate($rules);

        if ($this->editMode) {
            $position = Position::find($this->id);
            
            // Protect Super Admin Code
            if ($position->code === 'super_admin' && $validated['code'] !== 'super_admin') {
                $this->error('Cannot change the code of the Super Admin position.');
                return;
            }

            $position->update($validated);
            $this->success('Position updated successfully.');
        } else {
            Position::create($validated);
            $this->success('Position created successfully.');
        }

        $this->showModal = false;
        $this->loadPositions();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('delete positions')) {
            $this->error('You do not have permission to delete positions.');
            return;
        }

        $this->positionToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $position = Position::find($this->positionToDeleteId);
        
        if ($position->code === 'super_admin') {
            $this->error('Cannot delete the Super Admin position.');
            $this->showDeleteModal = false;
            return;
        }

        if ($position->users()->exists()) {
            $this->error('Cannot delete position with assigned users.');
            $this->showDeleteModal = false;
            return;
        }

        $position->delete();
        $this->success('Position deleted successfully.');
        $this->showDeleteModal = false;
        $this->loadPositions();
    }

    public function export($format = 'pdf')
    {
        $headers = ['Name', 'Code', 'Is Unique'];
        $positions = $this->positions;
        $data = [];

        foreach ($positions as $pos) {
            $data[] = [
                $pos->name,
                $pos->code,
                $pos->is_unique ? 'Yes' : 'No',
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
                "Content-Disposition" => "attachment; filename=positions-" . date('Y-m-d') . ".csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', [
                'title' => 'Positions',
                'headers' => $headers,
                'rows' => $data
            ]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'positions-' . date('Y-m-d') . '.pdf');
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Positions', ['size' => 16, 'bold' => true]);
            
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
            }, 'positions-' . date('Y-m-d') . '.docx');
        } elseif ($format === 'doc') {
             $content = view('exports.pdf', [
                'title' => 'Positions',
                'headers' => $headers,
                'rows' => $data
            ])->render();
            
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, 'positions-' . date('Y-m-d') . '.doc', [
                'Content-Type' => 'application/msword'
            ]);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['Name', 'Code', 'Is Unique (Yes/No)'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Example Position', 'example_code', 'Yes']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=positions_template.csv",
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
                Position::updateOrCreate(
                    ['code' => $row[1]],
                    [
                        'name' => $row[0],
                        'is_unique' => strtolower($row[2]) === 'yes' || $row[2] == '1',
                    ]
                );
            } catch (\Exception $e) {
                // Skip duplicates or errors
            }
        }
        fclose($file);
        $this->success('Positions imported successfully.');
        $this->showImportModal = false;
        $this->loadPositions();
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'code', 'label' => 'Code'],
                ['key' => 'role.name', 'label' => 'Linked Role'],
                ['key' => 'is_unique', 'label' => 'Unique Position', 'class' => 'w-32 text-center'],
            ]
        ];
    }
}; ?>

<div>
    <x-mary-header title="Positions" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-arrow-up-tray" class="btn-ghost" @click="$wire.showExportModal = true" tooltip="Export" />
            <x-mary-button icon="o-arrow-down-tray" class="btn-ghost" @click="$wire.showImportModal = true" tooltip="Import" />
            <x-mary-button icon="o-funnel" wire:click="$toggle('drawer')" class="btn-ghost" tooltip="Filter" />
            
            @can('create positions')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" tooltip="Create Position" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-drawer wire:model="drawer" title="Filters" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="grid gap-5">
            <x-mary-select label="Is Unique" wire:model.live="filterUnique" :options="[['id' => 1, 'name' => 'Yes'], ['id' => 0, 'name' => 'No']]" placeholder="All" />
        </div>
    </x-mary-drawer>

    <x-mary-card shadow class="rounded-2xl">
        <x-mary-table :headers="$headers" :rows="$positions" striped @row-click="$wire.view($event.detail.row.id)">
            @scope('cell_is_unique', $position)
                @if($position->is_unique)
                    <x-mary-icon name="o-check-circle" class="w-6 h-6 text-success" />
                @else
                    <x-mary-icon name="o-minus-circle" class="w-6 h-6 text-gray-300" />
                @endif
            @endscope
            @scope('actions', $position)
                <div class="flex gap-0">
                    <x-mary-button icon="o-eye" wire:click.stop="view({{ $position->id }})" spinner class="btn-sm btn-ghost px-1" tooltip="View" />
                    
                    @can('edit positions')
                        <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $position->id }})" spinner class="btn-sm btn-ghost text-blue-500 px-1" tooltip="Edit" />
                    @endcan
                    
                    @can('delete positions')
                        <x-mary-button icon="o-trash" wire:click.stop="confirmDelete({{ $position->id }})" spinner class="btn-sm btn-ghost text-red-500 px-1" tooltip="Delete" />
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Export Modal --}}
    <x-export-modal wire:model="showExportModal" />

    <x-mary-modal wire:model="showImportModal" title="Import Positions" class="backdrop-blur">
        <div class="bg-base-200 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <div class="font-bold mb-1">CSV Format Instructions</div>
                    <div class="text-sm opacity-70">
                        Columns: Name, Code, Is Unique (Yes/No)
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
            <x-mary-button label="Import" class="btn-primary" wire:click="import" spinner="import" wire:loading.attr="disabled" />
        </x-slot:actions>
    </x-mary-modal>

    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-xl">
        <x-mary-header 
            :title="$viewMode ? 'Position Details' : ($editMode ? 'Edit Position' : 'Create Position')"
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
                <div class="flex justify-between items-start border-b border-base-200 pb-4">
                    <div>
                        <div class="text-xl font-bold text-gray-800 leading-tight">{{ $name }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ $code }}</div>
                    </div>
                    <div class="text-right">
                        @if($is_unique)
                            <x-mary-badge value="Unique Position" class="badge-warning" />
                        @else
                            <x-mary-badge value="Standard Position" class="badge-neutral" />
                        @endif
                    </div>
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.showModal = false" />
            </x-slot:actions>
        @else
            <x-mary-form wire:submit="save">
                <x-mary-input label="Name" wire:model="name" placeholder="e.g. Vice Chancellor" />
                <x-mary-input label="Code" wire:model="code" placeholder="e.g. vc" hint="Must be unique" />
                
                <x-mary-select label="Linked Role" wire:model="role_id" :options="$roles" placeholder="Select a role (optional)" hint="Users with this position will automatically get this role" />
                
                <div class="pb-3 pl-1">
                    <label class="cursor-pointer flex items-center gap-2" for="toggle-unique">
                        <span class="text-xs font-bold text-gray-500 uppercase">Unique Position?</span>
                        <x-mary-toggle id="toggle-unique" wire:model="is_unique" class="toggle-warning toggle-sm" />
                    </label>
                    <div class="text-xs text-gray-400 mt-1">If enabled, only one user can hold this position at a time.</div>
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
            Are you sure you want to delete this position? This action cannot be undone.
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>
</div>
