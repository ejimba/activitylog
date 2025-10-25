@php
    use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
@endphp

<div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
    {{-- Event Icon --}}
    <div class="flex-shrink-0">
        <div class="flex h-8 w-8 items-center justify-center rounded-full {{ ActivityLogHelper::getEventColor($activity->event) === 'success' ? 'bg-success-100 dark:bg-success-500/10' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'danger' ? 'bg-danger-100 dark:bg-danger-500/10' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'info' ? 'bg-info-100 dark:bg-info-500/10' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'warning' ? 'bg-warning-100 dark:bg-warning-500/10' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'gray' ? 'bg-gray-100 dark:bg-gray-500/10' : '' }}">
            <x-filament::icon
                :icon="ActivityLogHelper::getEventIcon($activity->event)"
                class="h-4 w-4 {{ ActivityLogHelper::getEventColor($activity->event) === 'success' ? 'text-success-600 dark:text-success-400' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'danger' ? 'text-danger-600 dark:text-danger-400' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'info' ? 'text-info-600 dark:text-info-400' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'warning' ? 'text-warning-600 dark:text-warning-400' : '' }} {{ ActivityLogHelper::getEventColor($activity->event) === 'gray' ? 'text-gray-600 dark:text-gray-400' : '' }}"
            />
        </div>
    </div>

    {{-- Activity Details --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1">
                {{-- Event & Subject --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <x-filament::badge :color="ActivityLogHelper::getEventColor($activity->event)" size="sm">
                        {{ ActivityLogHelper::getEventLabel($activity->event) }}
                    </x-filament::badge>

                    @if ($showSubject && $activity->subject_type)
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ class_basename($activity->subject_type) }}
                            @if ($activity->subject_id)
                                <span class="text-gray-500 dark:text-gray-500">#{{ $activity->subject_id }}</span>
                            @endif
                        </span>
                    @endif
                </div>

                {{-- Description --}}
                @if ($activity->description)
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {{ $activity->description }}
                    </p>
                @endif

                {{-- Causer --}}
                @if ($showCauser && $activity->causer)
                    <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-user" class="h-3 w-3" />
                        <span>{{ $activity->causer->name ?? __('activitylog::infolists.components.unknown') }}</span>
                    </div>
                @endif

                {{-- Properties --}}
                @if ($showProperties && !empty($activity->properties))
                    <div class="mt-2 space-y-1">
                        @if (!empty($activity->properties['attributes'] ?? []))
                            <div class="text-xs">
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('filament-activitylog::batch.changes') }}:
                                </span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ count($activity->properties['attributes']) }} {{ __('filament-activitylog::batch.fields') }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Timestamp --}}
            @if ($showTimestamp && $activity->created_at)
                <div class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">
                    <time datetime="{{ $activity->created_at->toIso8601String() }}" title="{{ $activity->created_at->format('M d, Y H:i:s') }}">
                        {{ $activity->created_at->format('H:i:s') }}
                    </time>
                </div>
            @endif
        </div>
    </div>
</div>
