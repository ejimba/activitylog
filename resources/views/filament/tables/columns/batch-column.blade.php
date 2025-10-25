@php
    $batchUuid = $getState();
    $batchCount = $getBatchCount();
    $showCount = $getShowCount();
    $showIcon = $getShowIcon();
    $clickable = $getClickable();
@endphp

<div class="fi-ta-text-item inline-flex items-center gap-1.5">
    @if ($batchUuid)
        @if ($showIcon)
            <x-filament::icon
                icon="heroicon-m-queue-list"
                class="h-4 w-4 text-gray-400 dark:text-gray-500"
            />
        @endif

        <span class="font-mono text-xs text-gray-600 dark:text-gray-400">
            {{ substr($batchUuid, 0, 8) }}
        </span>

        @if ($showCount && $batchCount)
            <x-filament::badge size="sm" color="gray">
                {{ $batchCount }}
            </x-filament::badge>
        @endif
    @else
        <span class="text-gray-400 dark:text-gray-600">-</span>
    @endif
</div>
