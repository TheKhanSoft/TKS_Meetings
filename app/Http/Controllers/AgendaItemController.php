<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendaItemRequest;
use App\Models\AgendaItem;
use App\Services\AgendaItemService;
use Illuminate\Http\Request;

class AgendaItemController extends Controller
{
    protected $agendaItemService;

    public function __construct(AgendaItemService $agendaItemService)
    {
        $this->agendaItemService = $agendaItemService;
    }

    public function index()
    {
        return view('livewire.agenda-items.index');
    }

    public function store(AgendaItemRequest $request)
    {
        $this->agendaItemService->createAgendaItem($request->validated());
        return redirect()->route('agenda-items.index')->with('success', 'Agenda Item created successfully.');
    }

    public function update(AgendaItemRequest $request, AgendaItem $agendaItem)
    {
        $this->agendaItemService->updateAgendaItem($agendaItem, $request->validated());
        return redirect()->route('agenda-items.index')->with('success', 'Agenda Item updated successfully.');
    }

    public function destroy(AgendaItem $agendaItem)
    {
        $this->agendaItemService->deleteAgendaItem($agendaItem);
        return redirect()->route('agenda-items.index')->with('success', 'Agenda Item deleted successfully.');
    }
}
