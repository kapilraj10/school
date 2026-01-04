<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Timetable - {{ $term->name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 8px;
            line-height: 1.2;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #333;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            margin: 3px 0;
        }

        .subtitle {
            font-size: 10px;
            color: #555;
        }

        .classes-overview {
            margin-bottom: 15px;
        }

        .class-timetable {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .class-header {
            background-color: #333;
            color: white;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 5px;
        }

        .timetable {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .timetable th,
        .timetable td {
            border: 1px solid #333;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            font-size: 7px;
        }

        .timetable thead th {
            background-color: #666;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }

        .timetable tbody th {
            background-color: #e0e0e0;
            font-weight: bold;
            width: 50px;
        }

        .timetable td {
            min-width: 60px;
            height: 35px;
        }

        .slot-mini {
            font-size: 6px;
            line-height: 1.3;
        }

        .subject-mini {
            font-weight: bold;
            margin-bottom: 1px;
        }

        .teacher-mini {
            color: #555;
        }

        .free-period {
            color: #999;
            font-style: italic;
        }

        .page-break {
            page-break-after: always;
        }

        .footer {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 7px;
            color: #666;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ config('app.name', 'School Management System') }}</div>
        <div class="document-title">Master Timetable - All Classes</div>
        <div class="subtitle">{{ $term->name }} ({{ $term->year }})</div>
    </div>

    @foreach($schedules as $classId => $schedule)
        <div class="class-timetable {{ $loop->iteration % 3 === 0 ? 'page-break' : '' }}">
            <div class="class-header">
                {{ $schedule['class']->full_name }}
            </div>

            <table class="timetable">
                <thead>
                    <tr>
                        <th>Day</th>
                        @foreach($periods as $period => $periodLabel)
                            <th>P{{ $period }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $dayNum => $dayName)
                        <tr>
                            <th>{{ substr($dayName, 0, 3) }}</th>
                            @foreach($periods as $period => $periodLabel)
                                @php
                                    $slot = $schedule['slots'][$dayNum][$period] ?? null;
                                @endphp
                                <td>
                                    @if($slot && $slot->subject_id)
                                        <div class="slot-mini">
                                            <div class="subject-mini">{{ $slot->subject?->code ?? 'N/A' }}</div>
                                            <div class="teacher-mini">{{ substr($slot->teacher?->name ?? '', 0, 15) }}</div>
                                        </div>
                                    @else
                                        <div class="free-period">-</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Generated on {{ now()->format('d M Y, h:i A') }} | Total Classes: {{ count($schedules) }}
    </div>
</body>
</html>
