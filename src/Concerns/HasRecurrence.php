<?php

namespace Andreia\FilamentRecurrence\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Andreia\FilamentRecurrence\Data\RecurrenceData;

trait HasRecurrence
{
    /**
     * Get the recurrence data object.
     */
    public function getRecurrenceData(): ?RecurrenceData
    {
        $recurrence = $this->getAttribute($this->getRecurrenceAttributeName());

        if ($recurrence instanceof RecurrenceData) {
            return $recurrence;
        }

        if (is_array($recurrence)) {
            return RecurrenceData::fromArray($recurrence);
        }

        if (is_string($recurrence)) {
            return RecurrenceData::fromRule($recurrence);
        }

        return null;
    }

    /**
     * Get the next occurrence after the given date.
     */
    public function getNextOccurrence(?Carbon $after = null): ?Carbon
    {
        $data = $this->getRecurrenceData();

        if (! $data) {
            return null;
        }

        $after ??= now();
        $occurrences = $data->getOccurrences(1, $after->addSecond());

        return $occurrences[0] ?? null;
    }

    /**
     * Get upcoming occurrences.
     */
    public function getUpcomingOccurrences(int $limit = 10, ?Carbon $from = null): array
    {
        $data = $this->getRecurrenceData();

        if (! $data) {
            return [];
        }

        $from ??= now();
        return $data->getOccurrences($limit, $from);
    }

    /**
     * Check if the recurrence pattern has occurrences on a given date.
     */
    public function occursOn(Carbon $date): bool
    {
        $data = $this->getRecurrenceData();

        if (! $data) {
            return false;
        }

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $occurrences = $data->getOccurrences(100, $endOfDay);

        foreach ($occurrences as $occurrence) {
            if ($occurrence->between($startOfDay, $endOfDay)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope query to records that have occurrences on a given date.
     */
    public function scopeOccursOn(Builder $query, Carbon $date): Builder
    {
        return $query->whereNotNull($this->getRecurrenceAttributeName())
            ->get()
            ->filter(fn($record) => $record->occursOn($date));
    }

    /**
     * Scope query to records that have occurrences between two dates.
     */
    public function scopeOccursBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereNotNull($this->getRecurrenceAttributeName())
            ->get()
            ->filter(function ($record) use ($start, $end) {
                $data = $record->getRecurrenceData();
                if (! $data) {
                    return false;
                }

                $occurrences = $data->getOccurrences(1000, $end);
                
                foreach ($occurrences as $occurrence) {
                    if ($occurrence->between($start, $end)) {
                        return true;
                    }
                }

                return false;
            });
    }

    /**
     * Get the name of the recurrence attribute.
     */
    protected function getRecurrenceAttributeName(): string
    {
        return property_exists($this, 'recurrenceAttribute')
            ? $this->recurrenceAttribute
            : 'recurrence';
    }
}
