@php
    use Rmsramos\Activitylog\Helpers\ActivityLogHelper;

    $activities = $getRecord();
    $batchActivities = $getBatchActivities();
    $showCauser = $getShowCauser();
    $showSubject = $getShowSubject();
    $showTimestamp = $getShowTimestamp();
    $showProperties = $getShowProperties();
    $collapsible = $getCollapsible();
    $collapsed = $getCollapsed();
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @if ($batchActivities->isEmpty())
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('filament-activitylog::batch.no_activities') }}
        </div>
    @else
        <div class="space-y-3">
            @if ($collapsible)
                <details @if(!$collapsed) open @endif class="group">
                    <summary class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                        <x-filament::icon
                            icon="heroicon-m-chevron-right"
                            class="h-4 w-4 transition-transform group-open:rotate-90"
                        />
                        {{ __('filament-activitylog::batch.activities_count', ['count' => $batchActivities->count()]) }}
                    </summary>

                    <div class="mt-3 space-y-2">
                        @foreach ($batchActivities as $activity)
                            @include('filament-activitylog::filament.infolists.components.partials.batch-activity-item')
                        @endforeach
                    </div>
                </details>
            @else
                <div class="space-y-2">
                    @foreach ($batchActivities as $activity)
                        @include('filament-activitylog::filament.infolists.components.partials.batch-activity-item')
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-dynamic-component>
