<?php

namespace Andreia\FilamentRecurrence\Forms\Components;

use Andreia\FilamentRecurrence\Data\RecurrenceData;
use Andreia\FilamentRecurrence\Support\OccurrenceCalendar;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View as ViewComponent;

class RecurrenceField extends Field
{
    protected string $view = 'filament-recurrence::forms.components.recurrence-field';

    protected bool $showStartDate = true;
    protected bool $showEndOptions = true;
    protected bool $showAdvancedOptions = false;
    protected bool $showPreview = true;
    protected int $previewOccurrencesLimit = 5;
    protected bool $useDateTime = false;

    protected bool $showTimezone = true;

    public static function getDefaultName(): ?string
    {
        return 'recurrence';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();

        $this->afterStateHydrated(function (RecurrenceField $component, ?array $state): void {
            if (! is_array($state)) {
                return;
            }

            $merged = RecurrenceData::mergeFormUiState($state);

            if (! $component->showTimezone) {
                $merged['timezone'] = config('filament-recurrence.timezone', 'UTC');
            }

            if ($merged == $state) {
                return;
            }

            $component->state($merged);
        });

        $this->dehydrateStateUsing(function (mixed $state): mixed {
            if (is_array($state)) {
                if (! $this->showTimezone) {
                    unset($state['timezone']);
                }

                $data = RecurrenceData::fromArray($state);

                return $data->toArray();
            }

            return $state;
        });

        $this->schema(fn (): array => $this->buildRecurrenceSchema());
    }

    public function showStartDate(bool $condition = true): static
    {
        $this->showStartDate = $condition;
        return $this;
    }

    public function showEndOptions(bool $condition = true): static
    {
        $this->showEndOptions = $condition;
        return $this;
    }

    public function showAdvancedOptions(bool $condition = true): static
    {
        $this->showAdvancedOptions = $condition;
        return $this;
    }

    public function showPreview(bool $condition = true): static
    {
        $this->showPreview = $condition;

        return $this;
    }

    public function previewOccurrencesLimit(int $limit = 5): static
    {
        $this->previewOccurrencesLimit = max(1, $limit);

        return $this;
    }

    public function useDateTime(bool $condition = true): static
    {
        $this->useDateTime = $condition;
        return $this;
    }

    public function showTimezone(bool $condition = true): static
    {
        $this->showTimezone = $condition;

        return $this;
    }

