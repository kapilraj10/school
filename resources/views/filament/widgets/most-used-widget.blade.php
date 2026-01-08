<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Most Used
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            @foreach($links as $link)
                <a 
                    href="{{ $link['url'] }}" 
                    onclick="fetch('{{ route('track-click') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ page_name: '{{ $link['label'] }}', url: '{{ $link['url'] }}' }) })"
                    class="flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-md transition-all"
                >
                    <x-filament::icon 
                        icon="{{ $link['icon'] }}" 
                        class="w-8 h-8 text-primary-500 mb-2"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
                        {{ $link['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

