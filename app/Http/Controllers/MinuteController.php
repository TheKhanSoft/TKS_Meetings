<?php

namespace App\Http\Controllers;

use App\Http\Requests\MinuteRequest;
use App\Models\Minute;
use App\Services\MinuteService;
use Illuminate\Http\Request;

class MinuteController extends Controller
{
    protected $minuteService;

    public function __construct(MinuteService $minuteService)
    {
        $this->minuteService = $minuteService;
    }

    public function index()
    {
        return view('livewire.minutes.index');
    }

    public function store(MinuteRequest $request)
    {
        $this->minuteService->createMinute($request->validated());
        return redirect()->route('minutes.index')->with('success', 'Minute created successfully.');
    }

    public function update(MinuteRequest $request, Minute $minute)
    {
        $this->minuteService->updateMinute($minute, $request->validated());
        return redirect()->route('minutes.index')->with('success', 'Minute updated successfully.');
    }

    public function destroy(Minute $minute)
    {
        $this->minuteService->deleteMinute($minute);
        return redirect()->route('minutes.index')->with('success', 'Minute deleted successfully.');
    }
}
