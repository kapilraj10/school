<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        @if($timetableData)
            <x-filament::section>
                <x-slot name="heading">
                    @php
                        $viewType = $timetableData['viewType'] ?? 'class';
                        $entity = $timetableData['entity'] ?? null;
                        $entityTitle = match ($viewType) {
                            'teacher' => $entity?->name,
                            'room' => $entity?->name,
                            default => $entity?->full_name,
                        };
                    @endphp
                    {{ $entityTitle ?? 'Timetable' }} - {{ $timetableData['term']->name }}
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::button wire:click="refreshTimetable" size="sm">
                        <x-heroicon-o-arrow-path class="w-4 h-4 mr-1" />
                        Refresh
                    </x-filament::button>
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800">
                                <th class="border border-gray-300 dark:border-gray-600 p-3 text-left font-semibold">Weekday</th>
                                @foreach($timetableData['periods'] as $period => $periodLabel)
                                    <th class="border border-gray-300 dark:border-gray-600 p-3 text-center font-semibold">
                                        {{ $periodLabel }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timetableData['days'] as $dayNum => $dayName)
                                <tr>
                                    <td class="border border-gray-300 dark:border-gray-600 p-3 font-medium text-center bg-gray-50 dark:bg-gray-800">
                                        {{ $dayName }}
                                    </td>
                                    @foreach($timetableData['periods'] as $period => $periodLabel)
                                        @php
                                            $slot = $timetableData['slots'][$dayNum][$period] ?? null;
                                        @endphp
                                        <td class="border border-gray-300 dark:border-gray-600 p-3 min-w-[150px]">
                                            @if($slot)
                                                <div class="space-y-1">
                                                    <div class="font-semibold text-sm {{ $slot->is_combined ? 'text-purple-600 dark:text-purple-400' : 'text-blue-600 dark:text-blue-400' }}">
                                                        {{ $slot->subject?->code ?? 'N/A' }}
                                                        @if($slot->is_combined)
                                                            <span class="text-xs">(Combined)</span>
                                                        @endif
                                                    </div>
                                                    @if(($timetableData['viewType'] ?? 'class') === 'teacher')
                                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                                            {{ $slot->classRoom?->full_name ?? 'No Class' }}
                                                        </div>
                                                    @elseif(($timetableData['viewType'] ?? 'class') === 'room')
                                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                                            {{ $slot->classRoom?->full_name ?? 'No Class' }}
                                                        </div>
                                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                                            {{ $slot->teacher?->name ?? 'No Teacher' }}
                                                        </div>
                                                    @else
                                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                                            {{ $slot->teacher?->employee_id ?? 'No Teacher' }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-center text-gray-400 dark:text-gray-600 text-sm">
                                                    Free
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-between items-center">
                    <div class="flex space-x-4 text-sm">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
                            <span>Regular Class</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-purple-500 rounded mr-2"></div>
                            <span>Combined Period</span>
                        </div>
                    </div>

                    <x-filament::button 
                        tag="a" 
                        href="{{ route('filament.admin.pages.print-center') }}"
                        color="success"
                        size="sm"
                    >
                        <x-heroicon-o-printer class="w-4 h-4 mr-1" />
                        Print
                    </x-filament::button>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-calendar-days class="w-16 h-16 mx-auto mb-4 opacity-50" />
                    <p class="text-lg">No timetable data available</p>
                    <p class="text-sm mt-2">Please select a term and class to view the timetable</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
