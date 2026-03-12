@php
    $schoolDays = $getSchoolDays();
    $periodsPerDay = $getPeriodsPerDay();
    $state = $getState() ?? ['days' => [], 'periods' => [], 'matrix' => []];
    $availableDays = $state['days'] ?? [];
    $availablePeriods = $state['periods'] ?? [];
    $availabilityMatrix = $state['matrix'] ?? [];
    
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
        matrix: @js($availabilityMatrix),
        dayMap: @js($dayMap),
        allDays: @js($schoolDays),
        periodsPerDay: {{ $periodsPerDay }},

        init() {
            // If no matrix stored yet but days/periods exist, hydrate matrix
            if (Object.keys(this.matrix).length === 0 && (this.days.length || this.periods.length)) {
                this.matrix = {};

                this.allDays.forEach((day) => {
                    const short = this.dayMap[day] || day;
                    if (!this.days.includes(short)) {
                        return;
                    }

                    if (!this.matrix[short]) {
                        this.matrix[short] = {};
                    }

                    for (let period = 1; period <= this.periodsPerDay; period++) {
                        if (this.periods.includes(period)) {
                            this.matrix[short][period] = true;
                        }
                    }
                });
            }

            this.recomputeAggregates();
        },

        isAvailable(day, period) {
            const dayShort = this.dayMap[day] || day;
            return !!(this.matrix[dayShort] && this.matrix[dayShort][period]);
        },

        toggle(day, period) {
            const dayShort = this.dayMap[day] || day;

            if (!this.matrix[dayShort]) {
                this.matrix[dayShort] = {};
            }

            this.matrix[dayShort][period] = !this.matrix[dayShort][period];

            this.recomputeAggregates();
            this.updateState();
        },

        toggleDay(day) {
            const dayShort = this.dayMap[day] || day;

            if (!this.matrix[dayShort]) {
                this.matrix[dayShort] = {};
            }

            const row = this.matrix[dayShort];
            const hasAny = Object.values(row).some(Boolean);
            const newValue = !hasAny;

            for (let period = 1; period <= this.periodsPerDay; period++) {
                row[period] = newValue;
            }

            this.matrix[dayShort] = row;

            this.recomputeAggregates();
            this.updateState();
        },

        togglePeriod(period) {
            let hasAny = false;

            Object.values(this.dayMap).forEach((shortOrDay) => {
                const key = shortOrDay;
                const row = this.matrix[key] || {};
                if (row[period]) {
                    hasAny = true;
                }
            });

            const newValue = !hasAny;

            this.allDays.forEach((day) => {
                const short = this.dayMap[day] || day;
                if (!this.matrix[short]) {
                    this.matrix[short] = {};
                }

                this.matrix[short][period] = newValue;
            });

            this.recomputeAggregates();
            this.updateState();
        },

        recomputeAggregates() {
            const daySet = new Set();
            const periodSet = new Set();

            Object.entries(this.matrix).forEach(([dayShort, row]) => {
                const hasAny = Object.values(row).some(Boolean);
                if (!hasAny) {
                    return;
                }

                daySet.add(dayShort);

                Object.entries(row).forEach(([period, value]) => {
                    if (value) {
                        periodSet.add(Number(period));
                    }
                });
            });

            this.days = Array.from(daySet);
            this.periods = Array.from(periodSet).sort((a, b) => a - b);
        },

        updateState() {
            $wire.set('{{ $getStatePath() }}', {
                days: this.days,
                periods: this.periods,
                matrix: this.matrix,
            });
        }
    }" class="space-y-4">

        {{-- Legend --}}
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
                                <button 
                                    type="button"
                                    @click="togglePeriod({{ $period }})"
                                    class="w-full h-full flex items-center justify-center hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                                    title="Toggle entire Period {{ $period }} column"
                                >
                                    Period {{ $period }}
                                </button>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($schoolDays as $day)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-4 py-2 font-medium text-sm text-gray-900 dark:text-gray-200 border-r border-gray-200 dark:border-gray-700 sticky left-0 bg-gray-50 dark:bg-gray-800 z-10">
                                <button 
                                    type="button"
                                    @click="toggleDay('{{ $day }}')"
                                    class="w-full text-left hover:text-primary-600 dark:hover:text-primary-400 transition-colors py-1"
                                    title="Toggle entire {{ $day }} row"
                                >
                                    {{ $day }}
                                </button>
                            </td>
                            @for($period = 1; $period <= $periodsPerDay; $period++)
                                <td class="p-1 text-center border-r last:border-r-0 border-gray-100 dark:border-gray-800">
                                    <button
                                        type="button"
                                        @click="toggle('{{ $day }}', {{ $period }})"
                                        :class="{
                                            'bg-success-100 border-success-500 text-success-700 dark:bg-success-500/20 dark:border-success-500/50 dark:text-success-400': isAvailable('{{ $day }}', {{ $period }}),
                                            'bg-danger-50 border-danger-300 text-danger-700 dark:bg-danger-500/10 dark:border-danger-500/30 dark:text-danger-400': !isAvailable('{{ $day }}', {{ $period }})
                                        }"
                                        class="w-full h-10 rounded text-xs flex items-center justify-center border transition-all duration-200 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary-500 dark:focus:ring-offset-gray-900"
                                    >
                                        <span x-show="isAvailable('{{ $day }}', {{ $period }})" class="flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                        <span x-show="!isAvailable('{{ $day }}', {{ $period }})" class="flex items-center justify-center opacity-60">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    </button>
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
