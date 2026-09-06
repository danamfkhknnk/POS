@php
    $record = $getRecord();
    $outlets = $record->outlets;
@endphp

@if ($outlets->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400 py-2">No stock data</div>
@else
    <div class="space-y-1">
        @foreach ($outlets as $outlet)
            <div class="flex items-center gap-6 text-sm py-1">
                <span class="font-medium text-gray-900 dark:text-white min-w-[150px]">
                    {{ $outlet->name }}
                </span>
                <span class="text-gray-600 dark:text-gray-400">
                    Stock: <span class="font-semibold text-gray-900 dark:text-white">{{ $outlet->pivot->quantity }}</span>
                </span>
                <span class="text-gray-600 dark:text-gray-400">
                    Min: <span class="font-semibold text-gray-900 dark:text-white">{{ $outlet->pivot->min_stock }}</span>
                </span>
                @if ($outlet->pivot->is_active)
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-400/10 dark:text-green-400">Active</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-400/10 dark:text-gray-400">Inactive</span>
                @endif
            </div>
        @endforeach
    </div>
@endif
