<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
        }}
        style="position: absolute; left: 0; top: 0; line-height: var(--tl-line-h); display: flex; align-items: center; min-height: var(--tl-dot-size); width: 100%;"
    >
        <span style="line-height: var(--tl-line-h); display: inline-block;">{{ $getModifiedState() }}</span>
    </div>
</x-dynamic-component>
