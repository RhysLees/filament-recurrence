<?php

namespace Andreia\FilamentRecurrence\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class OccurrenceCalendar
{
    /**
     * Build two side-by-side month grids for the occurrence calendar preview.
     *
     * @param  array<int, CarbonInterface>  $occurrences
     * @return array{months: list<array{label: string, weeks: list<list<array{day: int, date_key: string, in_month: bool, is_occurrence: bool, is_first_occurrence: bool}>>>}, weekday_labels: list<string>}
     */
    public static function buildTwoMonths(array $occurrences, ?CarbonInterface $fallbackAnchor = null): array
    {
        $anchor = isset($occurrences[0])
            ? Carbon::instance($occurrences[0])->startOfMonth()
            : ($fallbackAnchor
                ? Carbon::instance($fallbackAnchor)->startOfMonth()
                : Carbon::now()->startOfMonth());

        $firstOccurrenceKey = isset($occurrences[0])
            ? Carbon::instance($occurrences[0])->format('Y-m-d')
            : null;

        $occurrenceKeys = [];
        foreach ($occurrences as $occurrence) {
            $occurrenceKeys[Carbon::instance($occurrence)->format('Y-m-d')] = true;
        }

        $months = [];
        $cursor = $anchor->copy();
        $monthCount = max(1, min(3, (int) config('filament-recurrence.calendar_preview_month_count', 2)));

        for ($m = 0; $m < $monthCount; $m++) {
            $months[] = self::buildMonthGrid($cursor->copy(), $occurrenceKeys, $firstOccurrenceKey);
            $cursor->addMonth();
        }

        return [
            'months' => $months,
            'weekday_labels' => self::weekdayLabels(),
        ];
    }

    /**
     * @param  array<string, bool>  $occurrenceKeys
     */
    protected static function buildMonthGrid(Carbon $month, array $occurrenceKeys, ?string $firstOccurrenceKey): array
    {
        $weekStartsAt = self::weekStartsAtConstant();

        $firstOfMonth = $month->copy()->startOfMonth();
        $lastOfMonth = $month->copy()->endOfMonth();

        $gridStart = $firstOfMonth->copy()->startOfWeek($weekStartsAt);
        $gridEnd = $lastOfMonth->copy()->endOfWeek($weekStartsAt);

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->format('Y-m-d');
                $isOccurrence = isset($occurrenceKeys[$key]);
                $week[] = [
                    'day' => $cursor->day,
                    'date_key' => $key,
                    'in_month' => $cursor->month === $firstOfMonth->month && $cursor->year === $firstOfMonth->year,
                    'is_occurrence' => $isOccurrence,
                    'is_first_occurrence' => $isOccurrence && $firstOccurrenceKey !== null && $key === $firstOccurrenceKey,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return [
            'label' => $firstOfMonth->translatedFormat('F Y'),
            'weeks' => $weeks,
        ];
    }

    /**
     * @return list<string>
     */
    protected static function weekdayLabels(): array
    {
        $weekStartsAt = self::weekStartsAtConstant();
        $labels = [];
        $ref = Carbon::now()->startOfWeek($weekStartsAt);

        for ($i = 0; $i < 7; $i++) {
            $short = $ref->copy()->addDays($i)->shortLocaleDayOfWeek;
            $labels[] = mb_substr($short, 0, 2);
        }

        return $labels;
    }

    protected static function weekStartsAtConstant(): int
    {
        $d = (int) config('filament-recurrence.week_start_day', Carbon::MONDAY);

        return max(Carbon::SUNDAY, min(Carbon::SATURDAY, $d));
    }
}
