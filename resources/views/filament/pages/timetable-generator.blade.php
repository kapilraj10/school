<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
    </form>

    @if($generationResult)
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
</x-filament-panels::page>
