<x-filament-panels::page>
    @php
        function formatSettingKey($key) {
            return str_replace('_', ' ', ucwords(str_replace('_', ' ', $key)));
        }
    @endphp
    
    <div class="space-y-6">
        @foreach($this->getResource()::table($this->makeTable())->getHeaderActions() as $action)
            <div class="flex justify-end">
                {{ $action }}
            </div>
        @endforeach

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->getRecords() as $record)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                {{ formatSettingKey($record->key) }}
                            </h3>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium 
                                @if($record->type === 'string') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                @elseif($record->type === 'integer') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300
                                @elseif($record->type === 'boolean') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300
                                @elseif($record->type === 'json') bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300
                                @endif">
                                {{ ucfirst($record->type) }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 ml-2">
                                {{ formatSettingKey($record->group) }}
                            </span>
                        </div>
                        <div class="flex gap-2 ml-2">
                            <a href="{{ $this->getResource()::getUrl('edit', ['record' => $record]) }}" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <button wire:click="$dispatch('open-modal', { id: 'delete-{{ $record->id }}' })" type="button" class="inline-flex items-center justify-center w-8 h-8 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Value:</p>
                        <p class="text-base text-gray-900 dark:text-white {{ $record->type === 'json' ? '' : 'font-mono' }} break-all">
                            @if($record->type === 'json')
                                @php
                                    $jsonValue = json_decode($record->value, true);
                                    if (is_array($jsonValue)) {
                                        echo implode(', ', $jsonValue);
                                    } else {
                                        echo $record->value;
                                    }
                                @endphp
                            @else
                                {{ strlen($record->value) > 100 ? substr($record->value, 0, 100) . '...' : $record->value }}
                            @endif
                        </p>
                    </div>
                    
                    <x-filament-actions::modals />
                    
                    @teleport('body')
                        <div x-data="{ open: false }" 
                             x-on:open-modal.window="if ($event.detail.id === 'delete-{{ $record->id }}') open = true"
                             x-show="open" 
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             style="display: none;">
                            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="open" 
                                     x-on:click="open = false"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
                                
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                                
                                <div x-show="open"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                                    <div class="sm:flex sm:items-start">
                                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                                Delete Setting
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    Are you sure you want to delete this setting? This action cannot be undone.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                        <button wire:click="deleteRecord({{ $record->id }})" 
                                                x-on:click="open = false"
                                                type="button" 
                                                class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                            Delete
                                        </button>
                                        <button x-on:click="open = false" 
                                                type="button" 
                                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endteleport
                    
                    @if($record->description)
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $record->description }}
                        </p>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
