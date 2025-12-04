<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingTypeRequest;
use App\Models\MeetingType;
use App\Services\MeetingTypeService;
use Illuminate\Http\Request;

class MeetingTypeController extends Controller
{
    protected $meetingTypeService;

    public function __construct(MeetingTypeService $meetingTypeService)
    {
        $this->meetingTypeService = $meetingTypeService;
    }

    public function index()
    {
        return view('livewire.meeting-types.index');
    }

    public function store(MeetingTypeRequest $request)
    {
        $this->meetingTypeService->createMeetingType($request->validated());
        return redirect()->route('meeting-types.index')->with('success', 'Meeting Type created successfully.');
    }

    public function update(MeetingTypeRequest $request, MeetingType $meetingType)
    {
        $this->meetingTypeService->updateMeetingType($meetingType, $request->validated());
        return redirect()->route('meeting-types.index')->with('success', 'Meeting Type updated successfully.');
    }

    public function destroy(MeetingType $meetingType)
    {
        $this->meetingTypeService->deleteMeetingType($meetingType);
        return redirect()->route('meeting-types.index')->with('success', 'Meeting Type deleted successfully.');
    }
}
