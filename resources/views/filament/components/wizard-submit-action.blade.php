<div
    x-data="{
        estimate: {{ (int) ($estimatedGenerationSeconds ?? 0) }},
        startAt: null,
        now: Date.now(),
        timer: null,
        begin() {
            if (!this.startAt) {
                this.startAt = Date.now();
            }
            if (!this.timer) {
                this.timer = setInterval(() => { this.now = Date.now(); }, 1000);
            }
        },
        elapsedSeconds() {
            if (!this.startAt) return 0;
            return Math.max(0, Math.floor((this.now - this.startAt) / 1000));
        },
        remainingSeconds() {
            if (!this.estimate) return 0;
            return Math.max(0, this.estimate - this.elapsedSeconds());
        },
        progressPercent() {
            if (!this.estimate) return 10;
            const pct = Math.floor((this.elapsedSeconds() / this.estimate) * 100);
            return Math.max(5, Math.min(95, pct));
        },
        format(seconds) {
            const s = Math.max(0, seconds);
            const m = Math.floor(s / 60);
            const r = s % 60;
            return `${m}:${String(r).padStart(2, '0')}`;
        }
    }"
    class="flex flex-col items-center gap-4 w-full"
>
    <x-filament::button
        type="submit"
        size="lg"
        color="success"
        icon="heroicon-m-sparkles"
        wire:loading.attr="disabled"
        wire:target="generateTimetable"
        x-on:click="begin()"
        class="w-full sm:w-auto"
    >
        <span wire:loading.remove wire:target="generateTimetable">
            Generate Timetable
        </span>
        <span wire:loading.inline-flex wire:target="generateTimetable" class="items-center gap-2">
            <span class="text-sm font-semibold">Generating Timetable</span>
        </span>
    </x-filament::button>

    <div wire:loading wire:target="generateTimetable" class="w-full max-w-lg">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Generating timetable</div>
                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="estimate ? `Est. total ${format(estimate)}` : 'Estimating…'"></div>
            </div>

            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div
                    class="h-full rounded-full bg-linear-to-r from-green-500 to-blue-500 transition-all duration-700"
                    x-bind:style="`width: ${progressPercent()}%`"
                ></div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-300">
                <div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 dark:bg-gray-800/60 px-3 py-2">
                    <span>Elapsed</span>
                    <span class="font-medium tabular-nums" x-text="format(elapsedSeconds())"></span>
                </div>
                <div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 dark:bg-gray-800/60 px-3 py-2">
                    <span>Est. remaining</span>
                    <span class="font-medium tabular-nums" x-text="estimate ? format(remainingSeconds()) : '—'"
                    ></span>
                </div>
            </div>

            <div class="mt-3 text-center text-sm text-gray-500 dark:text-gray-400" x-show="estimate && elapsedSeconds() > estimate">
                Taking longer than usual… still working.
            </div>
        </div>
    </div>
</div>
