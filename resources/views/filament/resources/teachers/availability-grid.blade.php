<div class="overflow-x-auto">
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
        
        $availableDaysShort = array_map(function($day) use ($dayMap) {
            return $dayMap[$day] ?? $day;
        }, $availableDays);
    @endphp

    <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider border-r border-gray-300 dark:border-gray-600">
                        Period / Day
                    </th>
                    @foreach($schoolDays as $day)
                        @php
                            $dayShort = $dayMap[$day] ?? $day;
                        @endphp
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider border-r last:border-r-0 border-gray-300 dark:border-gray-600">
                            {{ $day }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @for($period = 1; $period <= $periodsPerDay; $period++)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 border-r border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                            Period {{ $period }}
                        </td>
                        @foreach($schoolDays as $day)
                            @php
                                $dayShort = $dayMap[$day] ?? $day;
                                $isAvailable = in_array($dayShort, $availableDaysShort) && in_array($period, $availablePeriods);
                            @endphp
                            <td class="px-4 py-3 text-center text-sm border-r last:border-r-0 border-gray-200 dark:border-gray-700">
                                @if($isAvailable)
                                    <div class="inline-flex items-center justify-center w-full">
                                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="ml-2 text-green-600 dark:text-green-400 font-medium">Available</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center justify-center w-full">
                                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="ml-2 text-red-600 dark:text-red-400">Unavailable</span>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
