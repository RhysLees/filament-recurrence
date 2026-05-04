<?php

namespace Andreia\FilamentRecurrence\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Andreia\FilamentRecurrence\Data\RecurrenceData;

class RecurrenceCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?RecurrenceData
    {
        if ($value === null) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($data)) {
            return null;
        }

        return RecurrenceData::fromArray($data);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof RecurrenceData) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            $data = RecurrenceData::fromArray($value);
            return json_encode($data->toArray());
        }

        if (is_string($value)) {
            // Assume it's an RRULE string
            $data = RecurrenceData::fromRule($value);
            return json_encode($data->toArray());
        }

        return null;
    }
}
