{{-- File: resources/views/filament/pages/conflict-checker.blade.php --}}
<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        @if($conflicts)
            <x-filament::section>
                <x-slot name="heading">
                    Conflict Analysis
                </x-slot>

                @if($conflicts['total_conflicts'] === 0)
                    <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg text-center">
                        <x-heroicon-o-check-circle class="w-16 h-16 mx-auto text-green-500 mb-3" />
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-2">
                            No Conflicts Found!
                        </h3>
                        <p class="text-green-700 dark:text-green-300">
                            The timetable meets all requirements and constraints.
                        </p>
                    </div>
                @else
                    {{-- Summary Overview --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800 text-center">
                            <div class="text-3xl font-bold text-red-700 dark:text-red-300">
                                {{ $conflicts['total_conflicts'] }}
                            </div>
                            <div class="text-sm text-red-600 dark:text-red-400 mt-1">Total Violations</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800 text-center">
                            <div class="text-3xl font-bold text-red-700 dark:text-red-300">
                                {{ $conflicts['hard_conflicts'] ?? 0 }}
                            </div>
                            <div class="text-sm text-red-600 dark:text-red-400 mt-1">Hard Constraint Violations</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800 text-center">
                            <div class="text-3xl font-bold text-yellow-700 dark:text-yellow-300">
                                {{ $conflicts['soft_conflicts'] ?? 0 }}
                            </div>
                            <div class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">Soft Constraint Violations</div>
                        </div>
                    </div>

                    {{-- ═══════════════ HARD CONSTRAINTS ═══════════════ --}}
                    @if(($conflicts['hard_conflicts'] ?? 0) > 0)
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-red-800 dark:text-red-200 mb-4 flex items-center">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                            Hard Constraint Violations
                        </h2>
                        <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                            These must be resolved for a valid timetable.
                        </p>

                        {{-- Hard constraint summary cards --}}
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3 mb-6">
                            @php
                                $hardCards = [
                                    ['key' => 'teacher_conflicts', 'label' => 'Teacher Conflicts', 'color' => 'red'],
                                    ['key' => 'classroom_conflicts', 'label' => 'Class Conflicts', 'color' => 'red'],
                                    ['key' => 'empty_slot_violations', 'label' => 'Empty Slots', 'color' => 'red'],
                                    ['key' => 'cocurricular_same_day_violations', 'label' => 'Co-Curr Same Day', 'color' => 'red'],
                                    ['key' => 'cocurricular_consecutive_violations', 'label' => 'Co-Curr Consecutive', 'color' => 'red'],
                                    ['key' => 'subject_daily_excess_violations', 'label' => 'Subject >2/Day', 'color' => 'red'],
                                    ['key' => 'combined_grade_violations', 'label' => 'Combined Grade', 'color' => 'red'],
                                ];
                            @endphp
                            @foreach($hardCards as $card)
                                <div class="bg-{{ $card['color'] }}-50 dark:bg-{{ $card['color'] }}-900/20 p-3 rounded-lg border border-{{ $card['color'] }}-200 dark:border-{{ $card['color'] }}-800">
                                    <div class="text-2xl font-bold text-{{ $card['color'] }}-700 dark:text-{{ $card['color'] }}-300">
                                        {{ ($conflicts[$card['key']] ?? collect())->count() }}
                                    </div>
                                    <div class="text-xs text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">{{ $card['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3 mb-6">
                            @php
                                $hardCards2 = [
                                    ['key' => 'unavailable_violations', 'label' => 'Unavailable Times', 'color' => 'orange'],
                                    ['key' => 'overloaded_teachers', 'label' => 'Weekly Overload', 'color' => 'orange'],
                                    ['key' => 'daily_overloads', 'label' => 'Daily Overload', 'color' => 'orange'],
                                    ['key' => 'min_period_violations', 'label' => 'Below Minimum', 'color' => 'orange'],
                                    ['key' => 'max_period_violations', 'label' => 'Above Maximum', 'color' => 'orange'],
                                    ['key' => 'combined_period_violations', 'label' => 'Combined Adjacent', 'color' => 'orange'],
                                    ['key' => 'physical_period_violations', 'label' => 'Physical Period', 'color' => 'orange'],
                                    ['key' => 'total_period_violations', 'label' => 'Period Count', 'color' => 'orange'],
                                ];
                            @endphp
                            @foreach($hardCards2 as $card)
                                <div class="bg-{{ $card['color'] }}-50 dark:bg-{{ $card['color'] }}-900/20 p-3 rounded-lg border border-{{ $card['color'] }}-200 dark:border-{{ $card['color'] }}-800">
                                    <div class="text-2xl font-bold text-{{ $card['color'] }}-700 dark:text-{{ $card['color'] }}-300">
                                        {{ ($conflicts[$card['key']] ?? collect())->count() }}
                                    </div>
                                    <div class="text-xs text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">{{ $card['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Teacher Double-Booking --}}
                        @if(($conflicts['teacher_conflicts'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 flex items-center">
                                <x-heroicon-o-user class="w-5 h-5 mr-2" />
                                Teacher Double-Booking ({{ $conflicts['teacher_conflicts']->count() }})
                            </h4>
                            @foreach($conflicts['teacher_conflicts'] as $conflict)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800">
                                    <div class="font-semibold text-red-800 dark:text-red-200">
                                        {{ $conflict->data['teacher_name'] ?? 'Unknown Teacher' }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ \App\Models\TimetableSlot::getDays()[$conflict->data['day'] ?? 0] ?? "Day {$conflict->data['day']}" }}, Period {{ $conflict->data['period'] ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm mt-2 text-gray-700 dark:text-gray-300">
                                        Assigned to both: <span class="font-medium">{{ $conflict->data['class1'] ?? 'Unknown' }}</span> and
                                        <span class="font-medium">{{ $conflict->data['class2'] ?? 'Unknown' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Classroom Double-Booking --}}
                        @if(($conflicts['classroom_conflicts'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 flex items-center">
                                <x-heroicon-o-building-office-2 class="w-5 h-5 mr-2" />
                                Class Double-Booking ({{ $conflicts['classroom_conflicts']->count() }})
                            </h4>
                            @foreach($conflicts['classroom_conflicts'] as $conflict)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800">
                                    <div class="font-semibold text-red-800 dark:text-red-200">
                                        {{ $conflict->data['classroom_name'] ?? 'Unknown' }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ \App\Models\TimetableSlot::getDays()[$conflict->data['day'] ?? 0] ?? "Day" }}, Period {{ $conflict->data['period'] ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm mt-2 text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <span class="font-medium">{{ $conflict->data['subject1'] ?? 'Unknown' }}</span>
                                        <span class="text-gray-400">({{ $conflict->data['teacher1'] ?? '' }})</span>
                                        <span class="text-red-500">⚠</span>
                                        <span class="font-medium">{{ $conflict->data['subject2'] ?? 'Unknown' }}</span>
                                        <span class="text-gray-400">({{ $conflict->data['teacher2'] ?? '' }})</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Empty Slots --}}
                        @if(($conflicts['empty_slot_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 flex items-center">
                                <x-heroicon-o-x-circle class="w-5 h-5 mr-2" />
                                Empty / Missing Slots ({{ $conflicts['empty_slot_violations']->count() }})
                            </h4>
                            <p class="text-sm text-red-700 dark:text-red-300">Each class must have all 48 weekly slots filled (8 periods × 6 days)</p>
                            @foreach($conflicts['empty_slot_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-red-800 dark:text-red-200">{{ $v->data['class_name'] ?? 'Unknown' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Filled: {{ $v->data['filled'] ?? 0 }} / {{ $v->data['expected'] ?? 48 }}
                                            @if(($v->data['missing_slots'] ?? 0) > 0)
                                                | Missing slot records: {{ $v->data['missing_slots'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 px-3 py-1 rounded-full">
                                        <span class="text-sm font-semibold text-red-800 dark:text-red-200">{{ $v->data['empty'] ?? 0 }} empty</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Total Period Violations (8 per day) --}}
                        @if(($conflicts['total_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-orange-900 dark:text-orange-100 flex items-center">
                                <x-heroicon-o-calculator class="w-5 h-5 mr-2" />
                                Period Count Violations ({{ $conflicts['total_period_violations']->count() }})
                            </h4>
                            <p class="text-sm text-orange-700 dark:text-orange-300">Each class must have exactly 8 periods per day</p>
                            @foreach($conflicts['total_period_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-orange-200 dark:border-orange-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-orange-800 dark:text-orange-200">{{ $v->data['class_name'] ?? 'Unknown' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $v->data['day_name'] ?? 'Unknown' }}: {{ $v->data['actual'] ?? 0 }} periods (expected {{ $v->data['expected'] ?? 8 }})
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Co-Curricular Same Day --}}
                        @if(($conflicts['cocurricular_same_day_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 flex items-center">
                                <x-heroicon-o-no-symbol class="w-5 h-5 mr-2" />
                                Multiple Co-Curricular on Same Day ({{ $conflicts['cocurricular_same_day_violations']->count() }})
                            </h4>
                            <p class="text-sm text-red-700 dark:text-red-300">No two different co-curricular subjects should be taught in a single day</p>
                            @foreach($conflicts['cocurricular_same_day_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800">
                                    <div class="font-semibold text-red-800 dark:text-red-200">{{ $v->data['class_name'] ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $v->data['day_name'] ?? 'Unknown' }}: {{ $v->data['subjects'] ?? '' }} ({{ $v->data['count'] ?? 0 }} different co-curricular subjects)
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Co-Curricular Consecutive --}}
                        @if(($conflicts['cocurricular_consecutive_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 flex items-center">
                                <x-heroicon-o-arrows-right-left class="w-5 h-5 mr-2" />
                                Co-Curricular Consecutive Issues ({{ $conflicts['cocurricular_consecutive_violations']->count() }})
                            </h4>
                            <p class="text-sm text-red-700 dark:text-red-300">Co-curricular: max 2 periods/day, must be same subject and consecutive</p>
                            @foreach($conflicts['cocurricular_consecutive_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800">
                                    <div class="font-semibold text-red-800 dark:text-red-200">{{ $v->data['class_name'] ?? 'Unknown' }} – {{ $v->data['day_name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $v->data['issue'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Subject >2 per day --}}
                        @if(($conflicts['subject_daily_excess_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 flex items-center">
                                <x-heroicon-o-arrow-trending-up class="w-5 h-5 mr-2" />
                                Subject Exceeds 2 Periods/Day ({{ $conflicts['subject_daily_excess_violations']->count() }})
                            </h4>
                            @foreach($conflicts['subject_daily_excess_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-red-800 dark:text-red-200">{{ $v->data['subject_name'] ?? 'Unknown' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $v->data['day_name'] ?? '' }}: {{ $v->data['count'] ?? 0 }} periods (max {{ $v->data['max_allowed'] ?? 2 }})</div>
                                    </div>
                                    <div class="bg-red-100 dark:bg-red-900 px-3 py-1 rounded-full">
                                        <span class="text-sm font-semibold text-red-800 dark:text-red-200">+{{ ($v->data['count'] ?? 0) - ($v->data['max_allowed'] ?? 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Combined Grade Violations --}}
                        @if(($conflicts['combined_grade_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100 flex items-center">
                                <x-heroicon-o-link class="w-5 h-5 mr-2" />
                                Combined Subject Grade Violations ({{ $conflicts['combined_grade_violations']->count() }})
                            </h4>
                            <p class="text-sm text-red-700 dark:text-red-300">Combined subjects must be for the same grade, same day, same periods</p>
                            @foreach($conflicts['combined_grade_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800">
                                    <div class="font-semibold text-red-800 dark:text-red-200">{{ $v->data['subject_name'] ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Classes: {{ $v->data['classes'] ?? '' }}</div>
                                    <div class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $v->data['issue'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Physical Period Placement --}}
                        @if(($conflicts['physical_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-orange-900 dark:text-orange-100 flex items-center">
                                <x-heroicon-o-fire class="w-5 h-5 mr-2" />
                                Physical Subject Period Placement ({{ $conflicts['physical_period_violations']->count() }})
                            </h4>
                            <p class="text-sm text-orange-700 dark:text-orange-300">Physical subjects (sports, taekwondo, dance) should be in period 5</p>
                            @foreach($conflicts['physical_period_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-orange-200 dark:border-orange-800">
                                    <div class="font-semibold text-orange-800 dark:text-orange-200">{{ $v->data['subject_name'] ?? 'Unknown' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $v->data['issue'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Unavailable Period Violations --}}
                        @if(($conflicts['unavailable_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-orange-900 dark:text-orange-100 flex items-center">
                                <x-heroicon-o-clock class="w-5 h-5 mr-2" />
                                Teacher Unavailable Violations ({{ $conflicts['unavailable_violations']->count() }})
                            </h4>
                            @foreach($conflicts['unavailable_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-orange-200 dark:border-orange-800">
                                    <div class="font-semibold text-orange-800 dark:text-orange-200">{{ $v->data['teacher_name'] ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Scheduled during unavailable: {{ \App\Models\TimetableSlot::getDays()[$v->data['day'] ?? 0] ?? '' }} – Period {{ $v->data['period'] ?? 'N/A' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Weekly Overload --}}
                        @if(($conflicts['overloaded_teachers'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-orange-900 dark:text-orange-100 flex items-center">
                                <x-heroicon-o-scale class="w-5 h-5 mr-2" />
                                Weekly Overloaded Teachers ({{ $conflicts['overloaded_teachers']->count() }})
                            </h4>
                            @foreach($conflicts['overloaded_teachers'] as $t)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-orange-200 dark:border-orange-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-orange-800 dark:text-orange-200">{{ $t->data['name'] ?? 'Unknown' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Assigned {{ $t->data['assigned_periods'] ?? 0 }} / Max {{ $t->data['max_periods_per_week'] ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="bg-orange-100 dark:bg-orange-900 px-3 py-1 rounded-full">
                                        <span class="text-sm font-semibold text-orange-800 dark:text-orange-200">+{{ ($t->data['assigned_periods'] ?? 0) - ($t->data['max_periods_per_week'] ?? 0) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Daily Overload --}}
                        @if(($conflicts['daily_overloads'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-orange-900 dark:text-orange-100 flex items-center">
                                <x-heroicon-o-calendar-days class="w-5 h-5 mr-2" />
                                Daily Teacher Overload ({{ $conflicts['daily_overloads']->count() }})
                            </h4>
                            <p class="text-sm text-orange-700 dark:text-orange-300">Max 7 periods per day per teacher</p>
                            @foreach($conflicts['daily_overloads'] as $o)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-orange-200 dark:border-orange-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-orange-800 dark:text-orange-200">{{ $o->data['teacher_name'] ?? 'Unknown' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $o->data['day_name'] ?? '' }}: {{ $o->data['assigned_periods'] ?? 0 }} periods (Max: {{ $o->data['max_periods'] ?? 7 }})
                                        </div>
                                    </div>
                                    <div class="bg-orange-100 dark:bg-orange-900 px-3 py-1 rounded-full">
                                        <span class="text-sm font-semibold text-orange-800 dark:text-orange-200">+{{ $o->data['excess'] ?? 0 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Min Period Violations --}}
                        @if(($conflicts['min_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100 flex items-center">
                                <x-heroicon-o-arrow-trending-down class="w-5 h-5 mr-2" />
                                Below Minimum Periods ({{ $conflicts['min_period_violations']->count() }})
                            </h4>
                            @foreach($conflicts['min_period_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-blue-200 dark:border-blue-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-blue-800 dark:text-blue-200">{{ $v->data['subject_name'] ?? '' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Assigned: {{ $v->data['assigned'] ?? 0 }} | Min required: {{ $v->data['minimum'] ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-blue-900 px-3 py-1 rounded-full">
                                        <span class="text-sm font-semibold text-blue-800 dark:text-blue-200">-{{ $v->data['deficit'] ?? 0 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Max Period Violations --}}
                        @if(($conflicts['max_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-purple-900 dark:text-purple-100 flex items-center">
                                <x-heroicon-o-arrow-trending-up class="w-5 h-5 mr-2" />
                                Above Maximum Periods ({{ $conflicts['max_period_violations']->count() }})
                            </h4>
                            @foreach($conflicts['max_period_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-purple-200 dark:border-purple-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-purple-800 dark:text-purple-200">{{ $v->data['subject_name'] ?? '' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Assigned: {{ $v->data['assigned'] ?? 0 }} | Max allowed: {{ $v->data['maximum'] ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="bg-purple-100 dark:bg-purple-900 px-3 py-1 rounded-full">
                                        <span class="text-sm font-semibold text-purple-800 dark:text-purple-200">+{{ $v->data['excess'] ?? 0 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Combined Period Adjacency --}}
                        @if(($conflicts['combined_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-pink-900 dark:text-pink-100 flex items-center">
                                <x-heroicon-o-link class="w-5 h-5 mr-2" />
                                Combined Period Adjacency ({{ $conflicts['combined_period_violations']->count() }})
                            </h4>
                            @foreach($conflicts['combined_period_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-pink-200 dark:border-pink-800">
                                    <div class="font-semibold text-pink-800 dark:text-pink-200">{{ $v->data['subject_name'] ?? '' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $v->data['issue'] ?? '' }}</div>
                                    <div class="text-xs text-pink-700 dark:text-pink-300 mt-2 font-mono bg-pink-100 dark:bg-pink-900/50 p-2 rounded">{{ $v->data['details'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- ═══════════════ SOFT CONSTRAINTS ═══════════════ --}}
                    @if(($conflicts['soft_conflicts'] ?? 0) > 0)
                    <div class="mt-8">
                        <h2 class="text-xl font-bold text-yellow-800 dark:text-yellow-200 mb-4 flex items-center">
                            <x-heroicon-o-light-bulb class="w-6 h-6 mr-2" />
                            Soft Constraint Recommendations
                        </h2>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-4">
                            These are suggestions to improve timetable quality. Not mandatory but recommended.
                        </p>

                        {{-- Soft constraint summary cards --}}
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
                            @php
                                $softCards = [
                                    ['key' => 'subject_daily_balance_violations', 'label' => 'Daily Balance', 'color' => 'yellow'],
                                    ['key' => 'positional_consistency_violations', 'label' => 'Position Consistency', 'color' => 'yellow'],
                                    ['key' => 'core_subject_consistency_violations', 'label' => 'Core Slot Consistency', 'color' => 'yellow'],
                                    ['key' => 'consecutive_heavy_violations', 'label' => 'Consecutive Heavy', 'color' => 'yellow'],
                                    ['key' => 'cocurricular_placement_violations', 'label' => 'Co-Curr Placement', 'color' => 'yellow'],
                                ];
                            @endphp
                            @foreach($softCards as $card)
                                <div class="bg-{{ $card['color'] }}-50 dark:bg-{{ $card['color'] }}-900/20 p-3 rounded-lg border border-{{ $card['color'] }}-200 dark:border-{{ $card['color'] }}-800">
                                    <div class="text-2xl font-bold text-{{ $card['color'] }}-700 dark:text-{{ $card['color'] }}-300">
                                        {{ ($conflicts[$card['key']] ?? collect())->count() }}
                                    </div>
                                    <div class="text-xs text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">{{ $card['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Subject Daily Balance (prefer max 1/day for non-cc) --}}
                        @if(($conflicts['subject_daily_balance_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 flex items-center">
                                <x-heroicon-o-scale class="w-5 h-5 mr-2" />
                                Subject Daily Balance ({{ $conflicts['subject_daily_balance_violations']->count() }})
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Prefer not more than 1 period of the same subject per day</p>
                            @foreach($conflicts['subject_daily_balance_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-yellow-200 dark:border-yellow-800">
                                    <div class="font-semibold text-yellow-800 dark:text-yellow-200">{{ $v->data['subject_name'] ?? '' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $v->data['issue'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Positional Consistency --}}
                        @if(($conflicts['positional_consistency_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 flex items-center">
                                <x-heroicon-o-queue-list class="w-5 h-5 mr-2" />
                                Positional Consistency ({{ $conflicts['positional_consistency_violations']->count() }})
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Subject order should be consistent across days (Sunday as reference)</p>
                            @foreach($conflicts['positional_consistency_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-yellow-200 dark:border-yellow-800 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-yellow-800 dark:text-yellow-200">{{ $v->data['class_name'] ?? '' }} – {{ $v->data['day_name'] ?? '' }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $v->data['mismatches'] ?? 0 }} of {{ $v->data['total'] ?? 0 }} positions differ from Sunday
                                        </div>
                                    </div>
                                    <div class="bg-yellow-100 dark:bg-yellow-900 px-3 py-1 rounded-full">
                                        <span class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">{{ $v->data['percentage'] ?? 0 }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Core Subject Slot Consistency --}}
                        @if(($conflicts['core_subject_consistency_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 flex items-center">
                                <x-heroicon-o-academic-cap class="w-5 h-5 mr-2" />
                                Core Subject Slot Consistency ({{ $conflicts['core_subject_consistency_violations']->count() }})
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Core subjects (English, Math, Science) should be in the same period slot daily</p>
                            @foreach($conflicts['core_subject_consistency_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-yellow-200 dark:border-yellow-800">
                                    <div class="font-semibold text-yellow-800 dark:text-yellow-200">{{ $v->data['subject_name'] ?? '' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $v->data['issue'] ?? '' }}</div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Consecutive Heavy Subjects --}}
                        @if(($conflicts['consecutive_heavy_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 flex items-center">
                                <x-heroicon-o-bolt class="w-5 h-5 mr-2" />
                                Consecutive Heavy Subjects ({{ $conflicts['consecutive_heavy_violations']->count() }})
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Avoid scheduling mentally heavy subjects back-to-back (e.g., Math → Science)</p>
                            @foreach($conflicts['consecutive_heavy_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-yellow-200 dark:border-yellow-800">
                                    <div class="font-semibold text-yellow-800 dark:text-yellow-200">{{ $v->data['class_name'] ?? '' }} – {{ $v->data['day_name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Periods {{ $v->data['periods'] ?? '' }}: {{ $v->data['subject1'] ?? '' }} → {{ $v->data['subject2'] ?? '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Co-Curricular Placement --}}
                        @if(($conflicts['cocurricular_placement_violations'] ?? collect())->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 flex items-center">
                                <x-heroicon-o-paint-brush class="w-5 h-5 mr-2" />
                                Co-Curricular Early Placement ({{ $conflicts['cocurricular_placement_violations']->count() }})
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Co-curricular subjects should be placed in middle or last periods (4–8)</p>
                            @foreach($conflicts['cocurricular_placement_violations'] as $v)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-yellow-200 dark:border-yellow-800">
                                    <div class="font-semibold text-yellow-800 dark:text-yellow-200">{{ $v->data['subject_name'] ?? '' }} – {{ $v->data['class_name'] ?? '' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $v->data['day_name'] ?? '' }}: Period(s) {{ $v->data['period'] ?? '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif
                @endif
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-exclamation-triangle class="w-16 h-16 mx-auto mb-4 opacity-50" />
                    <p class="text-lg">No data available</p>
                    <p class="text-sm mt-2">Please select an academic term to check for conflicts</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
