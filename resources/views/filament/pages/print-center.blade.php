{{-- resources/views/filament/pages/print-center.blade.php --}}
<x-filament-panels::page>
    <div class="space-y-6"
         x-data="{
             printType: $wire.entangle('data.print_type'),
             get isPdfOnly() {
                 return this.printType === 'all_classes' || this.printType === 'master';
             }
         }">

        {{-- Form --}}
        <x-filament::section>
            <x-slot name="heading">Print Settings</x-slot>

            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </x-filament::section>

        {{-- Action Buttons --}}
        <x-filament::section>
            <x-slot name="heading">Generate Output</x-slot>

            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">

                    {{-- PDF - always available --}}
                    <x-filament::button
                        wire:click="downloadPdf"
                        wire:loading.attr="disabled"
                        wire:target="downloadPdf"
                        color="success"
                        size="lg"
                        icon="heroicon-o-document-arrow-down"
                    >
                        <span wire:loading.remove wire:target="downloadPdf">Generate PDF</span>
                        <span wire:loading wire:target="downloadPdf">Generating…</span>
                    </x-filament::button>

                    {{-- Excel - class / teacher / room --}}
                    <div x-show="!isPdfOnly">
                        <x-filament::button
                            wire:click="downloadExcel"
                            wire:loading.attr="disabled"
                            wire:target="downloadExcel"
                            color="primary"
                            size="lg"
                            icon="heroicon-o-table-cells"
                        >
                            <span wire:loading.remove wire:target="downloadExcel">Export Excel</span>
                            <span wire:loading wire:target="downloadExcel">Exporting…</span>
                        </x-filament::button>
                    </div>

                    {{-- Print Preview - class / teacher / room --}}
                    <div x-show="!isPdfOnly">
                        <x-filament::button
                            wire:click="previewOutput"
                            wire:loading.attr="disabled"
                            wire:target="previewOutput"
                            color="gray"
                            size="lg"
                            icon="heroicon-o-eye"
                        >
                            <span wire:loading.remove wire:target="previewOutput">Print Preview</span>
                            <span wire:loading wire:target="previewOutput">Opening…</span>
                        </x-filament::button>
                    </div>
                </div>

                {{-- Note for bulk / master --}}
                <p
                    x-show="isPdfOnly"
                    class="text-sm text-amber-600 dark:text-amber-400 flex items-center gap-1"
                >
                    <x-heroicon-o-information-circle class="w-4 h-4 shrink-0" />
                    Bulk and Master timetable output only supports PDF format.
                </p>
            </div>
        </x-filament::section>

        {{-- Tips --}}
        <x-filament::section>
            <x-slot name="heading">Print Tips</x-slot>

            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li>Use <strong>landscape orientation</strong> for better readability.</li>
                <li>Recommended paper size: <strong>A4</strong>.</li>
                <li>PDF is recommended for sharing and distribution.</li>
                <li>Excel is best for further editing of class, teacher, or room timetable data.</li>
                <li>Print Preview opens class/teacher/room schedules in a browser tab — use the browser's print dialog to print.</li>
            </ul>
        </x-filament::section>

    </div>
</x-filament-panels::page>
