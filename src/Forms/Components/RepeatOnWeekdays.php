<?php

namespace Andreia\FilamentRecurrence\Forms\Components;

use Filament\Forms\Components\ToggleButtons;

class RepeatOnWeekdays extends ToggleButtons
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-recurrence::forms.components.repeat-on-weekdays';

    protected function setUp(): void
    {
        parent::setUp();

        $this->multiple();
        $this->inline();

        $letters = __('filament-recurrence::recurrence.weekday_letters');
        $weekdays = __('filament-recurrence::recurrence.weekdays');

        $keys = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

        $options = [];
        $tooltips = [];
        $colors = [];

        foreach ($keys as $key) {
            $options[$key] = is_array($letters) ? ($letters[$key] ?? $key) : $key;
            $tooltips[$key] = is_array($weekdays) ? ($weekdays[$key] ?? $key) : $key;
            $colors[$key] = 'primary';
        }

        $this->options($options);
        $this->tooltips($tooltips);
        $this->colors($colors);
    }
}
