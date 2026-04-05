<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $room->name }} - Room Schedule</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 9px; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 6px; padding-bottom: 5px; border-bottom: 2px solid #333; }
        .school-name { font-size: 14px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; }
        .document-title { font-size: 12px; font-weight: bold; margin: 2px 0; }
        .subtitle { font-size: 10px; color: #555; }
        .meta-info { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 8px; color: #666; }
        .room-info { background-color: #f0f0f0; padding: 4px 8px; margin-bottom: 6px; border-radius: 3px; border-left: 3px solid #22c55e; }
        .room-info-item { display: inline-block; margin-right: 15px; font-size: 8px; }
        .room-info-label { font-weight: bold; margin-right: 3px; }
        .timetable { width: 100%; border-collapse: collapse; margin-bottom: 6px; table-layout: fixed; }
        .timetable th, .timetable td { border: 1px solid #333; padding: 3px 2px; text-align: center; vertical-align: middle; }
        .timetable thead th { background-color: #333; color: #fff; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .timetable tbody th { background-color: #f0f0f0; font-weight: bold; width: 55px; font-size: 8px; }
        .slot-content { display: flex; flex-direction: column; gap: 1px; }
        .subject-name { font-weight: bold; font-size: 8px; color: #000; }
        .class-name, .teacher-name { font-size: 7px; color: #555; }
        .free-period { color: #999; font-style: italic; font-size: 8px; }
        .footer { margin-top: 6px; padding-top: 5px; border-top: 1px solid #ccc; display: flex; justify-content: space-between; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ config('app.name', 'School Management System') }}</div>
        <div class="document-title">Room / Lab Schedule</div>
        <div class="subtitle">{{ $room->name }} - {{ $term->name }}</div>
    </div>

    <div class="meta-info">
        <div>Academic Year: {{ $term->year }}</div>
        <div>Term: {{ ucfirst($term->term) }}</div>
        <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
    </div>

    <div class="room-info">
        <div class="room-info-item"><span class="room-info-label">Code:</span><span>{{ $room->code ?? 'N/A' }}</span></div>
        <div class="room-info-item"><span class="room-info-label">Type:</span><span>{{ ucfirst(str_replace('_', ' ', $room->type ?? 'room')) }}</span></div>
        <div class="room-info-item"><span class="room-info-label">Capacity:</span><span>{{ $room->capacity ?? 'N/A' }}</span></div>
    </div>

    <table class="timetable">
        <thead>
            <tr>
                <th>Day / Period</th>
                @foreach($periods as $period => $periodLabel)
                    <th>{{ $periodLabel }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($days as $dayNum => $dayName)
                <tr>
                    <th>{{ $dayName }}</th>
                    @foreach($periods as $period => $periodLabel)
                        @php $slot = $slots[$dayNum][$period] ?? null; @endphp
                        <td>
                            @if($slot)
                                <div class="slot-content">
                                    <div class="subject-name">{{ $slot->subject?->name ?? 'N/A' }}</div>
                                    <div class="class-name">{{ $slot->classRoom?->full_name ?? 'No Class' }}</div>
                                    <div class="teacher-name">{{ $slot->teacher?->name ?? 'No Teacher' }}</div>
                                </div>
                            @else
                                <div class="free-period">Free</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>Room: {{ $room->name }}</div>
        <div>Total Occupied: {{ $filledSlots }} / {{ $totalSlots }}</div>
        <div>Page 1 of 1</div>
    </div>
</body>
</html>
