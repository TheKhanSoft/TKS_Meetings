<?php

use App\Models\Minute;
use App\Models\Notification;
use App\Services\NotificationService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

new class extends Component {
    use Toast, WithFileUploads;

    public $notifications;
    public string $search = '';
    public $minutes;
    
    public bool $showModal = false;
    public bool $showExportModal = false;
    public bool $showImportModal = false;
    public $file;
    public bool $hasHeader = true;
    public bool $editMode = false;
    public bool $viewMode = false;
    public bool $drawer = false;
    public bool $showDeleted = false;
    
    public $filterDateStart = '';
    public $filterDateEnd = '';

    public $id;
    public $minute_id;
    public $notification_no;
    public $notification_date;

    // Delete Modal
    public bool $showDeleteModal = false;
    public $notificationToDeleteId;

    public function mount(NotificationService $service)
    {
        if (!auth()->user()->can('view notifications')) {
            $this->error('Unauthorized access. Redirecting to dashboard...');
            return $this->redirect(route('dashboard'), navigate: true);
        }
        $this->loadNotifications($service);
        $this->minutes = Minute::with('agendaItem')->get();
    }

    public function loadNotifications(NotificationService $service)
    {
        $query = Notification::query();

        if ($this->search) {
            $query->where('notification_no', 'like', '%' . $this->search . '%');
        }

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if ($this->filterDateStart) {
            $query->whereDate('notification_date', '>=', $this->filterDateStart);
        }

        if ($this->filterDateEnd) {
            $query->whereDate('notification_date', '<=', $this->filterDateEnd);
        }

        $this->notifications = $query->get();
    }

    public function updatedSearch()
    {
        $this->loadNotifications(app(NotificationService::class));
    }

    public function updatedShowDeleted()
    {
        $this->loadNotifications(app(NotificationService::class));
    }

    public function updatedFilterDateStart()
    {
        $this->loadNotifications(app(NotificationService::class));
    }

    public function updatedFilterDateEnd()
    {
        $this->loadNotifications(app(NotificationService::class));
    }

    public function create()
    {
        if (!auth()->user()->can('create notifications')) {
            abort(403);
        }
        $this->reset(['id', 'minute_id', 'notification_no', 'notification_date']);
        $this->editMode = false;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function fillForm(Notification $notification)
    {
        $this->id = $notification->id;
        $this->minute_id = $notification->minute_id;
        $this->notification_no = $notification->notification_no;
        $this->notification_date = $notification->notification_date ? $notification->notification_date->format('Y-m-d') : null;
    }

    public function edit(Notification $notification)
    {
        if (!auth()->user()->can('edit notifications')) {
            abort(403);
        }
        $this->fillForm($notification);
        $this->editMode = true;
        $this->viewMode = false;
        $this->showModal = true;
    }

    public function view(Notification $notification)
    {
        $this->fillForm($notification);
        $this->editMode = false;
        $this->viewMode = true;
        $this->showModal = true;
    }

    public function save(NotificationService $service)
    {
        if ($this->viewMode) {
            $this->showModal = false;
            return;
        }

        $rules = [
            'minute_id' => 'required|exists:minutes,id',
            'notification_no' => 'required|string|max:255|unique:notifications,notification_no,' . $this->id,
            'notification_date' => 'required|date',
        ];

        $validated = $this->validate($rules);

        if ($this->editMode) {
            $notification = Notification::find($this->id);
            $service->updateNotification($notification, $validated);
            $this->success('Notification updated successfully.');
        } else {
            $service->createNotification($validated);
            $this->success('Notification created successfully.');
        }

        $this->showModal = false;
        $this->loadNotifications($service);
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('delete notifications')) {
            abort(403);
        }
        $this->notificationToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(NotificationService $service)
    {
        if (!auth()->user()->can('delete notifications')) {
            abort(403);
        }
        $service->deleteNotification(Notification::find($this->notificationToDeleteId));
        $this->success('Notification deleted successfully.');
        $this->showDeleteModal = false;
        $this->loadNotifications($service);
    }

    public function restore($id)
    {
        if (!auth()->user()->can('delete notifications')) {
            abort(403);
        }
        Notification::withTrashed()->find($id)->restore();
        $this->success('Notification restored successfully.');
        $this->loadNotifications(app(NotificationService::class));
    }

    public function export($format = 'pdf')
    {
        $headers = ['Minute ID', 'Notification No', 'Date'];
        $notifications = $this->notifications;
        $data = [];

        foreach ($notifications as $notification) {
            $data[] = [
                $notification->minute_id,
                $notification->notification_no,
                $notification->notification_date ? $notification->notification_date->format('Y-m-d') : ''
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
                "Content-Disposition" => "attachment; filename=notifications-" . date('Y-m-d') . ".csv",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ]);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.pdf', [
                'title' => 'Notifications',
                'headers' => $headers,
                'rows' => $data
            ]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'notifications-' . date('Y-m-d') . '.pdf');
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Notifications', ['size' => 16, 'bold' => true]);
            
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
            }, 'notifications-' . date('Y-m-d') . '.docx');
        } elseif ($format === 'doc') {
             $content = view('exports.pdf', [
                'title' => 'Notifications',
                'headers' => $headers,
                'rows' => $data
            ])->render();
            
            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, 'notifications-' . date('Y-m-d') . '.doc', [
                'Content-Type' => 'application/msword'
            ]);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['Minute ID', 'Notification No', 'Date (YYYY-MM-DD)'];
        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['1', 'NOT-001', '2023-12-31']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=notifications_template.csv",
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
                Notification::create([
                    'minute_id' => $row[0],
                    'notification_no' => $row[1],
                    'notification_date' => $row[2] ? \Carbon\Carbon::parse($row[2]) : null,
                ]);
            } catch (\Exception $e) {
                // Skip duplicates or errors
            }
        }
        fclose($file);
        $this->success('Notifications imported successfully.');
        $this->showImportModal = false;
        $this->loadNotifications(app(NotificationService::class));
    }

    public function with(): array
    {
        return [
            'headers' => [
                ['key' => 'id', 'label' => '#'],
                ['key' => 'notification_no', 'label' => 'Notification No'],
                ['key' => 'notification_date', 'label' => 'Date'],
                ['key' => 'minute.id', 'label' => 'Minute ID'],
            ]
        ];
    }
}; ?>