    protected function buildRecurrenceSchema(): array
    {
        $formComponents = [];

        $fusedRepeat = FusedGroup::make([
            TextInput::make('interval')
                ->hiddenLabel()
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->maxValue(999)
                ->live()
                ->visible(fn (Get $get) => filled($get('frequency'))),
            Select::make('frequency')
                ->hiddenLabel()
                ->options(fn (Get $get): array => self::getFrequencyOptionsForInterval($get))
                ->required()
                ->default('WEEKLY')
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state) {
                    // Reset dependent fields when frequency changes
                    $set('by_day', null);
                    $set('by_month_day', null);
                    $set('by_month', null);
                    $set('by_set_pos', null);
                }),
        ])
            ->label(__('filament-recurrence::recurrence.fields.recurrence.fused_repeats'))
            ->columns(2);

        $timezoneSelect = $this->showTimezone
            ? TimezoneSelect::make('timezone')
                ->label(__('filament-recurrence::recurrence.fields.recurrence.timezone'))
                ->default(config('filament-recurrence.timezone', 'UTC'))
                ->searchable()
                ->required(fn (Get $get) => filled($get('frequency')))
                ->visible(fn (Get $get) => filled($get('frequency')))
                ->columnSpanFull()
            : null;

        if ($this->showStartDate) {
            $startField = $this->useDateTime
                ? DateTimePicker::make('start_date')
                    ->label('Start Date & Time')
                    ->required()
                    ->default(now())
                    ->live()
                : DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->default(now())
                    ->live();

            $topRow = [$startField, $fusedRepeat];
            if ($timezoneSelect !== null) {
                $topRow[] = $timezoneSelect;
            }

            $formComponents[] = Group::make($topRow)
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]);
        } else {
            $topRow = [$fusedRepeat];
            if ($timezoneSelect !== null) {
                $topRow[] = $timezoneSelect;
            }

            $formComponents[] = Group::make($topRow)
                ->columns(1);
        }

        // Day-specific options for weekly frequency
        $formComponents[] = RepeatOnWeekdays::make('by_day')
            ->label(__('filament-recurrence::recurrence.fields.recurrence.repeat_on'))
            ->live()
            ->visible(fn (Get $get) => $get('frequency') === 'WEEKLY');

        // Month day options for monthly frequency
        $formComponents[] = Group::make([
            Radio::make('monthly_type')
                ->label('Repeat by')
                ->options([
                    'day' => 'Day of month',
                    'weekday' => 'Day of week',
                ])
                ->default('day')
                ->live()
                ->inline()
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    if ($state === 'day') {
                        $set('by_set_pos', null);
                        $set('by_day', null);
                    } elseif ($state === 'weekday') {
                        $set('by_month_day', null);
                    }
                }),

            Select::make('by_month_day')
                ->label('Day')
                ->options(array_combine(range(1, 31), range(1, 31)))
                ->multiple()
                ->live()
                ->visible(fn (Get $get) => $get('monthly_type') === 'day'),

            Group::make([
                Select::make('by_set_pos')
                    ->label('Week')
                    ->options([
                        '1' => 'First',
                        '2' => 'Second',
                        '3' => 'Third',
                        '4' => 'Fourth',
                        '-1' => 'Last',
                    ])
                    ->live(),

                CheckboxList::make('by_day')
                    ->options([
                        'MO' => 'Monday',
                        'TU' => 'Tuesday',
                        'WE' => 'Wednesday',
                        'TH' => 'Thursday',
                        'FR' => 'Friday',
                        'SA' => 'Saturday',
                        'SU' => 'Sunday',
                    ])
                    ->columns(4)
                    ->live(),
            ])->visible(fn (Get $get) => $get('monthly_type') === 'weekday'),
        ])
            ->visible(fn(Get $get) => $get('frequency') === 'MONTHLY');

        // Month selection for yearly frequency
        $formComponents[] = CheckboxList::make('by_month')
            ->label('In months')
            ->options([
                '1' => 'January',
                '2' => 'February',
                '3' => 'March',
                '4' => 'April',
                '5' => 'May',
                '6' => 'June',
                '7' => 'July',
                '8' => 'August',
                '9' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December',
            ])
            ->columns(4)
            ->visible(fn(Get $get) => $get('frequency') === 'YEARLY');

        // End options
        if ($this->showEndOptions) {
            $formComponents[] = Fieldset::make('Ends')
                ->schema([
                    Radio::make('end_type')
                        ->hiddenLabel()
                        ->options([
                            'never' => 'Never',
                            'date' => 'On date',
                            'count' => 'After occurrences',
                        ])
                        ->default('never')
                        ->live()
                        ->inline()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if ($state === 'never') {
                                $set('end_date', null);
                                $set('count', null);

                                return;
                            }

                            if ($state === 'date') {
                                $set('count', null);

                                return;
                            }

                            if ($state === 'count') {
                                $set('end_date', null);
                            }
                        }),

                    ($this->useDateTime
                        ? DateTimePicker::make('end_date')
                            ->label('End Date & Time')
                        : DatePicker::make('end_date')
                            ->label(__('filament-recurrence::recurrence.fields.recurrence.end_date')))
                        ->visible(fn (Get $get) => $get('end_type') === 'date')
                        ->afterOrEqual('start_date')
                        ->live(),

                    TextInput::make('count')
                        ->label('Number of occurrences')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(999)
                        ->visible(fn(Get $get) => $get('end_type') === 'count')
                        ->live(),
                ])
                ->visible(fn(Get $get) => filled($get('frequency')));
        }

        if (! $this->showPreview) {
            return [
                Group::make($formComponents)
                    ->columns(1)
                    ->columnSpanFull(),
            ];
        }

        $previewLimit = $this->previewOccurrencesLimit;

        $preview = ViewComponent::make('filament-recurrence::forms.components.preview')
            ->live()
            ->viewData(function (Get $get) use ($previewLimit) {
                $state = self::getRecurrenceStateForPreview($get);
                if (! is_array($state)) {
                    return [
                        'humanReadable' => null,
                        'occurrences' => [],
                        'calendar' => null,
                    ];
                }

                try {
                    $data = RecurrenceData::fromArray($state);

                    $calendarLimit = max(
                        $previewLimit,
                        (int) config('filament-recurrence.calendar_preview_occurrences', 32)
                    );

                    $occurrences = $data->getOccurrences($previewLimit);

                    return [
                        'humanReadable' => $data->toHumanReadable(),
                        'occurrences' => $occurrences,
                        'calendar' => OccurrenceCalendar::buildTwoMonths(
                            $data->getOccurrences($calendarLimit),
                            $data->startDate,
                        ),
                    ];
                } catch (\Exception) {
                    return [
                        'humanReadable' => null,
                        'occurrences' => [],
                        'calendar' => null,
                    ];
                }
            })
            ->columnSpan([
                'default' => 1,
                'lg' => 1,
            ])
            ->visible(fn (Get $get) => filled($get('frequency')));

        return [
            Group::make([
                Group::make($formComponents)
                    ->columns(1)
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 4,
                    ]),
                $preview,
            ])
                ->columns([
                    'default' => 1,
                    'lg' => 5,
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function getFrequencyOptionsForInterval(Get $get): array
    {
        $interval = max(1, (int) ($get('interval') ?? 1));

        $configured = config('filament-recurrence.frequencies', []);
        $units = __('filament-recurrence::recurrence.frequency_units');

        $unitKeys = [
            'DAILY' => 'daily',
            'WEEKLY' => 'weekly',
            'MONTHLY' => 'monthly',
            'YEARLY' => 'yearly',
        ];

        $options = [];
        foreach ($configured as $key => $fallbackLabel) {
            $unitKey = $unitKeys[$key] ?? null;
            if ($unitKey && is_array($units) && isset($units[$unitKey])) {
                $options[$key] = trans_choice($units[$unitKey], $interval);
            } else {
                $options[$key] = is_string($fallbackLabel) ? $fallbackLabel : (string) $key;
            }
        }

        return $options;
    }

    /**
     * Resolve the Recurrence field's full state for the preview. Relative paths are
     * sensitive to how many Group / FusedGroup wrappers are in the schema, so we try
     * the most likely roots until the shape matches.
     */
    protected static function getRecurrenceStateForPreview(Get $get): ?array
    {
        $pathsTried = ['', '..', '../..', '../../..', '../../', '../../../..'];

        foreach ($pathsTried as $path) {
            $state = $get($path);
            if (! is_array($state)) {
                continue;
            }
            if (array_key_exists('frequency', $state) || array_key_exists('start_date', $state)) {
                return $state;
            }
        }

        return null;
    }
}
