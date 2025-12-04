<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Models\Meeting;
use App\Services\MeetingService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    protected $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    public function index()
    {
        return view('livewire.meetings.index');
    }

    public function store(MeetingRequest $request)
    {
        $this->meetingService->createMeeting($request->validated());
        return redirect()->route('meetings.index')->with('success', 'Meeting created successfully.');
    }

    public function update(MeetingRequest $request, Meeting $meeting)
    {
        $this->meetingService->updateMeeting($meeting, $request->validated());
        return redirect()->route('meetings.index')->with('success', 'Meeting updated successfully.');
    }

    public function destroy(Meeting $meeting)
    {
        $this->meetingService->deleteMeeting($meeting);
        return redirect()->route('meetings.index')->with('success', 'Meeting deleted successfully.');
    }

    public function downloadMinutes($id, $format = 'pdf')
    {
        $meeting = Meeting::with(['meetingType', 'director', 'registrar', 'vc', 'agendaItems.minutes'])->find($id);

        if (!$meeting) {
            abort(404);
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.meeting-minutes-pdf', ['meeting' => $meeting]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'minutes-meeting-' . $meeting->number . '.pdf');
        } elseif ($format === 'docx') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            
            // Header
            $headerText = "MINUTES OF THE MEETING OF " . $meeting->number . " MEETING OF THE " . ($meeting->meetingType->name ?? 'COMMITTEE') . "\nHELD ON " . ($meeting->date ? $meeting->date->format('F d, Y') : 'DATE');
            $section->addText($headerText, ['bold' => true, 'size' => 12], ['align' => 'center']);
            $section->addTextBreak(1);

            // Intro
            $intro = "The " . $meeting->number . " meeting of the " . ($meeting->meetingType->name ?? 'Committee') . " was held on " . ($meeting->date ? $meeting->date->format('F d, Y') : '') . ", at " . ($meeting->time ? $meeting->time->format('h:i A') : '') . " in the Committee Room, with " . ($meeting->vc->name ?? 'the Vice Chancellor') . ", in the Chair.";
            $section->addText($intro, ['align' => 'both']);
            $section->addTextBreak(1);

            // Attendees
            $section->addText("The meeting was attended by the following members:", ['bold' => false]);
            if ($meeting->director) {
                $section->addListItem($meeting->director->name . ", " . ($meeting->director->designation ?? 'Director'), 0);
            }
            if ($meeting->registrar) {
                $section->addListItem($meeting->registrar->name . ", " . ($meeting->registrar->designation ?? 'Registrar'), 0);
            }
            
            foreach ($meeting->members as $member) {
                if ($member instanceof \App\Models\User && in_array($member->id, [$meeting->director_id, $meeting->registrar_id, $meeting->vc_id])) {
                    continue;
                }
                $text = "";
                if ($member instanceof \App\Models\Participant && $member->title) {
                    $text .= $member->title . " ";
                }
                $text .= $member->name;
                if ($member instanceof \App\Models\Participant && $member->organization) {
                    $text .= " (" . $member->organization . ")";
                }
                $section->addListItem($text, 0);
            }

            if ($meeting->attendees->count() > 0) {
                $section->addTextBreak(1);
                $section->addText("The following also attended:", ['bold' => true]);
                foreach ($meeting->attendees as $attendee) {
                    $text = "";
                    if ($attendee instanceof \App\Models\Participant && $attendee->title) {
                        $text .= $attendee->title . " ";
                    }
                    $text .= $attendee->name;
                    if ($attendee instanceof \App\Models\Participant && $attendee->organization) {
                        $text .= " (" . $attendee->organization . ")";
                    }
                    $section->addListItem($text, 0);
                }
            }
            $section->addTextBreak(1);

            // Items
            foreach ($meeting->agendaItems as $item) {
                $section->addText("Item No. " . $item->sequence_number . ": " . strtoupper($item->title), ['bold' => true, 'underline' => 'single']);
                $section->addText("Discussion:", ['bold' => true]);
                $section->addText($item->details);
                
                foreach ($item->minutes as $minute) {
                    $section->addText("Decision:", ['bold' => true]);
                    $section->addText($minute->decision);
                    if ($minute->action_required) {
                        $section->addText("Action Required:", ['bold' => true]);
                        $section->addText($minute->action_required);
                    }
                }
                $section->addTextBreak(1);
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            return response()->streamDownload(function () use ($objWriter) {
                $objWriter->save('php://output');
            }, 'minutes-meeting-' . $meeting->number . '.docx');
        }
    }

    public function viewMinutes($id)
    {
        $meeting = Meeting::with(['meetingType', 'director', 'registrar', 'vc', 'agendaItems.minutes'])->find($id);

        if (!$meeting) {
            abort(404);
        }

        $pdf = Pdf::loadView('exports.meeting-minutes-pdf', ['meeting' => $meeting]);
        return response()->stream(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="minutes-meeting-' . $meeting->number . '.pdf"'
        ]);
    }

    public function downloadAgenda($id, $format = 'pdf')
    {
        $meeting = Meeting::with(['meetingType', 'agendaItems.agendaItemType'])->find($id);

        if (!$meeting) {
            abort(404);
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.meeting-agenda-pdf', ['meeting' => $meeting]);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'agenda-meeting-' . $meeting->number . '.pdf');
        } elseif ($format === 'docx') {
             $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            
            // Header
            $headerText = "AGENDA OF THE " . $meeting->number . " MEETING OF THE " . ($meeting->meetingType->name ?? 'COMMITTEE') . "\nTO BE HELD ON " . ($meeting->date ? $meeting->date->format('F d, Y') : 'DATE');
            $section->addText($headerText, ['bold' => true, 'size' => 12], ['align' => 'center']);
            $section->addTextBreak(1);

            // Intro
            $intro = "The " . $meeting->number . " meeting of the " . ($meeting->meetingType->name ?? 'Committee') . " is scheduled to be held on " . ($meeting->date ? $meeting->date->format('F d, Y') : '') . ", at " . ($meeting->time ? $meeting->time->format('h:i A') : '') . " in the Committee Room.";
            $section->addText($intro, ['align' => 'both']);
            $section->addTextBreak(1);

            // Members
            $section->addText("MEMBERS:", ['bold' => true, 'underline' => 'single']);
            foreach ($meeting->members as $member) {
                $text = "";
                if ($member instanceof \App\Models\Participant && $member->title) {
                    $text .= $member->title . " ";
                }
                $text .= $member->name;
                $section->addListItem($text);
            }
            $section->addTextBreak(1);

            $groupedItems = $meeting->agendaItems->groupBy(function($item) {
                return $item->agendaItemType->name ?? 'General';
            });
            $order = ['Normal', 'Additional', 'Special', 'Table'];
            $sortedGroups = $groupedItems->sortBy(function($items, $key) use ($order) {
                $index = array_search($key, $order);
                return $index === false ? 999 : $index;
            });

            foreach ($sortedGroups as $type => $items) {
                $section->addText(strtoupper($type) . " AGENDA ITEMS", ['bold' => true, 'underline' => 'single', 'size' => 14], ['align' => 'center']);
                $section->addTextBreak(1);
                
                foreach ($items as $item) {
                    $section->addText("Item No. " . $item->sequence_number . ": " . strtoupper($item->title), ['bold' => true, 'underline' => 'single']);
                    $section->addText($item->details);
                    $section->addTextBreak(1);
                }
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            return response()->streamDownload(function () use ($objWriter) {
                $objWriter->save('php://output');
            }, 'agenda-meeting-' . $meeting->number . '.docx');
        }
    }

    public function viewAgenda($id)
    {
        $meeting = Meeting::with(['meetingType', 'agendaItems.agendaItemType'])->find($id);

        if (!$meeting) {
            abort(404);
        }

        $pdf = Pdf::loadView('exports.meeting-agenda-pdf', ['meeting' => $meeting]);
        return response()->stream(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agenda-meeting-' . $meeting->number . '.pdf"'
        ]);
    }
}