<div>
    <x-mary-header title="Notifications" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-mary-input icon="o-magnifying-glass" placeholder="Search..." wire:model.live.debounce="search" />
        </x-slot:middle>
        <x-slot:actions>
            <x-mary-button icon="o-arrow-up-tray" class="btn-ghost" @click="$wire.showExportModal = true" tooltip="Export" />
            <x-mary-button icon="o-arrow-down-tray" class="btn-ghost" @click="$wire.showImportModal = true" tooltip="Import" />
            <x-mary-button icon="o-funnel" wire:click="$toggle('drawer')" class="btn-ghost" tooltip="Filter" />
            @can('create notifications')
                <x-mary-button icon="o-plus" class="btn-primary" wire:click="create" tooltip="Create" />
            @endcan
        </x-slot:actions>
    </x-mary-header>

    <x-mary-drawer wire:model="drawer" title="Filters" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="grid gap-5">
            <x-mary-datetime label="Date From" wire:model.live="filterDateStart" />
            <x-mary-datetime label="Date To" wire:model.live="filterDateEnd" />
            <x-mary-toggle label="Show Deleted" wire:model.live="showDeleted" />
        </div>
    </x-mary-drawer>

    <x-mary-card shadow class="rounded-2xl">
        <x-mary-table :headers="$headers" :rows="$notifications" striped @row-click="$wire.view($event.detail.row.id)">
            @scope('cell_notification_date', $notification)
                {{ $notification->notification_date ? $notification->notification_date->format('d M Y') : '-' }}
            @endscope
            @scope('actions', $notification)
                <div class="flex flex-nowrap gap-0">
                    @if($notification->trashed())
                        @can('delete notifications')
                            <x-mary-button icon="o-arrow-path" wire:click.stop="restore({{ $notification->id }})" spinner class="btn-sm btn-ghost text-green-500 px-1" tooltip="Restore" />
                        @endcan
                    @else
                        <x-mary-button icon="o-eye" wire:click.stop="view({{ $notification->id }})" spinner class="btn-sm btn-ghost px-1" tooltip="View" />
                        
                        @can('edit notifications')
                            <x-mary-button icon="o-pencil" wire:click.stop="edit({{ $notification->id }})" spinner class="btn-sm btn-ghost text-blue-500 px-1" tooltip="Edit" />
                        @endcan
                        
                        @can('delete notifications')
                            <x-mary-button icon="o-trash" wire:click.stop="confirmDelete({{ $notification->id }})" spinner class="btn-sm btn-ghost text-red-500 px-1" tooltip="Delete" />
                        @endcan
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showModal" class="backdrop-blur" box-class="w-11/12 max-w-2xl">
        <x-mary-header 
            :title="$viewMode ? 'Notification Details' : ($editMode ? 'Edit Notification' : 'Create Notification')"
            :subtitle="$viewMode ? 'Ref #' . $notification_no : 'Fill in the details below'"
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
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Notification No</div>
                        <div class="text-xl font-bold text-gray-800 leading-tight">{{ $notification_no }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Date</div>
                        <div class="text-lg font-medium text-gray-700">{{ $notification_date ? \Carbon\Carbon::parse($notification_date)->format('M d, Y') : '-' }}</div>
                    </div>
                </div>

                {{-- Minute Info --}}
                <div class="bg-base-200/50 rounded-xl p-4 border border-base-200/50">
                    <div class="text-xs font-bold text-gray-500 uppercase mb-1">Related Minute</div>
                    <div class="font-medium text-lg">
                        @php
                            $minute = $minutes->firstWhere('id', $minute_id);
                        @endphp
                        {{ $minute ? ($minute->agendaItem->title ?? 'Minute #' . $minute->id) : '-' }}
                    </div>
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button label="Close" @click="$wire.showModal = false" />
            </x-slot:actions>
        @else
            <x-mary-form wire:submit="save">
                {{-- Row 1: Minute --}}
                <x-mary-select label="Related Minute" wire:model="minute_id" :options="$minutes" option-label="id" option-value="id" placeholder="Select Minute..." searchable />
                
                {{-- Row 2: Notification No & Date --}}
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <x-mary-input label="Notification No" wire:model="notification_no" placeholder="e.g. NOT-2024-001" />
                    </div>
                    <div class="w-full md:w-1/3">
                        <x-mary-datetime label="Date" wire:model="notification_date" />
                    </div>
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
            Are you sure you want to delete this notification? This action cannot be undone.
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDeleteModal = false" />
            <x-mary-button label="Delete" class="btn-error" wire:click="delete" spinner />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Export Modal --}}
    <x-export-modal wire:model="showExportModal" />

    <x-mary-modal wire:model="showImportModal" title="Import Notifications" class="backdrop-blur">
        <div class="bg-base-200 p-4 rounded-lg mb-4">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <div class="font-bold mb-1">CSV Format Instructions</div>
                    <div class="text-sm opacity-70">
                        Columns: Minute ID, Notification No, Date
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
