{{-- File: resources/views/filament/components/selection-stats.blade.php --}}
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-6 rounded-lg border-2 border-blue-200 dark:border-blue-700">
    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        Selection Summary
    </h4>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-1">Classes Selected</div>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $classCount }}</div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-1">Weekly Periods</div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $totalPeriods }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">~{{ $totalPeriods }} slots to fill</div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-1">Total Subjects</div>
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $totalSubjects }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Across all classes</div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-1">Relevant Subjects</div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $subjectCount }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">For these levels</div>
        </div>
    </div>

    <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
        <div class="text-xs text-gray-600 dark:text-gray-400">
            <strong>Selected Classes:</strong>
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach($classes as $class)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $class->full_name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</div>
