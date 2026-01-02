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
                    {{-- Summary Stats --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="text-2xl font-bold text-red-700 dark:text-red-300">
                                {{ $conflicts['teacher_conflicts']->count() }}
                            </div>
                            <div class="text-xs text-red-600 dark:text-red-400 mt-1">Teacher Conflicts</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800">
                            <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                                {{ $conflicts['unavailable_violations']->count() }}
                            </div>
                            <div class="text-xs text-orange-600 dark:text-orange-400 mt-1">Unavailable Times</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                                {{ $conflicts['overloaded_teachers']->count() }}
                            </div>
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Overloaded</div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                {{ ($conflicts['min_period_violations'] ?? collect())->count() }}
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Below Minimum</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                                {{ ($conflicts['max_period_violations'] ?? collect())->count() }}
                            </div>
                            <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">Above Maximum</div>
                        </div>
                        <div class="bg-pink-50 dark:bg-pink-900/20 p-4 rounded-lg border border-pink-200 dark:border-pink-800">
                            <div class="text-2xl font-bold text-pink-700 dark:text-pink-300">
                                {{ ($conflicts['combined_period_violations'] ?? collect())->count() }}
                            </div>
                            <div class="text-xs text-pink-600 dark:text-pink-400 mt-1">Combined Issues</div>
                        </div>
                    </div>

                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="flex items-center mb-4">
                            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-500 mr-3" />
                            <div>
                                <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">
                                    {{ $conflicts['total_conflicts'] }} Total Violation(s) Found
                                </h3>
                                <p class="text-sm text-red-700 dark:text-red-300">
                                    The timetable has conflicts and rule violations that need to be resolved
                                </p>
                            </div>
                        </div>

                        @if($conflicts['teacher_conflicts']->count() > 0)
                        <div class="mt-4 space-y-3">
                            <h4 class="font-semibold text-red-900 dark:text-red-100">Teacher Double-Booking Conflicts</h4>
                            @foreach($conflicts['teacher_conflicts'] as $conflict)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800">
                                    <div class="font-semibold text-red-800 dark:text-red-200">
                                        {{ $conflict->data['teacher_name'] ?? 'Unknown Teacher' }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Conflict on {{ \App\Models\TimetableSlot::$days[$conflict->data['day'] ?? 0] ?? "Day {$conflict->data['day']}" }}, Period {{ $conflict->data['period'] ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm mt-2 text-gray-700 dark:text-gray-300">
                                        Assigned to both: <span class="font-medium">{{ $conflict->data['class1'] ?? 'Unknown' }}</span> and 
                                        <span class="font-medium">{{ $conflict->data['class2'] ?? 'Unknown' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    @if($conflicts['unavailable_violations']->count() > 0)
                        <div class="mt-6 bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200 mb-3">
                                Unavailable Period Violations ({{ $conflicts['unavailable_violations']->count() }})
                            </h3>
                            <div class="space-y-3">
                                @foreach($conflicts['unavailable_violations'] as $violation)
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded border border-orange-200 dark:border-orange-800">
                                        <div class="font-semibold text-orange-800 dark:text-orange-200">
                                            {{ $violation->data['teacher_name'] ?? 'Unknown Teacher' }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Scheduled during unavailable period: {{ \App\Models\TimetableSlot::$days[$violation->data['day'] ?? 0] ?? "Day {$violation->data['day']}" }} - Period {{ $violation->data['period'] ?? 'N/A' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($conflicts['overloaded_teachers']->count() > 0)
                        <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-3">
                                Overloaded Teachers ({{ $conflicts['overloaded_teachers']->count() }})
                            </h3>
                            <div class="space-y-3">
                                @foreach($conflicts['overloaded_teachers'] as $teacher)
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded border border-yellow-200 dark:border-yellow-800">
                                        <div class="font-semibold text-yellow-800 dark:text-yellow-200">
                                            {{ $teacher->data['name'] ?? 'Unknown Teacher' }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Assigned {{ $teacher->data['assigned_periods'] ?? 0 }} periods (Max: {{ $teacher->data['max_periods_per_week'] ?? 0 }})
                                            - <span class="font-semibold text-yellow-700 dark:text-yellow-300">{{ ($teacher->data['assigned_periods'] ?? 0) - ($teacher->data['max_periods_per_week'] ?? 0) }} over limit</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(($conflicts['min_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-3 flex items-center">
                                <x-heroicon-o-arrow-trending-down class="w-5 h-5 mr-2" />
                                Minimum Period Violations ({{ $conflicts['min_period_violations']->count() }})
                            </h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                                Subjects with fewer periods than required by class subject settings
                            </p>
                            <div class="space-y-3">
                                @foreach($conflicts['min_period_violations'] as $violation)
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded border border-blue-200 dark:border-blue-800">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-semibold text-blue-800 dark:text-blue-200">
                                                    {{ $violation->data['subject_name'] ?? 'Unknown' }} - {{ $violation->data['class_name'] ?? 'Unknown' }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    Assigned: <span class="font-semibold">{{ $violation->data['assigned'] ?? 0 }}</span> periods
                                                    | Required minimum: <span class="font-semibold text-blue-700 dark:text-blue-300">{{ $violation->data['minimum'] ?? 0 }}</span> periods
                                                </div>
                                            </div>
                                            <div class="bg-blue-100 dark:bg-blue-900 px-3 py-1 rounded-full">
                                                <span class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                                                    -{{ $violation->data['deficit'] ?? 0 }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(($conflicts['max_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-6 bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-purple-800 dark:text-purple-200 mb-3 flex items-center">
                                <x-heroicon-o-arrow-trending-up class="w-5 h-5 mr-2" />
                                Maximum Period Violations ({{ $conflicts['max_period_violations']->count() }})
                            </h3>
                            <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">
                                Subjects with more periods than allowed by class subject settings
                            </p>
                            <div class="space-y-3">
                                @foreach($conflicts['max_period_violations'] as $violation)
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded border border-purple-200 dark:border-purple-800">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="font-semibold text-purple-800 dark:text-purple-200">
                                                    {{ $violation->data['subject_name'] ?? 'Unknown' }} - {{ $violation->data['class_name'] ?? 'Unknown' }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    Assigned: <span class="font-semibold">{{ $violation->data['assigned'] ?? 0 }}</span> periods
                                                    | Maximum allowed: <span class="font-semibold text-purple-700 dark:text-purple-300">{{ $violation->data['maximum'] ?? 0 }}</span> periods
                                                </div>
                                            </div>
                                            <div class="bg-purple-100 dark:bg-purple-900 px-3 py-1 rounded-full">
                                                <span class="text-sm font-semibold text-purple-800 dark:text-purple-200">
                                                    +{{ $violation->data['excess'] ?? 0 }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(($conflicts['combined_period_violations'] ?? collect())->count() > 0)
                        <div class="mt-6 bg-pink-50 dark:bg-pink-900/20 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-pink-800 dark:text-pink-200 mb-3 flex items-center">
                                <x-heroicon-o-link class="w-5 h-5 mr-2" />
                                Combined Period Violations ({{ $conflicts['combined_period_violations']->count() }})
                            </h3>
                            <p class="text-sm text-pink-700 dark:text-pink-300 mb-3">
                                Combined subjects should have adjacent periods
                            </p>
                            <div class="space-y-3">
                                @foreach($conflicts['combined_period_violations'] as $violation)
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded border border-pink-200 dark:border-pink-800">
                                        <div class="font-semibold text-pink-800 dark:text-pink-200">
                                            {{ $violation->data['subject_name'] ?? 'Unknown' }} - {{ $violation->data['class_name'] ?? 'Unknown' }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $violation->data['issue'] ?? 'Combined period issue' }}
                                        </div>
                                        <div class="text-xs text-pink-700 dark:text-pink-300 mt-2 font-mono bg-pink-100 dark:bg-pink-900/50 p-2 rounded">
                                            {{ $violation->data['details'] ?? '' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-lg">No data available</p>
                    <p class="text-sm mt-2">Please select an academic term to check for conflicts</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
