<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $class->full_name }} - Timetable</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 6px;
            padding-bottom: 5px;
            border-bottom: 2px solid #333;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .document-title {
            font-size: 12px;
            font-weight: bold;
            margin: 2px 0;
        }

        .subtitle {
            font-size: 10px;
            color: #555;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 8px;
            color: #666;
        }

        .timetable-container {
            width: 100%;
            overflow: hidden;
        }

        .timetable {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            table-layout: fixed;
        }

        .timetable th,
        .timetable td {
            border: 1px solid #333;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .timetable thead th {
            background-color: #333;
            color: white;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }

        .timetable tbody th {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 55px;
            font-size: 8px;
        }

        .timetable td {
            font-size: 8px;
        }

        .slot-content {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .subject-name {
            font-weight: bold;
            font-size: 8px;
            color: #000;
        }

        .teacher-name {
            font-size: 7px;
            color: #555;
        }

        .subject-code {
            font-size: 7px;
            color: #777;
        }

        .combined-badge {
            display: inline-block;
            background-color: #8b5cf6;
            color: white;
            padding: 0px 2px;
            border-radius: 2px;
            font-size: 6px;
            margin-left: 2px;
        }

        .free-period {
            color: #999;
            font-style: italic;
            font-size: 8px;
        }

        .footer {
            margin-top: 6px;
            padding-top: 5px;
            border-top: 1px solid #ccc;
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            color: #666;
        }

        .legend {
            margin-top: 5px;
            padding: 4px 6px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        .legend-title {
            font-weight: bold;
            margin-bottom: 2px;
            font-size: 8px;
        }

        .legend-items {
            display: flex;
            gap: 10px;
            font-size: 7px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .legend-box {
            width: 10px;
            height: 10px;
            border: 1px solid #333;
            border-radius: 1px;
        }

        .legend-box.regular {
            background-color: #3b82f6;
        }

        .legend-box.combined {
            background-color: #8b5cf6;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ config('app.name', 'School Management System') }}</div>
        <div class="document-title">Class Timetable</div>
        <div class="subtitle">{{ $class->full_name }} - {{ $term->name }}</div>
    </div>

    <div class="meta-info">
        <div>Academic Year: {{ $term->year }}</div>
        <div>Term: {{ ucfirst($term->term) }}</div>
        <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
    </div>

    <div class="timetable-container">
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
                            @php
                                $slot = $slots[$dayNum][$period] ?? null;
                            @endphp
                            <td>
                                @if($slot)
                                    <div class="slot-content">
                                        <div class="subject-name">
                                            {{ $slot->subject?->name ?? 'N/A' }}
                                            @if($slot->is_combined)
                                                <span class="combined-badge">COMBINED</span>
                                            @endif
                                        </div>
                                        <div class="teacher-name">
                                            {{ $slot->teacher?->name ?? 'No Teacher' }}
                                        </div>
                                        @if($slot->subject?->code)
                                            <div class="subject-code">
                                                {{ $slot->subject->code }}
                                            </div>
                                        @endif
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
    </div>

    <div class="legend">
        <div class="legend-title">Legend:</div>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-box regular"></div>
                <span>Regular Class Period</span>
            </div>
            <div class="legend-item">
                <div class="legend-box combined"></div>
                <span>Combined Period (Multiple Classes)</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>Total Periods: {{ $totalSlots }}</div>
        <div>Filled Periods: {{ $filledSlots }}</div>
        <div>Page 1 of 1</div>
    </div>
</body>
</html>
