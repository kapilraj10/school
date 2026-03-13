<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        @if($timetableData)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div>
                            {{ $timetableData['term']->name }}
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <x-filament::button 
                                wire:click="previousDay" 
                                size="sm" 
                                color="gray"
                                outlined
                            >
                                <x-heroicon-o-chevron-left class="w-4 h-4" />
                            </x-filament::button>
                            
                            <span class="text-lg font-bold px-4">
                                {{ $timetableData['day'] }}
                            </span>
                            
                            <x-filament::button 
                                wire:click="nextDay" 
                                size="sm" 
                                color="gray"
                                outlined
                            >
                                <x-heroicon-o-chevron-right class="w-4 h-4" />
                            </x-filament::button>
                        </div>
                    </div>
                </x-slot>

                {{-- Quick Day Navigation --}}
                <div class="mb-4">
                    <h4 class="text-sm font-semibold mb-2">Quick Day Navigation</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
                        @foreach(\App\Models\TimetableSlot::getDays() as $dayNum => $dayName)
                            <x-filament::button 
                                wire:click="setDay({{ $dayNum }})"
                                color="{{ $currentDay === $dayNum ? 'primary' : 'gray' }}"
                                size="lg"
                                class="w-full"
                            >
                                {{ $dayName }}
                            </x-filament::button>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800">
                                <th class="border border-gray-300 dark:border-gray-600 p-3 text-left font-semibold sticky left-0 bg-gray-100 dark:bg-gray-800 z-10">
                                    {{ $timetableData['rowHeaderLabel'] }}
                                </th>
                                @foreach($timetableData['periods'] as $period => $periodLabel)
                                    <th class="border border-gray-300 dark:border-gray-600 p-3 text-center font-semibold">
                                        {{ $period }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timetableData['rows'] as $rowData)
                                <tr>
                                    <td class="border border-gray-300 dark:border-gray-600 p-3 font-medium bg-gray-50 dark:bg-gray-800 sticky left-0 z-10">
                                        {{ $rowData['label'] }}
                                    </td>
                                    @foreach($timetableData['periods'] as $period => $periodLabel)
                                        @php
                                            $slot = $rowData['periods'][$period] ?? null;
                                            $metaText = $timetableData['viewMode'] === 'teacher'
                                                ? ($slot?->classRoom?->full_name ?? ($slot?->class_room_id ? 'Class #'.$slot->class_room_id : 'Class Not Set'))
                                                : ($slot?->teacher?->employee_id ?? ($slot?->teacher_id ? 'Teacher #'.$slot->teacher_id : 'Teacher Not Set'));
                                        @endphp
                                        <td class="border border-gray-300 dark:border-gray-600 p-2 min-w-[150px]">
                                            @if($slot && $slot->subject_id)
                                                <div class="space-y-1">
                                                    <div class="font-semibold text-sm {{ $slot->is_combined ? 'text-purple-600 dark:text-purple-400' : 'text-blue-600 dark:text-blue-400' }}">
                                                        {{ $slot->subject?->code ?? 'N/A' }}
                                                        @if($slot->is_combined)
                                                            <span class="text-xs">(C)</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-600 dark:text-gray-400 line-clamp-1" title="{{ $metaText }}">
                                                        {{ $metaText }}
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center text-gray-500 dark:text-gray-500 text-xs font-medium">
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

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Total Slots: {{ $timetableData['totalSlots'] }} | 
                        Filled: {{ $timetableData['filledSlots'] }}
                    </div>
                </div>
            </x-filament::section>

        @else
            <x-filament::section>
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-calendar-days class="w-16 h-16 mx-auto mb-4 opacity-50" />
                    <p class="text-lg">No timetable data available</p>
                    <p class="text-sm mt-2">Please select an academic term to view the daily timetable</p>
                </div>
            </x-filament::section>
        @endif
    </div>

    <style>
        @media print {
            .fi-topbar,
            .fi-sidebar,
            .fi-page-actions,
            .fi-fo-field-wrp,
            form,
            button {
                display: none !important;
            }
            
            table {
                font-size: 10px;
            }
            
            .sticky {
                position: static !important;
            }
        }
    </style>
</x-filament-panels::page>
