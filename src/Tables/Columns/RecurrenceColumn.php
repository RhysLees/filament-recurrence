<?php

namespace Andreia\FilamentRecurrence\Tables\Columns;

use Andreia\FilamentRecurrence\Data\RecurrenceData;
use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class RecurrenceColumn extends Column
{
    protected string $view = 'filament-recurrence::tables.columns.recurrence-column';

    protected bool $showRule = false;
    protected bool $showNextOccurrences = false;
    protected int $nextOccurrencesLimit = 3;

    /**
     * Normalize column state to RecurrenceData. Eloquent casts (RecurrenceCast)
     * return RecurrenceData instances; raw JSON may still be array or string (RRULE).
     */
    protected function resolveToRecurrenceData(mixed $state): ?RecurrenceData
    {
        if ($state === null || $state === '') {
            return null;
        }

        if ($state instanceof RecurrenceData) {
            return $state;
        }

        try {
            if (is_string($state)) {
                return RecurrenceData::fromRule($state);
            }

            if (is_array($state)) {
                return RecurrenceData::fromArray($state);
            }
        } catch (\Exception) {
            return null;
        }

        return null;
    }

    public function showRule(bool $condition = true): static
    {
        $this->showRule = $condition;
        return $this;
    }

    /**
     * When enabled, upcoming dates are shown in the column tooltip (not inline in the cell).
     */
    public function showNextOccurrences(bool $condition = true, int $limit = 3): static
    {
        $this->showNextOccurrences = $condition;
        $this->nextOccurrencesLimit = $limit;
        return $this;
    }

    /**
     * @param  mixed  $state  Per-item state for split columns; defaults to {@see getState()} when omitted.
     */
    public function getTooltip(mixed $state = null): string | Htmlable | null
    {
        $evaluationState = func_num_args() > 0 ? $state : $this->getState();

        $base = $this->evaluate($this->tooltip, [
            'state' => $evaluationState,
        ]);

        if (! $this->showNextOccurrences) {
            return $base;
        }

        $extra = $this->getNextOccurrencesTooltipFragment();

        if ($extra === null) {
            return $base;
        }

        if ($base instanceof Htmlable) {
            return new HtmlString($base->toHtml() . '<br><br>' . $extra->toHtml());
        }

        if (filled($base)) {
            return new HtmlString(e($base) . '<br><br>' . $extra->toHtml());
        }

        return $extra;
    }

    /**
     * HTML fragment for the tooltip so each occurrence can use its own line ({@see HtmlString} + allowHTML).
     */
    protected function getNextOccurrencesTooltipFragment(): ?HtmlString
    {
        $data = $this->resolveToRecurrenceData($this->getState());

        if (! $data) {
            return null;
        }

        try {
            $occurrences = $data->getOccurrences($this->nextOccurrencesLimit);
        } catch (\Exception) {
            return null;
        }

        if ($occurrences === []) {
            return null;
        }

        $dateTimeFormat = config('filament-recurrence.date_format') . ' ' . config('filament-recurrence.time_format');
        $parts = [e(__('filament-recurrence::recurrence.fields.recurrence.next_occurrences')) . '<br>'];

        foreach ($occurrences as $occurrence) {
            $parts[] = e('• ' . $occurrence->format($dateTimeFormat)) . '<br>';
        }

        return new HtmlString(implode('', $parts));
    }

    public function getHumanReadable(): ?string
    {
        $data = $this->resolveToRecurrenceData($this->getState());

        if (! $data) {
            return null;
        }

        try {
            return $data->toHumanReadable();
        } catch (\Exception $e) {
            return 'Invalid recurrence';
        }
    }

    public function getRule(): ?string
    {
        if (! $this->showRule) {
            return null;
        }

        $data = $this->resolveToRecurrenceData($this->getState());

        if (! $data) {
            return null;
        }

        try {
            $rule = $data->toRule();

            return $rule !== '' ? $rule : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getNextOccurrences(): array
    {
        if (! $this->showNextOccurrences) {
            return [];
        }

        $data = $this->resolveToRecurrenceData($this->getState());

        if (! $data) {
            return [];
        }

        try {
            return $data->getOccurrences($this->nextOccurrencesLimit);
        } catch (\Exception $e) {
            return [];
        }
    }
}
