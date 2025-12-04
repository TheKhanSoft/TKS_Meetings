<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\AgendaItem;
use App\Models\Minute;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

new class extends Component {
    // --- Filters ---
    public $timeframe = 'this_year';
    public $customStart;
    public $customEnd;
    public $meetingTypeId = '';

    public $mtTimeframe = 'this_year';
    public $mtCustomStart;
    public $mtCustomEnd;

    public $agendaTimeframe = 'this_year';
    public $agendaCustomStart;
    public $agendaCustomEnd;

    public $showExportModal = false;
    public $exportFormat = 'pdf';

    public function mount()
    {
        if (!auth()->user()->can('view dashboard')) {
            abort(403);
        }
    }

    public function getGreetingProperty()
    {
        $hour = date('H');
        if ($hour < 12) return 'Good Morning';
        if ($hour < 18) return 'Good Afternoon';
        return 'Good Evening';
    }

    public function with()
    {
        $userId = auth()->id();

        // 1. Activity Chart Data
        $query = Meeting::query();
        if ($this->meetingTypeId) {
            $query->where('meeting_type_id', $this->meetingTypeId);
        }
        $chartData = $this->getChartData(clone $query);

        // 2. Meeting Types Breakdown
        $totalMeetingsForBreakdown = 0;
        $meetingTypesBreakdown = MeetingType::get()->map(function ($type) use (&$totalMeetingsForBreakdown) {
            $query = Meeting::where('meeting_type_id', $type->id);
            $this->applyDateFilter($query, 'mt'); 
            $count = $query->count();
            $type->meetings_count = $count;
            $totalMeetingsForBreakdown += $count;
            return $type;
        });

        // Calculate percentages
        $meetingTypesBreakdown->transform(function($type) use ($totalMeetingsForBreakdown) {
            $type->percentage = $totalMeetingsForBreakdown > 0 
                ? round(($type->meetings_count / $totalMeetingsForBreakdown) * 100) 
                : 0;
            return $type;
        });

        // 3. Agenda Stats
        $agendaChartData = $this->getAgendaChartData();
        $agendaQuery = AgendaItem::query();
        $this->applyDateFilter($agendaQuery, 'agenda'); 
        
        $totalItems = (clone $agendaQuery)->count();
        $discussedItems = (clone $agendaQuery)->where('discussion_status', 'discussed')->count();

        // Agenda Breakdown List
        $rawStats = (clone $agendaQuery)
            ->select('discussion_status', DB::raw('count(*) as count'))
            ->groupBy('discussion_status')
            ->pluck('count', 'discussion_status');

        $agendaStatsList = [];
        foreach ($rawStats as $status => $count) {
             if ($count > 0) {
                 $config = $this->getStatusConfig($status);
                 $agendaStatsList[] = [
                     'label' => $config['label'],
                     'count' => $count,
                     'class' => $config['class'],
                     'color_hex' => $config['color']
                 ];
             }
        }

        $completionRate = $totalItems > 0 ? round(($discussedItems / $totalItems) * 100) : 0;
        $lastMeeting = Meeting::where('date', '<', now())->orderBy('date', 'desc')->orderBy('time', 'desc')->first();

        $announcements = Announcement::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->take(3)
            ->get();

        return [
            'meetingsCount' => Meeting::count(),
            'meetingsThisMonth' => Meeting::whereMonth('date', date('m'))->whereYear('date', date('Y'))->count(),
            'pendingAgendaItems' => AgendaItem::where('discussion_status', 'pending')->count(),
            'myActionsCount' => Minute::where('responsible_user_id', $userId)->where('approval_status', '!=', 'approved')->count(),
            'upcomingMeetings' => Meeting::with('meetingType')->where('date', '>=', now()->toDateString())->orderBy('date')->orderBy('time')->take(5)->get(),
            'myPendingActions' => Minute::with(['agendaItem.meeting', 'agendaItem'])
                ->where('responsible_user_id', $userId)
                ->where('approval_status', '!=', 'approved')
                ->latest()
                ->take(5)
                ->get(),
            'chartData' => $chartData,
            'meetingTypesBreakdown' => $meetingTypesBreakdown,
            'lastMeeting' => $lastMeeting,
            'announcements' => $announcements,
            'meetingTypes' => MeetingType::all(),
            'agendaChartData' => $agendaChartData,
            'completionRate' => $completionRate,
            'totalItems' => $totalItems,
            'agendaStatsList' => $agendaStatsList,
            'exportFormats' => [
                ['id' => 'pdf', 'name' => 'PDF Document'],
                ['id' => 'csv', 'name' => 'CSV Spreadsheet'],
                ['id' => 'docx', 'name' => 'MS Word (DOCX)'],
            ],
        ];
    }

    // --- Core Filter Logic ---
    private function applyDateFilter($query, $context = 'chart')
    {
        $timeframe = match($context) { 'chart' => $this->timeframe, 'mt' => $this->mtTimeframe, 'agenda' => $this->agendaTimeframe, default => $this->timeframe };
        $customStart = match($context) { 'chart' => $this->customStart, 'mt' => $this->mtCustomStart, 'agenda' => $this->agendaCustomStart, default => $this->customStart };
        $customEnd = match($context) { 'chart' => $this->customEnd, 'mt' => $this->mtCustomEnd, 'agenda' => $this->agendaCustomEnd, default => $this->customEnd };

        if ($context === 'agenda') {
            $query->whereHas('meeting', function($q) use ($timeframe, $customStart, $customEnd) {
                $this->applyDateQuery($q, $timeframe, $customStart, $customEnd);
            });
            return;
        }
        $this->applyDateQuery($query, $timeframe, $customStart, $customEnd);
    }

    private function applyDateQuery($query, $timeframe, $customStart, $customEnd)
    {
        if ($timeframe === 'all_time') return;
        if ($timeframe === 'this_year') $query->whereYear('date', date('Y'));
        if ($timeframe === 'last_year') $query->whereYear('date', date('Y') - 1);
        if ($timeframe === 'this_month') $query->whereYear('date', date('Y'))->whereMonth('date', date('m'));
        if ($timeframe === 'last_month') {
            $lastMonth = Carbon::now()->subMonth();
            $query->whereYear('date', $lastMonth->year)->whereMonth('date', $lastMonth->month);
        }
        if ($timeframe === 'custom' && $customStart && $customEnd) {
            $query->whereBetween('date', [$customStart, $customEnd]);
        }
    }

    // --- EXPORT LOGIC (FIXED) ---
    public function export()
    {
        // 1. Prepare Data
        $data = MeetingType::get()->map(function ($type) {
            $query = Meeting::where('meeting_type_id', $type->id);
            $this->applyDateFilter($query, 'mt'); // Use MT filter context
            $type->meetings_count = $query->count();
            return $type;
        });

        // 2. Generate Label
        $range = match($this->mtTimeframe) {
            'this_year' => 'This Year (' . date('Y') . ')',
            'last_year' => 'Last Year (' . (date('Y') - 1) . ')',
            'this_month' => 'This Month (' . date('F Y') . ')',
            'last_month' => 'Last Month (' . Carbon::now()->subMonth()->format('F Y') . ')',
            'all_time' => 'All Time',
            'custom' => 'Custom (' . $this->mtCustomStart . ' to ' . $this->mtCustomEnd . ')',
            default => 'Report'
        };

        $filename = 'meeting-stats-' . strtolower(date('Y-m-d')) . '.' . $this->exportFormat;

        // 3. Handle PDF
        if ($this->exportFormat === 'pdf') {
            $pdf = Pdf::loadView('exports.meeting-types', ['data' => $data, 'dateRange' => $range]);
            return response()->streamDownload(fn() => print($pdf->output()), $filename);
        }
        
        // 4. Handle CSV
        if ($this->exportFormat === 'csv') {
            $headers = [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
            ];
            $callback = function() use ($data, $range) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Meeting Type Statistics', $range]);
                fputcsv($file, ['Type', 'Count']);
                foreach ($data as $row) fputcsv($file, [$row->name, $row->meetings_count]);
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        // 5. Handle DOCX
        if ($this->exportFormat === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addText('Meeting Statistics', ['bold' => true, 'size' => 16]);
            $section->addText('Range: ' . $range);
            $table = $section->addTable(['borderSize' => 6]);
            $table->addRow();
            $table->addCell(4000)->addText('Type', ['bold' => true]);
            $table->addCell(2000)->addText('Count', ['bold' => true]);
            foreach ($data as $row) {
                $table->addRow();
                $table->addCell(4000)->addText($row->name);
                $table->addCell(2000)->addText($row->meetings_count);
            }
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            return response()->streamDownload(fn() => $objWriter->save('php://output'), $filename);
        }

        $this->showExportModal = false;
    }

    // --- Chart Helpers ---
    private function getChartData($query)
    {
        $labels = [];
        $data = [];
        $this->applyDateFilter($query, 'chart');

        if (in_array($this->timeframe, ['this_year', 'last_year'])) {
             $meetings = $query->get()->groupBy(fn($m) => $m->date->format('M'));
             $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
             foreach($labels as $month) { $data[] = $meetings->get($month)?->count() ?? 0; }
        } elseif (in_array($this->timeframe, ['this_month', 'last_month'])) {
             $meetings = $query->get()->groupBy(fn($m) => $m->date->format('j'));
             $days = $this->timeframe === 'this_month' ? Carbon::now()->daysInMonth : Carbon::now()->subMonth()->daysInMonth;
             for($i=1; $i<=$days; $i++) { $labels[] = $i; $data[] = $meetings->get($i)?->count() ?? 0; }
        } else {
             $meetings = $query->orderBy('date')->get()->groupBy(fn($m) => $m->date->format('Y-m'));
             foreach($meetings as $dateKey => $group) { 
                 $labels[] = Carbon::createFromFormat('Y-m', $dateKey)->format('M Y'); 
                 $data[] = $group->count(); 
             }
        }
        return ['labels' => $labels, 'datasets' => [['data' => $data]]];
    }

    private function getAgendaChartData()
    {
        $query = AgendaItem::query();
        $this->applyDateFilter($query, 'agenda');
        $agendaStats = $query->select('discussion_status', DB::raw('count(*) as count'))->groupBy('discussion_status')->pluck('count', 'discussion_status');
        $labels = []; $data = []; $colors = [];
        foreach ($agendaStats as $status => $count) {
            $config = $this->getStatusConfig($status);
            $labels[] = $config['label'];
            $data[] = $count;
            $colors[] = $config['color'];
        }
        return ['labels' => $labels, 'datasets' => [['data' => $data, 'backgroundColor' => $colors]]];
    }

    private function getStatusConfig($status)
    {
        $statuses = [
            'pending' => ['label' => 'Pending', 'color' => '#fbbf24', 'class' => 'text-warning'],
            'discussed' => ['label' => 'Discussed', 'color' => '#3b82f6', 'class' => 'text-info'],
            'approved' => ['label' => 'Approved', 'color' => '#22c55e', 'class' => 'text-success'],
            'rejected' => ['label' => 'Rejected', 'color' => '#ef4444', 'class' => 'text-error'],
            'deferred' => ['label' => 'Deferred', 'color' => '#a855f7', 'class' => 'text-secondary'],
            'withdrawn' => ['label' => 'Withdrawn', 'color' => '#9ca3af', 'class' => 'text-neutral'],
        ];
        return $statuses[strtolower($status)] ?? ['label' => ucfirst($status), 'color' => '#cbd5e1', 'class' => 'text-gray-500'];
    }

    public function updated($property)
    {
        if (in_array($property, ['timeframe', 'customStart', 'customEnd', 'meetingTypeId'])) {
            $q = Meeting::query();
            if ($this->meetingTypeId) $q->where('meeting_type_id', $this->meetingTypeId);
            $this->dispatch('update-chart', data: $this->getChartData($q));
        }
        if (in_array($property, ['agendaTimeframe', 'agendaCustomStart', 'agendaCustomEnd'])) {
            $this->dispatch('update-agenda-chart', data: $this->getAgendaChartData());
        }
    }
}; ?>

<div class="p-6 max-w-[1600px] mx-auto space-y-8">
    
    <div class="flex flex-col md:flex-row justify-between items-end gap-4 bg-gradient-to-r from-base-100 to-base-200 p-6 rounded-2xl border border-base-300 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-sm font-bold text-primary uppercase tracking-wider mb-1">
                <x-mary-icon name="o-home" class="w-4 h-4" /> Dashboard
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-base-content">
                {{ $this->greeting }}, {{ auth()->user()->first_name ?? auth()->user()->name }}
            </h1>
            <p class="text-gray-500 mt-2 flex items-center gap-2">
                <x-mary-icon name="o-calendar" class="w-4 h-4" />
                {{ now()->format('l, F j, Y') }}
            </p>
        </div>
        <div class="flex gap-3">
            <x-mary-button icon="o-magnifying-glass" class="btn-ghost" @click.stop="$dispatch('mary-search-open')" tooltip="Search" />
            <x-mary-button label="Add Agenda" icon="o-document-plus" link="{{ route('agenda-items.index') }}" class="btn-ghost" />
            <x-mary-button label="Schedule Meeting" icon="o-plus" link="{{ route('meetings.index') }}" class="btn-primary shadow-lg shadow-primary/30" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="relative overflow-hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 bg-indigo-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="relative z-10">
                <div class="text-gray-500 text-sm font-medium mb-1">Total Meetings</div>
                <div class="text-3xl font-black text-indigo-600">{{ $meetingsCount }}</div>
                <div class="mt-4 flex items-center gap-2 text-xs text-indigo-400 font-medium">
                    <x-mary-icon name="o-archive-box" class="w-4 h-4" /> All time records
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 bg-blue-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="relative z-10">
                <div class="text-gray-500 text-sm font-medium mb-1">Scheduled This Month</div>
                <div class="text-3xl font-black text-blue-600">{{ $meetingsThisMonth }}</div>
                <div class="mt-4 flex items-center gap-2 text-xs text-blue-400 font-medium">
                    <x-mary-icon name="o-calendar-days" class="w-4 h-4" /> Active schedules
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 bg-amber-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="relative z-10">
                <div class="text-gray-500 text-sm font-medium mb-1">Pending Agenda</div>
                <div class="text-3xl font-black text-amber-500">{{ $pendingAgendaItems }}</div>
                <div class="mt-4 flex items-center gap-2 text-xs text-amber-500 font-medium">
                    <x-mary-icon name="o-clock" class="w-4 h-4" /> Requires discussion
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-white p-6 rounded-2xl shadow-sm border border-gray-100 group hover:shadow-md transition-all">
            <div class="absolute -right-4 -top-4 bg-emerald-50 w-24 h-24 rounded-full group-hover:scale-110 transition-transform"></div>
            <div class="relative z-10">
                <div class="text-gray-500 text-sm font-medium mb-1">Completion Rate</div>
                <div class="text-3xl font-black text-emerald-600">{{ $completionRate }}<span class="text-lg align-top">%</span></div>
                <div class="mt-4 flex items-center gap-2 text-xs text-emerald-500 font-medium">
                     <x-mary-progress value="{{ $completionRate }}" max="100" class="progress-success h-2 w-20" />
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            
            <x-mary-card class="shadow-sm border border-gray-100">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="bg-primary/10 p-2 rounded-lg text-primary"><x-mary-icon name="o-chart-bar" class="w-5 h-5" /></div>
                        <span>Activity Overview</span>
                    </div>
                </x-slot:title>
                <x-slot:menu>
                    <div class="flex flex-col md:flex-row gap-2 items-end md:items-center">
                        @if($timeframe === 'custom')
                            <div class="flex gap-2 animate-fade-in">
                                <input type="date" wire:model.live="customStart" class="input input-sm input-bordered" />
                                <input type="date" wire:model.live="customEnd" class="input input-sm input-bordered" />
                            </div>
                        @endif
                        <div class="flex gap-2">
                            <select wire:model.live="meetingTypeId" class="select select-sm select-bordered font-normal">
                                <option value="">All Types</option>
                                @foreach($meetingTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="timeframe" class="select select-sm select-bordered font-normal">
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="last_year">Last Year</option>
                                <option value="all_time">All Time</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>
                </x-slot:menu>
                <div class="relative h-80 w-full" wire:ignore>
                    <canvas id="meetingsChart"></canvas>
                </div>
            </x-mary-card>

            <div class="grid md:grid-cols-2 gap-6">
                <x-mary-card title="Agenda Status" class="shadow-sm border border-gray-100">
                    <x-slot:menu>
                        <div class="flex flex-col gap-2 items-end">
                            <select wire:model.live="agendaTimeframe" class="select select-sm select-bordered font-normal w-32">
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="last_year">Last Year</option>
                                <option value="all_time">All Time</option>
                                <option value="custom">Custom</option>
                            </select>
                            @if($agendaTimeframe === 'custom')
                                <div class="flex gap-1 flex-col animate-fade-in">
                                    <input type="date" wire:model.live="agendaCustomStart" class="input input-xs input-bordered" />
                                    <input type="date" wire:model.live="agendaCustomEnd" class="input input-xs input-bordered" />
                                </div>
                            @endif
                        </div>
                    </x-slot:menu>
                     <div class="relative h-64 w-full flex items-center justify-center" wire:ignore>
                        <canvas id="agendaChart"></canvas>
                    </div>
                </x-mary-card>

                <x-mary-card title="Status Breakdown" class="shadow-sm border border-gray-100">
                    <div class="space-y-4">
                         @foreach($agendaStatsList as $stat)
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $stat['color_hex'] }}"></div>
                                    <span class="text-sm font-medium text-gray-600">{{ $stat['label'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-800">{{ $stat['count'] }}</span>
                                    <span class="text-xs text-gray-400">items</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full" style="width: {{ ($stat['count'] / $totalItems) * 100 }}%; background-color: {{ $stat['color_hex'] }}"></div>
                            </div>
                        @endforeach
                    </div>
                </x-mary-card>
            </div>
            
             <x-mary-card class="shadow-sm border border-gray-100 overflow-hidden">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                         <div class="bg-red-50 p-2 rounded-lg text-red-500"><x-mary-icon name="o-bell-alert" class="w-5 h-5" /></div>
                        <span>Requires Your Attention</span>
                        <x-mary-badge :value="$myActionsCount" class="badge-error badge-sm text-white" />
                    </div>
                </x-slot:title>

                @if($myPendingActions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th>Action</th>
                                    <th>Context</th>
                                    <th>Due Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myPendingActions as $action)
                                    <tr class="hover:bg-gray-50 cursor-pointer group">
                                        <td class="font-medium">
                                            {{ Str::limit($action->action_required, 40) }}
                                        </td>
                                        <td>
                                            <div class="text-xs text-gray-500">{{ $action->agendaItem->meeting->title ?? 'N/A' }}</div>
                                        </td>
                                        <td>
                                            @if($action->target_due_date)
                                                <div class="flex items-center gap-1 {{ $action->target_due_date->isPast() ? 'text-red-500 font-bold' : 'text-gray-600' }}">
                                                    <x-mary-icon name="o-calendar" class="w-3 h-3" />
                                                    {{ $action->target_due_date->format('M d') }}
                                                </div>
                                            @else
                                                <span class="text-gray-300">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <x-mary-button icon="o-chevron-right" link="{{ route('minutes.index') }}" class="btn-ghost btn-xs opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="bg-green-50 p-4 rounded-full mb-3">
                            <x-mary-icon name="o-check" class="w-6 h-6 text-green-500" />
                        </div>
                        <p class="text-gray-600 font-medium">All caught up!</p>
                        <p class="text-gray-400 text-sm">No pending actions assigned to you.</p>
                    </div>
                @endif
            </x-mary-card>
        </div>

        <div class="space-y-6">
            
            @if($announcements->count() > 0)
                <x-mary-card title="Announcements" class="shadow-sm border border-gray-100">
                    <div class="space-y-4">
                        @foreach($announcements as $announcement)
                            <div class="p-3 bg-base-200/50 rounded-lg border border-base-200">
                                <div class="flex justify-between items-start mb-1">
                                    <div class="font-bold text-sm">{{ $announcement->title }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $announcement->created_at->diffForHumans() }}</div>
                                </div>
                                <div class="text-xs text-gray-600 line-clamp-2">{{ $announcement->content }}</div>
                            </div>
                        @endforeach
                    </div>
                    <x-slot:actions>
                        <x-mary-button label="View All" link="{{ route('announcements.index') }}" class="btn-ghost btn-sm" />
                    </x-slot:actions>
                </x-mary-card>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-900 p-5 text-white">
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-2">Last Session</div>
                    @if($lastMeeting)
                        <h3 class="text-xl font-bold leading-tight mb-4">{{ $lastMeeting->title }}</h3>
                        <div class="flex justify-between items-end">
                             <div class="flex items-center gap-2 text-sm text-gray-300">
                                <x-mary-icon name="o-clock" class="w-4 h-4" />
                                {{ $lastMeeting->date->format('M d, Y') }}
                             </div>
                             <x-mary-badge :value="$lastMeeting->meetingType->name" class="badge-outline text-white" />
                        </div>
                    @else
                        <p class="text-gray-400 italic">No meeting history found.</p>
                    @endif
                </div>
                @if($lastMeeting)
                <div class="p-5">
                    <div class="grid grid-cols-2 gap-4 mb-5">
                         <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-2xl font-black text-gray-800">{{ $lastMeeting->agendaItems()->count() }}</div>
                            <div class="text-xs text-gray-500 font-bold uppercase">Agenda Items</div>
                         </div>
                         <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <div class="text-2xl font-black text-gray-800">{{ $lastMeeting->minutes()->count() }}</div>
                            <div class="text-xs text-gray-500 font-bold uppercase">Minutes</div>
                         </div>
                    </div>
                    <x-mary-button label="View Full Report" class="btn-outline w-full" link="{{ route('meetings.index') }}" />
                </div>
                @endif
            </div>

            <x-mary-card title="Upcoming Schedule" class="shadow-sm border border-gray-100">
                <div class="relative pl-4 border-l border-gray-100 space-y-6 py-2">
                    @forelse($upcomingMeetings as $meeting)
                        <div class="relative pl-6">
                            <div class="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-primary ring-4 ring-white"></div>
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-primary/5 rounded-xl flex flex-col items-center justify-center border border-primary/10">
                                    <span class="text-[10px] font-bold text-primary uppercase">{{ $meeting->date->format('M') }}</span>
                                    <span class="text-lg font-black text-gray-800 leading-none">{{ $meeting->date->format('d') }}</span>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 line-clamp-1">{{ $meeting->title }}</div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                        <span>{{ $meeting->time ? $meeting->time->format('h:i A') : 'TBD' }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $meeting->meetingType->name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-gray-400 text-sm">No upcoming meetings.</p>
                        </div>
                    @endforelse
                </div>
                <x-slot:actions>
                     <x-mary-button label="Calendar" link="{{ route('meetings.index') }}" class="btn-ghost btn-sm" />
                </x-slot:actions>
            </x-mary-card>

            <x-mary-card title="Types Breakdown" class="shadow-sm border border-gray-100">
                <x-slot:menu>
                    <div class="flex flex-col gap-2 items-end">
                        <div class="flex gap-1">
                             <select wire:model.live="mtTimeframe" class="select select-sm select-bordered w-full max-w-xs bg-gray-50">
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="last_year">Last Year</option>
                                <option value="all_time">All Time</option>
                                <option value="custom">Custom</option>
                            </select>
                            <x-mary-button icon="o-arrow-down-tray" class="btn-circle btn-ghost btn-sm" @click="$wire.showExportModal = true" />
                        </div>
                        @if($mtTimeframe === 'custom')
                            <div class="flex gap-1 flex-col animate-fade-in">
                                <input type="date" wire:model.live="mtCustomStart" class="input input-xs input-bordered" />
                                <input type="date" wire:model.live="mtCustomEnd" class="input input-xs input-bordered" />
                            </div>
                        @endif
                    </div>
                </x-slot:menu>

                <div class="space-y-4">
                    @foreach($meetingTypesBreakdown as $type)
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-gray-600">{{ $type->name }}</span>
                                <span class="text-xs font-bold text-gray-900">{{ $type->percentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: {{ $type->percentage }}%"></div>
                            </div>
                            <div class="text-right text-[10px] text-gray-400 mt-1">{{ $type->meetings_count }} meetings</div>
                        </div>
                    @endforeach
                </div>
            </x-mary-card>

        </div>
    </div>

    <x-mary-modal wire:model="showExportModal" title="Export Dashboard Data">
        <div class="p-4">
            <p class="text-gray-500 mb-4 text-sm">Select a format to download the meeting types breakdown report.</p>
            <div class="grid grid-cols-1 gap-3">
                 @foreach($exportFormats as $format)
                    <button wire:click="$set('exportFormat', '{{ $format['id'] }}')" 
                            class="flex items-center gap-3 p-3 rounded-lg border hover:border-primary hover:bg-primary/5 transition-all {{ $exportFormat === $format['id'] ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-gray-200' }}">
                        <div class="w-4 h-4 rounded-full border border-gray-300 {{ $exportFormat === $format['id'] ? 'bg-primary border-primary' : '' }}"></div>
                        <span class="font-medium {{ $exportFormat === $format['id'] ? 'text-primary' : 'text-gray-700' }}">{{ $format['name'] }}</span>
                    </button>
                 @endforeach
            </div>
        </div>
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showExportModal = false" />
            <x-mary-button label="Download" class="btn-primary" wire:click="export" spinner />
        </x-slot:actions>
    </x-mary-modal>

    @script
    <script>
        let myMeetingsChart = null;
        let myAgendaChart = null;

        const initChart = (data) => {
            const ctx = document.getElementById('meetingsChart');
            if (!ctx) return;
            if (myMeetingsChart) myMeetingsChart.destroy();

            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(79, 70, 229, 0.7)');
            gradient.addColorStop(1, 'rgba(79, 70, 229, 0.05)');

            if (data.datasets && data.datasets[0]) {
                data.datasets[0].backgroundColor = gradient;
                data.datasets[0].borderColor = '#4f46e5';
                data.datasets[0].borderWidth = 2;
                data.datasets[0].pointRadius = 0;
                data.datasets[0].fill = true;
                data.datasets[0].tension = 0.4;
            }

            myMeetingsChart = new Chart(ctx, {
                type: 'line', 
                data: data,
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#f3f4f6' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        };

        const initAgendaChart = (data) => {
            const ctx = document.getElementById('agendaChart');
            if (!ctx) return;
            if (myAgendaChart) myAgendaChart.destroy();
            myAgendaChart = new Chart(ctx, {
                type: 'doughnut', data: data,
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '80%',
                    plugins: { legend: { display: false } }
                }
            });
        };

        // Initial render
        initChart(@json($chartData));
        initAgendaChart(@json($agendaChartData));

        // Listen for updates
        $wire.on('update-chart', (event) => initChart(event.data));
        $wire.on('update-agenda-chart', (event) => initAgendaChart(event.data));
    </script>
    @endscript
</div>