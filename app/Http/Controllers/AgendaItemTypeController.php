<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendaItemTypeRequest;
use App\Models\AgendaItemType;
use App\Services\AgendaItemTypeService;
use Illuminate\Http\Request;

class AgendaItemTypeController extends Controller
{
    protected $agendaItemTypeService;

    public function __construct(AgendaItemTypeService $agendaItemTypeService)
    {
        $this->agendaItemTypeService = $agendaItemTypeService;
    }

    public function index()
    {
        return view('livewire.agenda-item-types.index');
    }

    public function store(AgendaItemTypeRequest $request)
    {
        $this->agendaItemTypeService->createAgendaItemType($request->validated());
        return redirect()->route('agenda-item-types.index')->with('success', 'Agenda Item Type created successfully.');
    }

    public function update(AgendaItemTypeRequest $request, AgendaItemType $agendaItemType)
    {
        $this->agendaItemTypeService->updateAgendaItemType($agendaItemType, $request->validated());
        return redirect()->route('agenda-item-types.index')->with('success', 'Agenda Item Type updated successfully.');
    }

    public function destroy(AgendaItemType $agendaItemType)
    {
        $this->agendaItemTypeService->deleteAgendaItemType($agendaItemType);
        return redirect()->route('agenda-item-types.index')->with('success', 'Agenda Item Type deleted successfully.');
    }
}
