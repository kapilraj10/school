@php
    $dayMap = [
        'Sunday' => 'Sun',
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
        'Saturday' => 'Sat',
    ];

    $availableDaysShort = array_map(function ($day) use ($dayMap) {
        return $dayMap[$day] ?? $day;
    }, $availableDays ?? []);

    $availablePeriodsNormalized = array_map('intval', $availablePeriods ?? []);
@endphp

<div class="space-y-3">
    <div class="flex flex-wrap gap-4 items-center justify-end text-sm">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded border bg-success-100 border-success-500 text-success-700 dark:bg-success-500/20 dark:border-success-400 dark:text-success-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            </div>
            <span class="text-gray-600 dark:text-gray-400 font-medium">Available</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded border bg-danger-50 border-danger-400 text-danger-700 dark:bg-danger-500/10 dark:border-danger-500 dark:text-danger-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </div>
            <span class="text-gray-600 dark:text-gray-400 font-medium">Unavailable</span>
        </div>
    </div>

    <div class="w-full overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
        <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 w-32 sticky left-0 z-10 bg-gray-50 dark:bg-gray-800">
                        Day / Period
                    </th>
                    @for($period = 1; $period <= $periodsPerDay; $period++)
                        <th class="px-2 py-3 text-center text-xs font-semibold text-gray-700 dark:text-white border-r last:border-r-0 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                            Period {{ $period }}
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($schoolDays as $day)
                    @php
                        $dayShort = $dayMap[$day] ?? $day;
                    @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-2 font-medium text-sm text-gray-900 dark:text-gray-200 border-r border-gray-200 dark:border-gray-700 sticky left-0 bg-gray-50 dark:bg-gray-800 z-10">
                            {{ $day }}
                        </td>

                        @for($period = 1; $period <= $periodsPerDay; $period++)
                            @php
                                $isAvailable = in_array($dayShort, $availableDaysShort, true)
                                    && in_array($period, $availablePeriodsNormalized, true);
                            @endphp
                            <td class="p-1 text-center border-r last:border-r-0 border-gray-100 dark:border-gray-800">
                                <div class="w-full h-8 rounded border text-xs flex items-center justify-center
                                    {{ $isAvailable
                                        ? 'bg-success-100 border-success-500 text-success-700 dark:bg-success-500/20 dark:border-success-500/50 dark:text-success-400'
                                        : 'bg-danger-50 border-danger-300 text-danger-700 dark:bg-danger-500/10 dark:border-danger-500/30 dark:text-danger-400' }}
                                ">
                                    @if($isAvailable)
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 opacity-70" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
