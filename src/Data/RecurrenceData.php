<?php

namespace Andreia\FilamentRecurrence\Data;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;

class RecurrenceData implements Arrayable
{
    public function __construct(
        public ?string $frequency = null,
        public ?int $interval = 1,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
        public ?int $count = null,
        public ?array $byDay = null,
        public ?array $byMonthDay = null,
        public ?array $byMonth = null,
        public ?int $bySetPos = null,
        public ?string $timezone = null,
    ) {
        $this->timezone ??= config('filament-recurrence.timezone', 'UTC');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            frequency: $data['frequency'] ?? null,
            interval: self::normalizeNullableInt($data['interval'] ?? null) ?? 1,
            startDate: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            endDate: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            count: self::normalizeNullableInt($data['count'] ?? null),
            byDay: $data['by_day'] ?? null,
            byMonthDay: $data['by_month_day'] ?? null,
            byMonth: $data['by_month'] ?? null,
            bySetPos: self::normalizeNullableInt($data['by_set_pos'] ?? null),
            timezone: self::normalizeNullableTimezone($data['timezone'] ?? null),
        );
    }

    /**
     * Filament Select clears often send '' instead of null; coerce for typed properties.
     */
    private static function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private static function normalizeNullableTimezone(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Merge UI-only schema keys (end_type, monthly_type) from persisted recurrence fields.
     * These are not part of the RRULE but are required for radios / visibility on edit.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function mergeFormUiState(array $state): array
    {
        if (! filled($state['end_type'] ?? null)) {
            $state['end_type'] = self::inferEndTypeFromState($state);
        }

        if (($state['frequency'] ?? null) === 'MONTHLY' && ! filled($state['monthly_type'] ?? null)) {
            $state['monthly_type'] = self::inferMonthlyTypeFromState($state);
        }

        if (! filled($state['timezone'] ?? null)) {
            $state['timezone'] = config('filament-recurrence.timezone', 'UTC');
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function inferEndTypeFromState(array $state): string
    {
        $count = $state['count'] ?? null;
        if ($count !== null && $count !== '' && (int) $count > 0) {
            return 'count';
        }

        if (filled($state['end_date'] ?? null)) {
            return 'date';
        }

        return 'never';
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function inferMonthlyTypeFromState(array $state): string
    {
        $bySetPos = $state['by_set_pos'] ?? null;
        $byDay = $state['by_day'] ?? null;

        $useNthWeekday = filled($bySetPos)
            && is_array($byDay)
            && ! empty($byDay);

        return $useNthWeekday ? 'weekday' : 'day';
    }

    public static function fromRule(string $rule): self
    {
        try {
            $rrule = \Recurr\Rule::createFromString($rule);

            return new self(
                frequency: $rrule->getFreqAsText(),
                interval: $rrule->getInterval(),
                startDate: self::carbonOrNull($rrule->getStartDate()),
                endDate: self::carbonOrNull($rrule->getUntil()),
                count: $rrule->getCount(),
                byDay: $rrule->getByDay(),
                byMonthDay: $rrule->getByMonthDay(),
                byMonth: $rrule->getByMonth(),
                bySetPos: self::firstIntFromBySetPosition($rrule->getBySetPosition()),
                timezone: $rrule->getTimezone() ?: config('filament-recurrence.timezone', 'UTC'),
            );
        } catch (\Throwable) {
            return new self();
        }
    }

    private static function carbonOrNull(?DateTimeInterface $date): ?Carbon
    {
        if ($date === null) {
            return null;
        }

        return Carbon::instance($date);
    }

    /**
     * @param  array<int, string>|null  $positions
     */
    private static function firstIntFromBySetPosition(?array $positions): ?int
    {
        if ($positions === null || $positions === []) {
            return null;
        }

        $first = reset($positions);
        if ($first === false || $first === '') {
            return null;
        }

        return is_numeric($first) ? (int) $first : null;
    }

    public function toRule(): string
    {
        if (! $this->frequency) {
            return '';
        }

        $parts = [];
        
        // Frequency
        $parts[] = 'FREQ=' . strtoupper($this->frequency);
        
        // Interval
        if ($this->interval && $this->interval > 1) {
            $parts[] = 'INTERVAL=' . $this->interval;
        }
        
        // End condition
        if ($this->count) {
            $parts[] = 'COUNT=' . $this->count;
        } elseif ($this->endDate) {
            $parts[] = 'UNTIL=' . $this->endDate->format('Ymd\THis\Z');
        }
        
        $freq = strtoupper((string) $this->frequency);

        // MONTHLY: never combine BYMONTHDAY with BYDAY+BYSETPOS (stale UI state causes invalid rules and Recurr can hang).
        $useMonthlyNthWeekday = $freq === 'MONTHLY'
            && filled($this->bySetPos)
            && $this->byDay
            && ! empty($this->byDay);

        $useMonthlyMonthDays = $freq === 'MONTHLY'
            && $this->byMonthDay
            && ! empty($this->byMonthDay)
            && ! $useMonthlyNthWeekday;

        // By Day
        if ($this->byDay && ! empty($this->byDay)) {
            if ($freq !== 'MONTHLY' || $useMonthlyNthWeekday) {
                $parts[] = 'BYDAY=' . implode(',', $this->byDay);
            }
        }

        // By Month Day
        if ($this->byMonthDay && ! empty($this->byMonthDay)) {
            if ($freq !== 'MONTHLY' || $useMonthlyMonthDays) {
                $parts[] = 'BYMONTHDAY=' . implode(',', $this->byMonthDay);
            }
        }

        // By Month
        if ($this->byMonth && ! empty($this->byMonth)) {
            $parts[] = 'BYMONTH=' . implode(',', $this->byMonth);
        }

        // By Set Position
        if (filled($this->bySetPos)) {
            if ($freq !== 'MONTHLY' || $useMonthlyNthWeekday) {
                $parts[] = 'BYSETPOS=' . $this->bySetPos;
            }
        }
        
        return implode(';', $parts);
    }

    public function toArray(): array
    {
        return self::mergeFormUiState([
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'start_date' => $this->startDate?->toDateTimeString(),
            'end_date' => $this->endDate?->toDateTimeString(),
            'count' => $this->count,
            'by_day' => $this->byDay,
            'by_month_day' => $this->byMonthDay,
            'by_month' => $this->byMonth,
            'by_set_pos' => $this->bySetPos,
            'timezone' => $this->timezone,
            'rule' => $this->toRule(),
        ]);
    }

    public function toHumanReadable(): string
    {
        if (! $this->frequency) {
            return __('filament-recurrence::recurrence.messages.no_recurrence');
        }

        try {
            $rule = new \Recurr\Rule($this->toRule(), $this->startDate?->toDateTime());
            $transformer = new \Recurr\Transformer\TextTransformer();
            $pattern = $transformer->transform($rule);
        } catch (\Exception $e) {
            return __('filament-recurrence::recurrence.messages.invalid_recurrence');
        }

        $sentence = __('filament-recurrence::recurrence.preview.repeats', ['pattern' => $pattern]);

        $extras = [];

        if ($this->startDate && $this->endDate) {
            $extras[] = __('filament-recurrence::recurrence.preview.date_range', [
                'start' => $this->formatDateForPreview($this->startDate),
                'end' => $this->formatDateForPreview($this->endDate),
            ]);
        } elseif ($this->startDate) {
            $extras[] = __('filament-recurrence::recurrence.preview.starting_only', [
                'date' => $this->formatDateForPreview($this->startDate),
            ]);
        } elseif ($this->endDate) {
            $extras[] = __('filament-recurrence::recurrence.preview.until_only', [
                'date' => $this->formatDateForPreview($this->endDate),
            ]);
        }

        if ($this->count) {
            $extras[] = __('filament-recurrence::recurrence.preview.for_occurrences', [
                'count' => $this->count,
            ]);
        }

        if ($this->startDate && $this->startHasVisibleTime()) {
            $extras[] = __('filament-recurrence::recurrence.preview.at_time', [
                'time' => $this->formatTimeForPreview($this->startDate),
            ]);
        }

        if ($extras === []) {
            return $sentence;
        }

        return $sentence.', '.implode(', ', $extras);
    }

    protected function formatDateForPreview(Carbon $date): string
    {
        $format = config('filament-recurrence.preview_date_format', 'j F Y');

        return $date->copy()->timezone((string) $this->timezone)->translatedFormat($format);
    }

    protected function formatTimeForPreview(Carbon $date): string
    {
        return $date->copy()->timezone((string) $this->timezone)->format(
            config('filament-recurrence.time_format', 'H:i')
        );
    }

    protected function startHasVisibleTime(): bool
    {
        if (! $this->startDate) {
            return false;
        }

        $local = $this->startDate->copy()->timezone((string) $this->timezone);

        return $local->format('H:i:s') !== '00:00:00';
    }

    public function getOccurrences(?int $limit = null, ?\DateTimeInterface $until = null): array
    {
        if (! $this->frequency || ! $this->startDate) {
            return [];
        }

        $limit ??= config('filament-recurrence.max_preview_occurrences', 10);
        $limit = max(1, $limit);

        try {
            $rule = new \Recurr\Rule($this->toRule(), $this->startDate->toDateTime());

            // ArrayTransformer::transform()'s third argument is $countConstraintFailures (bool), not a max count.
            // Infinite rules use ArrayTransformerConfig::virtualLimit (default 732). Cap generation and slice.
            $ruleCount = $rule->getCount();
            $virtualLimit = max($limit, $ruleCount ?? 0);

            $config = new \Recurr\Transformer\ArrayTransformerConfig();
            $config->setVirtualLimit($virtualLimit);

            $transformer = new \Recurr\Transformer\ArrayTransformer($config);

            $constraint = new \Recurr\Transformer\Constraint\BetweenConstraint(
                $this->startDate->toDateTime(),
                $until ?? ($this->endDate?->toDateTime() ?: new \DateTime('+1 year')),
                true
            );

            $occurrences = $transformer->transform($rule, $constraint, true);

            $dates = array_map(
                fn ($occurrence) => Carbon::instance($occurrence->getStart()),
                $occurrences->toArray()
            );

            return array_slice($dates, 0, $limit);
        } catch (\Exception $e) {
            return [];
        }
    }
}
