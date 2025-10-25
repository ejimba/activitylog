@php
    use Filament\Support\Enums\IconSize;
    use Illuminate\Support\Arr;

    $iconColorMap = [
        'success' => 'rgb(255 255 255)',
        'info' => 'rgb(255 255 255)',
        'warning' => 'rgb(255 255 255)',
        'danger' => 'rgb(255 255 255)',
        'gray' => 'rgb(255 255 255)',
        'primary' => 'rgb(255 255 255)',
    ];

    $bgColorMap = [
        'success' => 'rgb(34 197 94)',
        'info' => 'rgb(59 130 246)',
        'warning' => 'rgb(251 146 60)',
        'danger' => 'rgb(239 68 68)',
        'gray' => 'rgb(156 163 175)',
        'primary' => 'rgb(99 102 241)',
    ];
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $arrayState = Arr::wrap($getState());
        $state = $arrayState[0] ?? null;
    @endphp

    @if ($state && ($icon = $getIcon($state)))
        @php
            $color = $getColor($state) ?? 'gray';
            $iconColor = $iconColorMap[$color] ?? $iconColorMap['gray'];
            $bgColor = $bgColorMap[$color] ?? $bgColorMap['gray'];
            $size = $getSize($state) ?? IconSize::Small;

            $iconSize = match (true) {
                $size === IconSize::ExtraSmall || $size === 'xs' => '0.75rem',
                $size === IconSize::Small || $size === 'sm' => '1.25rem',
                $size === IconSize::Medium || $size === 'md' => '1.5rem',
                $size === IconSize::Large || $size === 'lg' => '1.75rem',
                $size === IconSize::ExtraLarge || $size === 'xl' => '2rem',
                $size === IconSize::TwoExtraLarge || $size === IconSize::ExtraExtraLarge || $size === '2xl' => '2.25rem',
                default => '1.25rem',
            };
        @endphp

        <div
            style="position: absolute; left: calc(var(--tl-pl) * -1 - var(--tl-dot-size) / 2 - 1px); top: calc((var(--tl-line-h) - var(--tl-dot-size)) / 2); display: flex; align-items: center; justify-content: center; width: var(--tl-dot-size); height: var(--tl-dot-size); background-color: {{ $bgColor }}; border-radius: 9999px;"
            {{
                $attributes
                    ->merge($getExtraAttributes(), escape: false)
            }}
        >
            <x-filament::icon
                :icon="$icon"
                style="width: calc(var(--tl-dot-size) - 0.375rem); height: calc(var(--tl-dot-size) - 0.375rem); color: {{ $iconColor }};"
            />
        </div>
    @elseif (($placeholder = $getPlaceholder()) !== null)
        <x-filament-infolists::entry-wrapper>
            {{ $placeholder }}
        </x-filament-infolists::entry-wrapper>
    @endif
</x-dynamic-component>
