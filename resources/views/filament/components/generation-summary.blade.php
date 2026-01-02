{{-- File: resources/views/filament/components/generation-summary.blade.php --}}
<div class="space-y-6">
    @if($term && $classes->count() > 0)
        {{-- Main Configuration Summary --}}
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center text-lg">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Selected Configuration
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Academic Term</span>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $term->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $term->start_date->format('M d, Y') }} - {{ $term->end_date->format('M d, Y') }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Classes Selected</span>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $classes->count() }} {{ Str::plural('class', $classes->count()) }}</p>
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($classes as $class)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $class->full_name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    @if(!empty($validation))
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Generation Capacity</h5>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Periods to Generate:</span>
                                    <span class="font-bold text-lg text-blue-600 dark:text-blue-400">{{ $validation['totalPeriodsToGenerate'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Available Subjects:</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $validation['availableSubjects'] ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-400">Active Teachers:</span>
                                    <span class="font-semibold text-purple-600 dark:text-purple-400">{{ $validation['activeTeachers'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        @if(!empty($validation['prioritySubjects']))
                            <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg border border-amber-200 dark:border-amber-700">
                                <div class="flex items-start">
                                    <svg class="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <div class="ml-3">
                                        <p class="text-xs font-semibold text-amber-800 dark:text-amber-200">Priority Subjects Set</p>
                                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                            {{ implode(', ', $validation['prioritySubjects']) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Settings Summary --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Generation Settings
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    @if($settings['respect_teacher_availability'] ?? false)
                        <div class="flex items-center text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium">Respecting teacher availability</span>
                        </div>
                    @else
                        <div class="flex items-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm">Teacher availability not enforced</span>
                        </div>
                    @endif
                    
                    @if($settings['avoid_consecutive_subjects'] ?? false)
                        <div class="flex items-center text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium">Avoiding consecutive same subjects</span>
                        </div>
                    @else
                        <div class="flex items-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm">Consecutive subjects allowed</span>
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    @if($settings['balance_daily_load'] ?? false)
                        <div class="flex items-center text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium">Balancing daily workload</span>
                        </div>
                    @else
                        <div class="flex items-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm">Daily load not balanced</span>
                        </div>
                    @endif
                    
                    @if($settings['clear_existing'] ?? false)
                        <div class="flex items-center text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium">Will clear existing timetables</span>
                        </div>
                    @else
                        <div class="flex items-center text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm">Preserving existing timetables</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Readiness Check --}}
        @if(!empty($validation))
            @php
                $isReady = ($validation['availableSubjects'] ?? 0) > 0 && ($validation['activeTeachers'] ?? 0) > 0;
            @endphp
            
            @if($isReady)
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border-2 border-green-300 dark:border-green-700">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h5 class="font-semibold text-green-800 dark:text-green-200">Ready to Generate</h5>
                            <p class="text-sm text-green-700 dark:text-green-300 mt-1">All requirements are met. You can proceed with timetable generation.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border-2 border-red-300 dark:border-red-700">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h5 class="font-semibold text-red-800 dark:text-red-200">Not Ready</h5>
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                @if(($validation['availableSubjects'] ?? 0) === 0)
                                    No subjects available for selected classes. 
                                @endif
                                @if(($validation['activeTeachers'] ?? 0) === 0)
                                    No active teachers available.
                                @endif
                                Please add the required data before generating.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @else
        <div class="bg-gray-50 dark:bg-gray-800 p-8 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Incomplete Configuration</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Please select an academic term and at least one class to continue.
            </p>
        </div>
    @endif
</div>
