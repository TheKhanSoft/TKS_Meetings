<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Agenda of Meeting</title>
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
        .item {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .item-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        .content {
            text-align: justify;
            margin-bottom: 10px;
        }
        .group-header {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            text-align: center;
            text-decoration: underline;
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
        ul, ol {
            margin-top: 0;
            margin-bottom: 10px;
            padding-left: 20px;
        }
        li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        AGENDA OF THE {{ $meeting->number }} MEETING OF THE {{ $meeting->meetingType->name ?? 'COMMITTEE' }}<br>
        TO BE HELD ON {{ $meeting->date ? $meeting->date->format('F d, Y') : 'DATE' }}
    </div>

    <div class="footer">
        Page <span class="page-number"></span>
    </div>

    <div class="intro">
        The {{ $meeting->number }} meeting of the {{ $meeting->meetingType->name ?? 'Committee' }} is scheduled to be held on {{ $meeting->date ? $meeting->date->format('F d, Y') : '' }}, at {{ $meeting->time ? $meeting->time->format('h:i A') : '' }} in the Committee Room.
    </div>

    <div style="margin-bottom: 20px;">
        <strong><u>MEMBERS:</u></strong>
        <ol>
            @foreach($meeting->members as $member)
                <li>
                    @if($member instanceof \App\Models\Participant && $member->title)
                        {{ $member->title }}
                    @endif
                    {{ $member->name }}
                </li>
            @endforeach
        </ol>
    </div>

    @php
        $groupedItems = $meeting->agendaItems->groupBy(function($item) {
            return $item->agendaItemType->name ?? 'General';
        });
        // Define custom order if needed, otherwise it's alphabetical or by ID
        $order = ['Normal', 'Additional', 'Special', 'Table'];
        $sortedGroups = $groupedItems->sortBy(function($items, $key) use ($order) {
            $index = array_search($key, $order);
            return $index === false ? 999 : $index;
        });
    @endphp

    @foreach($sortedGroups as $type => $items)
        <div class="group-header">{{ strtoupper($type) }} AGENDA ITEMS</div>
        
        @foreach($items as $item)
            <div class="item">
                <div class="item-title">
                    Item No. {{ $item->sequence_number }}: {{ strtoupper($item->title) }}
                </div>

                <div class="content">
                    {!! $item->details !!}
                </div>
            </div>
        @endforeach
    @endforeach
</body>
</html>