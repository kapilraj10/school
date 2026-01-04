<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $teacher->name }} - Schedule</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }

        .subtitle {
            font-size: 12px;
            color: #555;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 10px;
            color: #666;
        }

        .teacher-info {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 4px solid #3b82f6;
        }

        .teacher-info-item {
            display: inline-block;
            margin-right: 20px;
            font-size: 10px;
        }

        .teacher-info-label {
            font-weight: bold;
            margin-right: 5px;
        }

        .timetable-container {
            width: 100%;
            overflow: hidden;
        }

        .timetable {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .timetable th,
        .timetable td {
            border: 1px solid #333;
            padding: 8px 5px;
            text-align: center;
            vertical-align: middle;
        }

        .timetable thead th {
            background-color: #333;
            color: white;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        .timetable tbody th {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 80px;
        }

        .timetable td {
            min-width: 90px;
            max-width: 120px;
            height: 60px;
        }

        .slot-content {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .subject-name {
            font-weight: bold;
            font-size: 11px;
            color: #000;
        }

        .class-name {
            font-size: 9px;
            color: #555;
        }

        .subject-code {
            font-size: 8px;
            color: #777;
        }

        .combined-badge {
            display: inline-block;
            background-color: #8b5cf6;
            color: white;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            margin-left: 3px;
        }

        .free-period {
            color: #999;
            font-style: italic;
            font-size: 10px;
        }

        .summary-section {
            margin-top: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .summary-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            font-size: 9px;
        }

        .summary-item {
            background-color: white;
            padding: 8px;
            border-radius: 3px;
            border: 1px solid #e0e0e0;
        }

        .summary-item-label {
            color: #666;
            margin-bottom: 3px;
        }

        .summary-item-value {
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #666;
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
        <div class="document-title">Teacher Schedule</div>
        <div class="subtitle">{{ $teacher->name }} - {{ $term->name }}</div>
    </div>

    <div class="meta-info">
        <div>Academic Year: {{ $term->year }}</div>
        <div>Term: {{ ucfirst($term->term) }}</div>
        <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
    </div>

    <div class="teacher-info">
        <div class="teacher-info-item">
            <span class="teacher-info-label">Teacher ID:</span>
            <span>{{ $teacher->employee_id ?? 'N/A' }}</span>
        </div>
        <div class="teacher-info-item">
            <span class="teacher-info-label">Email:</span>
            <span>{{ $teacher->email ?? 'N/A' }}</span>
        </div>
        <div class="teacher-info-item">
            <span class="teacher-info-label">Phone:</span>
            <span>{{ $teacher->phone ?? 'N/A' }}</span>
        </div>
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
                                        <div class="class-name">
                                            {{ $slot->classRoom?->full_name ?? 'No Class' }}
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

    <div class="summary-section">
        <div class="summary-title">Weekly Summary</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-item-label">Total Classes</div>
                <div class="summary-item-value">{{ $totalSlots }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-item-label">Assigned Classes</div>
                <div class="summary-item-value">{{ $filledSlots }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-item-label">Free Periods</div>
                <div class="summary-item-value">{{ $totalSlots - $filledSlots }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>Teacher: {{ $teacher->name }}</div>
        <div>Teaching Load: {{ $filledSlots }} periods/week</div>
        <div>Page 1 of 1</div>
    </div>
</body>
</html>
