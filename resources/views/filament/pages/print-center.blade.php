{{-- File: resources/views/filament/pages/print-center.blade.php --}}
<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        <x-filament::section>
            <x-slot name="heading">
                Print Options
            </x-slot>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-filament::button wire:click="generateOutput" color="success" size="lg">
                        <x-heroicon-o-printer class="w-5 h-5 mr-2" />
                        Generate PDF
                    </x-filament::button>

                    <x-filament::button color="primary" size="lg">
                        <x-heroicon-o-document-arrow-down class="w-5 h-5 mr-2" />
                        Export Excel
                    </x-filament::button>

                    <x-filament::button color="gray" size="lg">
                        <x-heroicon-o-eye class="w-5 h-5 mr-2" />
                        Preview
                    </x-filament::button>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">
                        Print Tips:
                    </h4>
                    <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                        <li>Use landscape orientation for better readability</li>
                        <li>Recommended paper size: A4</li>
                        <li>PDF format is recommended for distribution</li>
                        <li>Excel format is best for further editing</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
