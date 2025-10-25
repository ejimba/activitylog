@php
    $isContained = $isContained();
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div
        {{
            $attributes
                ->merge([
                    'id' => $getId(),
                ], escape: false)
                ->merge($getExtraAttributes(), escape: false)
                ->class([
                    'fi-in-repeatable',
                    'fi-contained' => $isContained,
                ])
        }}
    >
        @if (count($childComponentContainers = $getChildComponentContainers()))
            <ol style="position: relative; --tl-pl: 2.75rem; --tl-dot-size: 1.25rem; --tl-line-h: 1.5rem; border-left: 2px solid rgb(229 231 235); padding-left: var(--tl-pl); margin-left: 1.25rem; list-style: none;">
                <div style="display: flex; flex-direction: column; gap: 0;">
                    @foreach ($childComponentContainers as $container)
                        <li style="position: relative; display: block; margin: 0.125rem 0; min-height: var(--tl-dot-size); line-height: var(--tl-line-h);">
                            {{ $container }}
                        </li>
                    @endforeach
                </div>
            </ol>
        @elseif (($placeholder = $getPlaceholder()) !== null)
            <x-filament-infolists::entry-wrapper>
                {{ $placeholder }}
            </x-filament-infolists::entry-wrapper>
        @endif
    </div>
</x-dynamic-component>
