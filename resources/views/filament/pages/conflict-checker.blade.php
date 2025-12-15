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
                            All teachers have been scheduled without conflicts.
                        </p>
                    </div>
                @else
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="flex items-center mb-4">
                            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-500 mr-3" />
                            <div>
                                <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">
                                    {{ $conflicts['total_conflicts'] }} Conflict(s) Found
                                </h3>
                                <p class="text-sm text-red-700 dark:text-red-300">
                                    Teachers are assigned to multiple classes at the same time
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach($conflicts['teacher_conflicts'] as $conflict)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-800">
                                    <div class="font-semibold text-red-800 dark:text-red-200">
                                        {{ $conflict->teacher_name }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        Conflict on {{ \App\Models\TimetableSlot::$days[$conflict->day] ?? "Day {$conflict->day}" }}, Period {{ $conflict->period }}
                                    </div>
                                    <div class="text-sm mt-2 text-gray-700 dark:text-gray-300">
                                        Assigned to both: <span class="font-medium">{{ $conflict->class1 }}</span> and 
                                        <span class="font-medium">{{ $conflict->class2 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
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
                                            {{ $violation->teacher_name }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Scheduled during unavailable period: {{ \App\Models\TimetableSlot::$days[$violation->day] ?? "Day {$violation->day}" }} - Period {{ $violation->period }}
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
                                            {{ $teacher->name }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Assigned {{ $teacher->assigned_periods }} periods (Max: {{ $teacher->max_periods_per_week }})
                                            - <span class="font-semibold text-yellow-700 dark:text-yellow-300">{{ $teacher->assigned_periods - $teacher->max_periods_per_week }} over limit</span>
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
