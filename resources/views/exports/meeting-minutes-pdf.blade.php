<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Minutes of Meeting</title>
    <style>
        @page {
            margin: 100px 50px;
        }
        body {
            font-family: "Times New Roman", serif;
            font-size: 12pt;
            line-height: 1.5;
        }
        .header {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .footer {
            position: fixed;
            bottom: -60px;
            left: 0px;
            right: 0px;
            text-align: right;
            font-size: 10pt;
        }
        .page-number:after { content: counter(page); }
        .intro {
            margin-bottom: 20px;
            text-align: justify;
        }
        .attendees {
            margin-bottom: 20px;
        }
        .attendees ol {
            margin-top: 10px;
        }
        .attendees li {
            margin-bottom: 10px;
        }
        .item {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .item-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 10px;
        }
        .content {
            text-align: justify;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header">
        MINUTES OF THE MEETING OF {{ $meeting->number }} MEETING OF THE {{ $meeting->meetingType->name ?? 'COMMITTEE' }}<br>
        HELD ON {{ $meeting->date ? $meeting->date->format('F d, Y') : 'DATE' }}
    </div>

    <div class="footer">
        Page <span class="page-number"></span>
    </div>

    <div class="intro">
        The {{ $meeting->number }} meeting of the {{ $meeting->meetingType->name ?? 'Committee' }} was held on {{ $meeting->date ? $meeting->date->format('F d, Y') : '' }}, at {{ $meeting->time ? $meeting->time->format('h:i A') : '' }} in the Committee Room, with {{ $meeting->vc->name ?? 'the Vice Chancellor' }}, in the Chair.
    </div>

    <div class="attendees">
        The meeting was attended by the following members:
        <ol>
            @if($meeting->director)
                <li>
                    <strong>{{ $meeting->director->name }}</strong>,<br>
                    {{ $meeting->director->designation ?? 'Director' }}
                </li>
            @endif
            @if($meeting->registrar)
                <li>
                    <strong>{{ $meeting->registrar->name }}</strong>,<br>
                    {{ $meeting->registrar->designation ?? 'Registrar' }}
                </li>
            @endif
            
            @foreach($meeting->members as $member)
                @php
                    // Skip if this member is already listed as Director, Registrar, or VC
                    if ($member instanceof \App\Models\User && in_array($member->id, [$meeting->director_id, $meeting->registrar_id, $meeting->vc_id])) {
                        continue;
                    }
                @endphp
                <li>
                    <strong>
                        @if($member instanceof \App\Models\Participant && $member->title)
                            {{ $member->title }}
                        @endif
                        {{ $member->name }}
                    </strong>
                    @if($member instanceof \App\Models\Participant && $member->organization)
                        <br><span>({{ $member->organization }})</span>
                    @endif
                </li>
            @endforeach
        </ol>

        @if($meeting->attendees->count() > 0)
            <div style="margin-top: 10px; font-weight: bold;">The following also attended:</div>
            <ol>
                @foreach($meeting->attendees as $attendee)
                    <li>
                        <strong>
                            @if($attendee instanceof \App\Models\Participant && $attendee->title)
                                {{ $attendee->title }}
                            @endif
                            {{ $attendee->name }}
                        </strong>
                        @if($attendee instanceof \App\Models\Participant && $attendee->organization)
                            <br><span>({{ $attendee->organization }})</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <div class="intro">
        The meeting started with recitation from the Holy Quran. The Chair welcomed all members.
    </div>

    @foreach($meeting->agendaItems as $item)
        <div class="item">
            <div class="item-title">
                Item No. {{ $item->sequence_number }}: {{ strtoupper($item->title) }}
            </div>

            <div class="section-title">Discussion:</div>
            <div class="content">
                {!! nl2br(e($item->details)) !!}
            </div>

            @foreach($item->minutes as $minute)
                <div class="section-title">Decision:</div>
                <div class="content">
                    {!! nl2br(e($minute->decision)) !!}
                </div>
                @if($minute->action_required)
                    <div class="section-title">Action Required:</div>
                    <div class="content">
                        {!! nl2br(e($minute->action_required)) !!}
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach

    {{-- Remove static footer div as it is now fixed --}}
</body>
</html>