{{-- File: resources/views/filament/components/generation-summary.blade.php --}}
<div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
    @if($term && $classes->count() > 0)
        <div class="space-y-4">
            <div>
                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Selected Configuration:</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Academic Term:</span>
                        <span class="font-medium ml-2">{{ $term->name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Classes:</span>
                        <span class="font-medium ml-2">{{ $classes->count() }} selected</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Period:</span>
                        <span class="font-medium ml-2">{{ $term->start_date->format('M d, Y') }} - {{ $term->end_date->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Settings:</h4>
                <div class="space-y-2 text-sm">
                    @if($settings['respect_teacher_availability'] ?? false)
                        <div class="flex items-center text-green-600 dark:text-green-400">
                            <x-heroicon-o-check-circle class="w-4 h-4 mr-2" />
                            <span>Teacher availability will be respected</span>
                        </div>
                    @endif
                    @if($settings['avoid_consecutive_subjects'] ?? false)
                        <div class="flex items-center text-green-600 dark:text-green-400">
                            <x-heroicon-o-check-circle class="w-4 h-4 mr-2" />
                            <span>Avoiding consecutive same subjects</span>
                        </div>
                    @endif
                    @if($settings['balance_daily_load'] ?? false)
                        <div class="flex items-center text-green-600 dark:text-green-400">
                            <x-heroicon-o-check-circle class="w-4 h-4 mr-2" />
                            <span>Daily load will be balanced</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="text-center text-gray-500 dark:text-gray-400">
            <p>Please select an academic term and at least one class to continue.</p>
        </div>
    @endif
</div>
