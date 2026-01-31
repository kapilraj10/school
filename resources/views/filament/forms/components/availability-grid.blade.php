@php
    $schoolDays = $getSchoolDays();
    $periodsPerDay = $getPeriodsPerDay();
    $state = $getState() ?? ['days' => [], 'periods' => []];
    $availableDays = $state['days'] ?? [];
    $availablePeriods = $state['periods'] ?? [];
    
    $dayMap = [
        'Sunday' => 'Sun',
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
        'Saturday' => 'Sat',
    ];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        days: @js($availableDays),
        periods: @js($availablePeriods),
        dayMap: @js($dayMap),
        
        isAvailable(day, period) {
            const dayShort = this.dayMap[day] || day;
            return this.days.includes(dayShort) && this.periods.includes(period);
        },
        
        toggle(day, period) {
            const dayShort = this.dayMap[day] || day;
            const isDayAvailable = this.days.includes(dayShort);
            const isPeriodAvailable = this.periods.includes(period);
            const isCurrentlyAvailable = isDayAvailable && isPeriodAvailable;
            
            if (isCurrentlyAvailable) {
                if (this.periods.filter(p => this.days.includes(dayShort)).length === 1) {
                    this.days = this.days.filter(d => d !== dayShort);
                }
                this.periods = this.periods.filter(p => p !== period);
            } else {
                if (!isDayAvailable) {
                    this.days.push(dayShort);
                }
                if (!isPeriodAvailable) {
                    this.periods.push(period);
                }
            }
            
            this.updateState();
        },
        
        toggleDay(day) {
            const dayShort = this.dayMap[day] || day;
            const isDayAvailable = this.days.includes(dayShort);
            
            if (isDayAvailable) {
                this.days = this.days.filter(d => d !== dayShort);
            } else {
                this.days.push(dayShort);
                for (let period = 1; period <= {{ $periodsPerDay }}; period++) {
                    if (!this.periods.includes(period)) {
                        this.periods.push(period);
                    }
                }
            }
            
            this.updateState();
        },
        
        togglePeriod(period) {
            const isPeriodAvailable = this.periods.includes(period);
            
            if (isPeriodAvailable) {
                this.periods = this.periods.filter(p => p !== period);
                this.days = this.days.filter(d => {
                    return this.periods.some(p => true);
                });
            } else {
                this.periods.push(period);
                @foreach($schoolDays as $day)
                    if (!this.days.includes('{{ $dayMap[$day] ?? $day }}')) {
                        this.days.push('{{ $dayMap[$day] ?? $day }}');
                    }
                @endforeach
            }
            
            this.updateState();
        },
        
        updateState() {
            $wire.set('{{ $getStatePath() }}', {
                days: this.days,
                periods: this.periods
            });
        }
    }" class="space-y-4">
        <div class="overflow-x-auto">
            <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider border-r border-gray-300 dark:border-gray-600">
                                Period / Day
                            </th>
                            @foreach($schoolDays as $day)
                                <th class="px-4 py-3 text-center text-xs font-medium border-r last:border-r-0 border-gray-300 dark:border-gray-600">
                                    <button 
                                        type="button"
                                        @click="toggleDay('{{ $day }}')"
                                        class="w-full text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                                        title="Click to toggle entire day"
                                    >
                                        {{ $day }}
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @for($period = 1; $period <= $periodsPerDay; $period++)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm font-medium border-r border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                                    <button 
                                        type="button"
                                        @click="togglePeriod({{ $period }})"
                                        class="w-full text-left text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                                        title="Click to toggle entire period"
                                    >
                                        Period {{ $period }}
                                    </button>
                                </td>
                                @foreach($schoolDays as $day)
                                    <td class="px-2 py-2 text-center border-r last:border-r-0 border-gray-200 dark:border-gray-700">
                                        <button
                                            type="button"
                                            @click="toggle('{{ $day }}', {{ $period }})"
                                            :class="{
                                                'bg-green-100 dark:bg-green-900/30 border-green-500 text-green-700 dark:text-green-300': isAvailable('{{ $day }}', {{ $period }}),
                                                'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700 text-red-600 dark:text-red-400': !isAvailable('{{ $day }}', {{ $period }})
                                            }"
                                            class="w-full px-3 py-2 rounded border-2 transition-all hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        >
                                            <span x-show="isAvailable('{{ $day }}', {{ $period }})" class="flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                            <span x-show="!isAvailable('{{ $day }}', {{ $period }})" class="flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <p><strong>Quick Tips:</strong></p>
            <ul class="list-disc list-inside space-y-1">
                <li>Click any cell to toggle availability for that specific day and period</li>
                <li>Click a day header to toggle all periods for that day</li>
                <li>Click a period row header to toggle that period for all days</li>
            </ul>
        </div>
    </div>
</x-dynamic-component>
