<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div style="margin-top: 0.25rem; font-size: 0.875rem; line-height: 1.25rem; color: rgb(75 85 99);">
        <style>
            .dark div[style*="color: rgb(75 85 99)"] {
                color: rgb(156 163 175) !important;
            }
        </style>
        {{ $getModifiedState() ?? (!is_array($getState()) ? $getState() ?? $getPlaceholder() : null) }}
    </div>
</x-dynamic-component>
