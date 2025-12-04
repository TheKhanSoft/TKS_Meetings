<?php

namespace App\Support;

use App\Models\AgendaItem;
use App\Models\Minute;
use Illuminate\Http\Request;

class Spotlight
{
    public function search(Request $request)
    {
        $search = $request->input('search');

        if (!$search) {
            return [];
        }

        // Search Agenda Items
        $agendaItems = AgendaItem::query()
            ->where('title', 'like', "%$search%")
            ->orWhere('details', 'like', "%$search%")
            ->orWhereHas('keywords', function ($q) use ($search) {
            $q->where('name', 'like', "%$search%");
            })
            ->take(5)
            ->get()
            ->map(function (AgendaItem $item) {
            $meeting = $item->meeting ?? null;
            $meetingNumber = optional($meeting)->number;
            $meetingType = optional($meeting)->meetingType->code;
            $meetingDateRaw = optional($meeting)->date;
            $meetingDate = null;
            if ($meetingDateRaw) {
                try {
                $meetingDate = \Carbon\Carbon::parse($meetingDateRaw)->format('jS M Y');
                } catch (\Exception $e) {
                $meetingDate = (string) $meetingDateRaw;
                }
            }

            $parts = ['Agenda item of the ',];
            if ($meetingNumber) $parts[] = $meetingNumber . ' meeting of';
            if ($meetingType) $parts[] = $meetingType;
            $description = trim(implode(' ', $parts));
            $description = $description . ($meetingDate ? ' held on ' . $meetingDate : '');
            if ($description === '') {
                $description = 'Agenda Item';
            }

            return [
                'name' => $item->title,
                'description' => $description,
                'link' => route('agenda-items.index', ['view' => $item->id]),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
            ];
            });

        // Search Minutes
        $minutes = Minute::query()
            ->where('decision', 'like', "%$search%")
            ->orWhere('action_required', 'like', "%$search%")
            ->orWhereHas('keywords', function ($q) use ($search) {
            $q->where('name', 'like', "%$search%");
            })
            ->take(5)
            ->get()
            ->map(function (Minute $item) {
            // Try to get a meaningful label. Minute doesn't have a title, so we use decision or related agenda item title.
            $label = $item->decision ? str($item->decision)->limit(50) : 'Minute #' . $item->id;

            $meeting = $item->agendaItem->meeting ?? null;
            $meetingNumber = optional($meeting)->number;
            $meetingType = optional($meeting)->meetingType->code;
            $meetingDateRaw = optional($meeting)->date;
            $meetingDate = null;
            if ($meetingDateRaw) {
                try {
                $meetingDate = \Carbon\Carbon::parse($meetingDateRaw)->format('jS M Y');
                } catch (\Exception $e) {
                $meetingDate = (string) $meetingDateRaw;
                }
            }

            $parts = ['Minute of the ',];
            if ($meetingNumber) $parts[] = $meetingNumber . ' meeting of';
            if ($meetingType) $parts[] = $meetingType;
            $description = trim(implode(' ', $parts));
            $description = $description . ($meetingDate ? ' held on ' . $meetingDate : '');
            if ($description === '') {
                $description = 'Minute';
            }

            return [
                'name' => $label,
                'description' => $description,
                'link' => route('minutes.index', ['view' => $item->id]),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            ];
            });

        return $agendaItems->merge($minutes)->all();
    }
}
