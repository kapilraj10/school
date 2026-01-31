<x-filament-panels::page>
    <div @if($isGenerating) wire:poll.500ms="checkGenerationProgress" @endif>
         
        <form wire:submit="generateTimetable">
            {{ $this->form }}
        </form>

        @if($isGenerating)
            <div class="mt-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Generation Progress
                    </x-slot>

                    <div class="space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">
                                    Generating Timetables...
                                </h3>
                                <span class="text-sm text-blue-600 dark:text-blue-400">
                                    {{ $currentClassIndex }} / {{ $totalClasses }} classes
                                </span>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="relative w-full bg-blue-200 dark:bg-blue-800 rounded-full h-3 mb-3">
                                <div class="bg-gradient-to-r from-blue-600 to-blue-500 dark:from-blue-400 dark:to-blue-300 h-3 rounded-full transition-all duration-300 ease-out" 
                                     style="width: {{ $totalClasses > 0 ? round(($currentClassIndex / $totalClasses * 100), 1) : 0 }}%"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xs font-semibold text-white dark:text-gray-900 mix-blend-difference">
                                        {{ $totalClasses > 0 ? round(($currentClassIndex / $totalClasses * 100), 1) : 0 }}%
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Current Status -->
                            <div class="flex items-center gap-3 text-sm text-blue-700 dark:text-blue-300 mb-4 bg-blue-100 dark:bg-blue-900/40 p-3 rounded-lg">
                                <svg class="animate-spin h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div class="flex-1">
                                    <span class="font-semibold block">{{ $currentProgress ?? 'Starting...' }}</span>
                                    <span class="text-xs text-blue-600 dark:text-blue-400">Please wait while we generate timetables...</span>
                                </div>
                            </div>

                            <!-- Class-by-Class Status List -->
                            @if(!empty($classStatuses))
                                <div class="mt-4 border-t border-blue-200 dark:border-blue-700 pt-4">
                                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-3">Class Status</h4>
                                    <div class="space-y-2 max-h-80 overflow-y-auto">
                                        @foreach($classStatuses as $classId => $classStatus)
                                            <div class="flex items-center justify-between p-3 rounded-lg transition-colors {{ 
                                                $classStatus['status'] === 'completed' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800' : 
                                                ($classStatus['status'] === 'running' ? 'bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800' : 
                                                ($classStatus['status'] === 'failed' ? 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800' : 
                                                'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700')) 
                                            }}">
                                                <div class="flex items-center gap-3">
                                                    @if($classStatus['status'] === 'completed')
                                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @elseif($classStatus['status'] === 'running')
                                                        <svg class="animate-spin w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    @elseif($classStatus['status'] === 'failed')
                                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @endif
                                                    
                                                    <div class="flex-1">
                                                        <span class="text-sm font-semibold block {{ 
                                                            $classStatus['status'] === 'completed' ? 'text-green-800 dark:text-green-200' : 
                                                            ($classStatus['status'] === 'running' ? 'text-yellow-800 dark:text-yellow-200' : 
                                                            ($classStatus['status'] === 'failed' ? 'text-red-800 dark:text-red-200' : 
                                                            'text-gray-600 dark:text-gray-400')) 
                                                        }}">
                                                            {{ $classStatus['name'] }}
                                                        </span>
                                                        @if($classStatus['message'])
                                                            <span class="text-xs block {{ 
                                                                $classStatus['status'] === 'completed' ? 'text-green-600 dark:text-green-400' : 
                                                                ($classStatus['status'] === 'running' ? 'text-yellow-600 dark:text-yellow-400' : 
                                                                ($classStatus['status'] === 'failed' ? 'text-red-600 dark:text-red-400' : 
                                                                'text-gray-500 dark:text-gray-400')) 
                                                            }}">
                                                                @if($classStatus['status'] === 'completed')
                                                                    Completed · {{ $classStatus['message'] }}
                                                                @else
                                                                    {{ $classStatus['message'] }}
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                @if($classStatus['status'] === 'completed' && $classStatus['slots'] > 0)
                                                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
                                                        {{ $classStatus['slots'] }} slots
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            </div>
        @endif

        @if($generationResult && !$isGenerating)
            <div class="mt-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Generation Results
                    </x-slot>

                    @if($generationResult['success'])
                        <div class="space-y-4">
                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-2">
                                    ✓ Timetable Generated Successfully!
                                </h3>
                                
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Slots</div>
                                        <div class="text-2xl font-bold">{{ $generationResult['statistics']['total_slots'] }}</div>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Combined Slots</div>
                                        <div class="text-2xl font-bold">{{ $generationResult['statistics']['combined_slots'] }}</div>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Classes</div>
                                        <div class="text-2xl font-bold">{{ $generationResult['statistics']['classes_generated'] }}</div>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Teachers Used</div>
                                        <div class="text-2xl font-bold">{{ $generationResult['statistics']['teachers_used'] }}</div>
                                    </div>
                                </div>
                            </div>

                            @if(count($generationResult['warnings']) > 0)
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                                    <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">⚠ Warnings:</h4>
                                    <ul class="list-disc list-inside space-y-1 text-sm">
                                        @foreach($generationResult['warnings'] as $warning)
                                            <li class="text-yellow-700 dark:text-yellow-300">{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
                                ✗ Generation Failed
                            </h3>
                            
                            @if(count($generationResult['errors']) > 0)
                                <ul class="list-disc list-inside space-y-1 text-sm mt-3">
                                    @foreach($generationResult['errors'] as $error)
                                        <li class="text-red-700 dark:text-red-300">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </x-filament::section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
