<x-filament-panels::page>
    <div>

        @if(!$isGenerating)
            <form wire:submit="generateTimetable">
                {{ $this->form }}
            </form>
        @endif

        @if($isGenerating || $progress)
        <div wire:poll.1000ms="tick">
            @php
                $pStatus    = $progress['status']      ?? 'starting';
                $pTotal     = (int)($progress['total']         ?? 0);
                $pCompleted = (int)($progress['completed']     ?? 0);
                $pSuccess   = (int)($progress['success_count'] ?? 0);
                $pSlots     = (int)($progress['total_slots']   ?? 0);
                $pCurrent   = $progress['current_class']       ?? 'Starting...';
                $pStatuses  = $progress['class_statuses']      ?? [];
                $pErrors    = $progress['errors']              ?? [];
                $pWarnings  = $progress['warnings']            ?? [];
                $isDone     = $pStatus === 'completed' || $pStatus === 'failed';
                $pct        = $pTotal > 0 ? round($pCompleted / $pTotal * 100, 1) : 0;
            @endphp

            <div class="mt-6">
                <x-filament::section>
                    <x-slot name="heading">Generation Progress</x-slot>

                    <div class="space-y-4">
                        <div class="{{ !$isDone ? 'bg-blue-50 dark:bg-blue-900/20' : ($pStatus === 'completed' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20') }} p-4 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold {{ !$isDone ? 'text-blue-800 dark:text-blue-200' : ($pStatus === 'completed' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200') }}">
                                    @if(!$isDone) Generating Timetables...
                                    @elseif($pStatus === 'completed') Generation Complete!
                                    @else Generation Failed
                                    @endif
                                </h3>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $pCompleted }} / {{ $pTotal }} classes
                                </span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="relative w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-3">
                                <div class="bg-gradient-to-r from-blue-600 to-blue-500 h-3 rounded-full transition-all duration-500 ease-out" style="width: {{ $pct }}%"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xs font-semibold text-white mix-blend-difference">{{ $pct }}%</span>
                                </div>
                            </div>

                            <!-- Current Status -->
                            <div class="flex items-center gap-3 text-sm mb-4 p-3 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                @if(!$isDone)
                                    <svg class="animate-spin h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                <div class="flex-1">
                                    <span class="font-semibold block">{{ $pCurrent }}</span>
                                    @if(!$isDone)
                                        <span class="text-xs opacity-75">Please wait while timetables are generated in the background...</span>
                                    @elseif($pStatus === 'completed')
                                        <span class="text-xs opacity-75">{{ $pSuccess }} classes generated with {{ $pSlots }} total slots. Redirecting...</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Class-by-Class Status -->
                            @if(!empty($pStatuses))
                                <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Class Status</h4>
                                    <div class="space-y-2 max-h-80 overflow-y-auto">
                                        @foreach($pStatuses as $cs)
                                            @php
                                                $csStatus = $cs['status'] ?? 'pending';
                                                $csColors = match($csStatus) {
                                                    'completed' => 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800',
                                                    'running'   => 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800',
                                                    'failed'    => 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800',
                                                    default     => 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700',
                                                };
                                                $csTextColor = match($csStatus) {
                                                    'completed' => 'text-green-800 dark:text-green-200',
                                                    'running'   => 'text-yellow-800 dark:text-yellow-200',
                                                    'failed'    => 'text-red-800 dark:text-red-200',
                                                    default     => 'text-gray-600 dark:text-gray-400',
                                                };
                                            @endphp
                                            <div class="flex items-center justify-between p-3 rounded-lg border transition-colors {{ $csColors }}">
                                                <div class="flex items-center gap-3">
                                                    @if($csStatus === 'completed')
                                                        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    @elseif($csStatus === 'running')
                                                        <svg class="animate-spin w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    @elseif($csStatus === 'failed')
                                                        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                                    @endif
                                                    <div class="flex-1">
                                                        <span class="text-sm font-semibold block {{ $csTextColor }}">{{ $cs['name'] ?? '' }}</span>
                                                        <span class="text-xs opacity-75 {{ $csTextColor }}">{{ $cs['message'] ?? '' }}</span>
                                                    </div>
                                                </div>
                                                @if($csStatus === 'completed' && ($cs['slots'] ?? 0) > 0)
                                                    <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded-full">{{ $cs['slots'] }} slots</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Errors -->
                            @if(!empty($pErrors))
                                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                    <h4 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">Errors</h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($pErrors as $error)
                                            <li class="text-xs text-red-700 dark:text-red-300">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>
        @endif

    </div>
</x-filament-panels::page>
