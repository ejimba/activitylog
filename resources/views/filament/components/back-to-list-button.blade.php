<div class="flex justify-start mt-4">
    @php
        $label = __('filament-activitylog::resource.back_to_list');
        if ($label === 'filament-activitylog::resource.back_to_list') {
            $label = __('Back to Activity Logs');
        }
    @endphp

    <a href="{{ \Rmsramos\Activitylog\Resources\Activitylogs\ActivitylogResource::getUrl('index') }}"
       class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-gray fi-btn-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20">
        <svg class="fi-btn-icon transition duration-75 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        <span class="fi-btn-label">
            {{ $label }}
        </span>
    </a>
</div>
