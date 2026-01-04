{{-- File: resources/views/filament/pages/teacher-requirements.blade.php --}}
<x-filament-panels::page class="!max-w-full">
    <div class="space-y-6">
        {{-- Settings Form --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-funnel class="w-5 h-5" />
                    Analysis Options
                </div>
            </x-slot>
            
            <form wire:submit.prevent class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">View Mode</label>
                    <select wire:model.live="viewMode" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="by_class">By Class (All Subjects)</option>
                        <option value="by_subject">By Subject (All Classes)</option>
                        <option value="single_class">Single Class Details</option>
                    </select>
                </div>
                
                @if($viewMode === 'single_class')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Class</label>
                    <select wire:model.live="selectedClassId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">-- Select Class --</option>
                        @foreach(\App\Models\ClassRoom::active()->get()->sortBy(fn($c) => (int) filter_var($c->name, FILTER_SANITIZE_NUMBER_INT) * 100 + ord($c->section)) as $class)
                            <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </form>
        </x-filament::section>

        {{-- Current Settings Info --}}
        @if($settings)
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-cog-6-tooth class="w-5 h-5" />
                    Current Timetable Settings
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    <span class="text-gray-500 dark:text-gray-400">School Days:</span>
                    <span class="font-medium ml-2">{{ implode(', ', $settings['school_days'] ?? []) }}</span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    <span class="text-gray-500 dark:text-gray-400">Periods Per Day:</span>
                    <span class="font-medium ml-2">{{ $settings['periods_per_day'] ?? 8 }}</span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    <span class="text-gray-500 dark:text-gray-400">Max Same Subject/Day:</span>
                    <span class="font-medium ml-2">{{ $settings['max_same_subject_per_day'] ?? 2 }}</span>
                </div>
            </div>
        </x-filament::section>
        @endif

        {{-- Analysis Results --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
                    Teacher Availability Requirements
                    @if($analysisData)
                        <span class="text-sm font-normal text-gray-500">({{ count($analysisData) }} rules)</span>
                    @endif
                </div>
            </x-slot>

            @if($analysisData && count($analysisData) > 0)
                <div class="space-y-4">
                    @if($viewMode === 'by_subject')
                        {{-- By Subject View --}}
                        @foreach($analysisData as $item)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $item['subject_name'] }}
                                            </span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $item['class_range'] ?? '' }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ ($item['single_combined'] ?? 'single') === 'combined' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-200' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' }}">
                                                {{ ucfirst($item['single_combined'] ?? 'single') }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-gray-700 dark:text-gray-300 text-sm mb-3">
                                            <x-heroicon-o-light-bulb class="w-4 h-4 inline text-yellow-500" />
                                            {{ $item['rule_text'] }}
                                        </p>
                                        
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                            <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                                <span class="text-gray-500 dark:text-gray-400 block">Classes:</span>
                                                <span class="font-semibold">{{ $item['class_count'] ?? 1 }} classes</span>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                                <span class="text-gray-500 dark:text-gray-400 block">Total Periods:</span>
                                                <span class="font-semibold">{{ $item['total_min_periods'] ?? $item['min_periods_per_week'] ?? 0 }} - {{ $item['total_max_periods'] ?? $item['max_periods_per_week'] ?? 0 }}/week</span>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                                <span class="text-gray-500 dark:text-gray-400 block">Days Needed:</span>
                                                <span class="font-semibold">{{ $item['min_days_needed'] ?? 1 }} days</span>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                                <span class="text-gray-500 dark:text-gray-400 block">Periods/Day:</span>
                                                <span class="font-semibold">{{ $item['periods_per_day_needed'] ?? $item['periods_per_day_avg'] ?? 1 }}+</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4 text-right">
                                        <div class="text-sm font-medium {{ ($item['teacher_id'] ?? null) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            <x-heroicon-o-user class="w-4 h-4 inline" />
                                            {{ $item['teacher'] ?? 'Not Assigned' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- By Class View --}}
                        @php
                            $grouped = collect($analysisData)->groupBy(fn($item) => $item['class_name'] ?? $item['class_number']);
                        @endphp
                        
                        @foreach($grouped as $className => $items)
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <x-heroicon-o-academic-cap class="w-5 h-5 text-primary-500" />
                                    {{ $className }}
                                    @if(isset($items[0]['sections']))
                                        <span class="text-sm font-normal text-gray-500">(Sections: {{ implode(', ', $items[0]['sections'] ?? []) }})</span>
                                    @endif
                                </h3>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subject</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Periods/Week</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Days Needed</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Periods</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Teacher</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Requirement Rule</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($items as $item)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ $item['subject_name'] ?? 'Unknown' }}</span>
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs {{ ($item['single_combined'] ?? 'single') === 'combined' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-200' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200' }}">
                                                                {{ ucfirst($item['single_combined'] ?? 'single') }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                                        <span class="text-sm">
                                                            <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $item['min_periods_per_week'] ?? 0 }}</span>
                                                            <span class="text-gray-400">-</span>
                                                            <span class="text-gray-600 dark:text-gray-300">{{ $item['max_periods_per_week'] ?? 0 }}</span>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                            {{ $item['min_days_needed'] ?? 1 }} days
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                                        <span class="text-xs text-gray-600 dark:text-gray-400">
                                                            {{ implode(', ', $item['periods_needed'] ?? []) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <span class="{{ ($item['teacher_id'] ?? null) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} text-sm">
                                                            {{ $item['teacher'] ?? 'Not Assigned' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 max-w-md">
                                                            {{ $item['rule_text'] ?? '' }}
                                                        </p>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @else
                <div class="text-center py-12">
                    <x-heroicon-o-clipboard-document-list class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Data Available</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        @if($viewMode === 'single_class' && !$selectedClassId)
                            Please select a class to view requirements.
                        @else
                            No class subject settings found. Please configure subjects for classes first.
                        @endif
                    </p>
                </div>
            @endif
        </x-filament::section>

        {{-- Legend --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5" />
                    How to Read This
                </div>
            </x-slot>
            
            <div class="prose dark:prose-invert max-w-none text-sm">
                <ul class="space-y-2">
                    <li><strong>Periods/Week:</strong> Shows min-max range. The minimum is what must be assigned, max is the upper limit.</li>
                    <li><strong>Days Needed:</strong> Calculated based on max {{ $settings['max_same_subject_per_day'] ?? 2 }} same-subject periods per day.</li>
                    <li><strong>Periods:</strong> Which period slots the teacher should ideally be available for.</li>
                    <li><strong>Single:</strong> Subject taught to one class at a time.</li>
                    <li><strong>Combined:</strong> Subject taught to multiple classes together (e.g., Sports, Music).</li>
                    <li><strong class="text-green-600 dark:text-green-400">Green teacher name:</strong> Teacher is assigned.</li>
                    <li><strong class="text-red-600 dark:text-red-400">Red "Not Assigned":</strong> No teacher assigned for this subject.</li>
                </ul>
                
                <p class="mt-4 text-gray-600 dark:text-gray-400">
                    <strong>Example interpretation:</strong> If a subject needs 5 periods/week and max 2 per day, the teacher needs to be available at least 3 days (⌈5/2⌉ = 3).
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
