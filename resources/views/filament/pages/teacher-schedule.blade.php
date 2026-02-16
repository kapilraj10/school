{{-- File: resources/views/filament/pages/teacher-schedule.blade.php --}}
<x-filament-panels::page>
    <style>
        @media print {
            .fi-sidebar,
            .fi-topbar,
            header,
            nav,
            .fi-header-actions,
            .print\\:hidden {
                display: none !important;
            }
            
            body {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .fi-main {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .fi-page {
                padding: 20px !important;
                max-width: 100% !important;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
    
    <div class="space-y-6">
        <form wire:submit.prevent="submit" class="print:hidden">
            {{ $this->form }}
        </form>

        @if($scheduleData)
            <x-filament::section>
                <x-slot name="heading">
                    {{ $scheduleData['teacher']->employee_id ?? 'N/A' }} - {{ $scheduleData['term']->name }}
                </x-slot>

                <x-slot name="description">
                    Total Periods: {{ $scheduleData['total_periods'] }} | 
                    Max per Day: {{ $scheduleData['max_periods_per_day'] }}
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800">
                                <th class="border border-gray-300 dark:border-gray-600 p-3 text-left">Day</th>
                                @foreach($scheduleData['periods'] as $period => $periodLabel)
                                    <th class="border border-gray-300 dark:border-gray-600 p-3 text-center">
                                        {{ $period }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scheduleData['days'] as $dayNum => $dayName)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="border border-gray-300 dark:border-gray-600 p-3 font-medium bg-gray-50 dark:bg-gray-800">
                                        <div>{{ $dayName }}</div>
                                        <div class="text-xs font-normal text-gray-500">
                                            ({{ $scheduleData['periods_per_day'][$dayNum] ?? 0 }} periods)
                                        </div>
                                    </td>
                                    @foreach($scheduleData['periods'] as $period => $periodLabel)
                                        @php
                                            $slot = $scheduleData['slots'][$dayNum][$period] ?? null;
                                        @endphp
                                        <td class="border border-gray-300 dark:border-gray-600 p-3">
                                            @if($slot)
                                                <div class="space-y-1">
                                                    <div class="font-semibold text-sm text-blue-600 dark:text-blue-400">
                                                        {{ $slot->classRoom?->full_name }}
                                                    </div>
                                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                                        {{ $slot->subject?->code ?? 'N/A' }}
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center text-gray-400 text-sm">Free</div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex space-x-4 text-sm">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
                        <span>Regular Class</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-purple-500 rounded mr-2"></div>
                        <span>Combined Period</span>
                    </div>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <p class="text-lg">No schedule data available</p>
                    <p class="text-sm mt-2">Please select a term and teacher to view the schedule</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
