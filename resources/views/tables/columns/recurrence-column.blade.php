@php
    $humanReadable = $getHumanReadable();
    $rule = $getRule();
    $tooltip = $column->getTooltip($column->getState());
@endphp

<div
    @class(['px-4 py-3'])
    @if (filled($tooltip))
        x-tooltip="{
            content: {{ \Illuminate\Support\Js::from($tooltip) }},
            theme: $store.theme,
            allowHTML: {{ \Illuminate\Support\Js::from($tooltip instanceof \Illuminate\Contracts\Support\Htmlable) }},
        }"
    @endif
>
    @if ($humanReadable)
        <div class="text-sm font-medium text-gray-950 dark:text-white">
            {{ $humanReadable }}
        </div>

        @if ($rule)
            <div class="mt-1 text-xs font-mono text-gray-500 dark:text-gray-400">
                {{ $rule }}
            </div>
        @endif
    @else
        <div class="text-sm text-gray-500 dark:text-gray-400">
            No recurrence
        </div>
    @endif
</div>
