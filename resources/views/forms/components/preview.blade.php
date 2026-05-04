<div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4 space-y-3">
    @if($humanReadable)
        <div>
            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                {{ __('filament-recurrence::recurrence.fields.recurrence.preview') }}
            </div>
            <div class="text-sm font-medium text-gray-950 dark:text-white">
                {{ $humanReadable }}
            </div>
        </div>

        @if(count($occurrences) > 0 && is_array($calendar ?? null))
            <div x-data="{ calendarOpen: false }">
                <div>
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2 mt-4">
                        {{ __('filament-recurrence::recurrence.fields.recurrence.next_occurrences') }}
                    </div>
                    <div class="space-y-1">
                        @foreach($occurrences as $occurrence)
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <span class="inline-flex h-3 w-3 shrink-0 overflow-hidden items-center justify-center" aria-hidden="true">
                                    <x-filament::icon
                                        :icon="\Filament\Support\Icons\Heroicon::CheckCircle"
                                        :size="\Filament\Support\Enums\IconSize::ExtraSmall"
                                        class="h-3 w-3"
                                    />
                                </span>
                                <span class="min-w-0">{{ $occurrence->format(config('filament-recurrence.date_format') . ' ' . config('filament-recurrence.time_format')) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-3">
                    <button
                        type="button"
                        class="text-xs font-semibold text-primary-600 underline decoration-primary-600/30 underline-offset-2 transition hover:text-primary-700 dark:text-primary-400 dark:decoration-primary-400/30 dark:hover:text-primary-300"
                        x-on:click="calendarOpen = true"
                    >
                        {{ __('filament-recurrence::recurrence.fields.recurrence.preview_on_calendar') }}
                    </button>
                </div>

                <div
                    x-show="calendarOpen"
                    x-cloak
                    x-on:keydown.escape.window="calendarOpen = false"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
                    style="display: none;"
                    role="dialog"
                    aria-modal="true"
                    aria-label="{{ __('filament-recurrence::recurrence.fields.recurrence.preview_on_calendar') }}"
                >
                    <div
                        class="fixed inset-0 bg-gray-950/60 backdrop-blur-[1px] dark:bg-gray-950/80"
                        x-on:click="calendarOpen = false"
                        aria-hidden="true"
                    ></div>

                    <div
                        class="relative z-10 w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                        x-on:click.stop
                    >
                        <div class="flex items-center justify-between border-b border-gray-950/10 px-4 py-3 dark:border-white/10">
                            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ __('filament-recurrence::recurrence.fields.recurrence.preview_on_calendar') }}
                            </h2>
                            <button
                                type="button"
                                class="rounded-lg px-2 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-950 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                                x-on:click="calendarOpen = false"
                            >
                                {{ __('filament-recurrence::recurrence.fields.recurrence.calendar_modal_close') }}
                            </button>
                        </div>

                        <div
                            @class([
                                'grid gap-8 p-4 sm:p-6',
                                'sm:grid-cols-1' => count($calendar['months']) === 1,
                                'sm:grid-cols-2' => count($calendar['months']) === 2,
                                'sm:grid-cols-3' => count($calendar['months']) >= 3,
                            ])
                        >
                            @foreach($calendar['months'] as $month)
                                <div class="min-w-0">
                                    <div class="text-center text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $month['label'] }}
                                    </div>
                                    <div class="mt-3 grid grid-cols-7 gap-y-1 text-center">
                                        @foreach($calendar['weekday_labels'] as $abbr)
                                            <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                {{ $abbr }}
                                            </div>
                                        @endforeach
                                    </div>
                                    @foreach($month['weeks'] as $week)
                                        <div class="mt-1 grid grid-cols-7 gap-y-1">
                                            @foreach($week as $cell)
                                                <div class="flex justify-center py-0.5">
                                                    @if($cell['in_month'])
                                                        @if($cell['is_first_occurrence'])
                                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-medium text-white dark:bg-primary-500">
                                                                {{ $cell['day'] }}
                                                            </span>
                                                        @elseif($cell['is_occurrence'])
                                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-gray-950 dark:bg-primary-500/25 dark:text-white">
                                                                {{ $cell['day'] }}
                                                            </span>
                                                        @else
                                                            <span class="inline-flex h-8 w-8 items-center justify-center text-xs font-medium text-gray-950 dark:text-white">
                                                                {{ $cell['day'] }}
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="inline-flex h-8 w-8 items-center justify-center text-xs text-gray-400 dark:text-gray-500">
                                                            {{ $cell['day'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('filament-recurrence::recurrence.messages.unable_to_preview') }}
        </div>
    @endif
</div>
