@php
    $days = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
    ];
    $periods = range(1, 8);
    
    // Group slots by day and period for easy lookup
    $slotsByDayPeriod = $slots->mapWithKeys(function ($slot) {
        return ["{$slot->day}_{$slot->period}" => $slot];
    });
@endphp

<div class="overflow-x-auto">
    @if ($slots->isEmpty())
        <div class="p-8 text-center text-gray-500 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="mt-4 text-lg font-medium">No timetable available{{ $className ? ' for ' . $className : '' }}</p>
            <p class="mt-1 text-sm">Please check back later or contact your administrator.</p>
        </div>
    @else
        <table class="w-max min-w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-900">
                    <th class="px-5 py-4 text-left text-[0.85em] font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-r border-gray-300 dark:border-gray-700 min-w-40">
                        Day / Period
                    </th>
                    @foreach ($periods as $period)
                        <th class="px-5 py-4 text-center text-[0.85em] font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-r border-gray-300 dark:border-gray-700 last:border-r-0 min-w-36">
                            Period {{ $period }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($days as $dayNum => $dayName)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <th class="px-5 py-4 text-left text-[0.95em] font-semibold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-300 dark:border-gray-700 min-w-40">
                            {{ $dayName }}
                        </th>
                        @foreach ($periods as $period)
                            @php
                                $slot = $slotsByDayPeriod["{$dayNum}_{$period}"] ?? null;
                            @endphp
                            <td class="px-3 py-4 border-b border-r border-gray-300 dark:border-gray-700 last:border-r-0 min-w-36">
                                @if ($slot)
                                    <div class="space-y-1">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $slot->subject?->code ?? $slot->subject?->name ?? 'N/A' }}
                                        </div>
                                        <div class="text-[0.85em] text-gray-600 dark:text-gray-400 teacher-name">
                                            {{ $slot->teacher?->employee_id ?? $slot->teacher?->name ?? 'No Teacher' }}
                                        </div>
                                        @if ($slot->classRoom && $className && $slot->classRoom->full_name !== $className)
                                            <div class="text-[0.85em] text-purple-600 dark:text-purple-400 class-info">
                                                ({{ $slot->classRoom->full_name }})
                                            </div>
                                        @endif
                                        @if ($slot->type === 'break')
                                            <div class="inline-flex items-center px-2 py-0.5 rounded text-[0.75em] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                                Break
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center text-gray-400 dark:text-gray-600 text-[0.85em]">
                                        -
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
