<?php

namespace Andreia\FilamentRecurrence\Support;

use Carbon\Carbon;
use Andreia\FilamentRecurrence\Data\RecurrenceData;

class RecurrenceHelper
{
    /**
     * Create a simple daily recurrence.
     */
    public static function daily(
        Carbon $startDate,
        int $interval = 1,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'DAILY',
            interval: $interval,
            startDate: $startDate,
            count: $count,
            endDate: $endDate,
        );
    }

    /**
     * Create a weekday-only recurrence.
     */
    public static function weekdays(
        Carbon $startDate,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'WEEKLY',
            interval: 1,
            startDate: $startDate,
            byDay: ['MO', 'TU', 'WE', 'TH', 'FR'],
            count: $count,
            endDate: $endDate,
        );
    }

    /**
     * Create a weekly recurrence on specific days.
     */
    public static function weekly(
        Carbon $startDate,
        array $days,
        int $interval = 1,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'WEEKLY',
            interval: $interval,
            startDate: $startDate,
            byDay: $days,
            count: $count,
            endDate: $endDate,
        );
    }

    /**
     * Create a monthly recurrence on a specific day of the month.
     */
    public static function monthlyOnDay(
        Carbon $startDate,
        int $day,
        int $interval = 1,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'MONTHLY',
            interval: $interval,
            startDate: $startDate,
            byMonthDay: [$day],
            count: $count,
            endDate: $endDate,
        );
    }

    /**
     * Create a monthly recurrence on a specific weekday position.
     * 
     * @param int $position 1-4 for first through fourth, -1 for last
     * @param string $weekday MO, TU, WE, TH, FR, SA, SU
     */
    public static function monthlyOnWeekday(
        Carbon $startDate,
        int $position,
        string $weekday,
        int $interval = 1,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'MONTHLY',
            interval: $interval,
            startDate: $startDate,
            byDay: [$weekday],
            bySetPos: $position,
            count: $count,
            endDate: $endDate,
        );
    }

    /**
     * Create a yearly recurrence.
     */
    public static function yearly(
        Carbon $startDate,
        ?array $months = null,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'YEARLY',
            interval: 1,
            startDate: $startDate,
            byMonth: $months,
            count: $count,
            endDate: $endDate,
        );
    }

    /**
     * Create a bi-weekly recurrence.
     */
    public static function biWeekly(
        Carbon $startDate,
        array $days,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return static::weekly($startDate, $days, 2, $count, $endDate);
    }

    /**
     * Create a quarterly recurrence.
     */
    public static function quarterly(
        Carbon $startDate,
        ?int $count = null,
        ?Carbon $endDate = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'MONTHLY',
            interval: 3,
            startDate: $startDate,
            count: $count,
            endDate: $endDate,
        );
    }

    /**
     * Create business quarter end dates (March 31, June 30, Sept 30, Dec 31).
     */
    public static function quarterEnds(
        Carbon $startDate,
        ?int $count = null
    ): RecurrenceData {
        return new RecurrenceData(
            frequency: 'YEARLY',
            interval: 1,
            startDate: $startDate,
            byMonth: [3, 6, 9, 12],
            byMonthDay: [31],
            count: $count,
        );
    }

    /**
     * Get all weekday abbreviations.
     */
    public static function allWeekdays(): array
    {
        return ['MO', 'TU', 'WE', 'TH', 'FR'];
    }

    /**
     * Get all weekend day abbreviations.
     */
    public static function allWeekendDays(): array
    {
        return ['SA', 'SU'];
    }

    /**
     * Get all day abbreviations.
     */
    public static function allDays(): array
    {
        return ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
    }

    /**
     * Convert Carbon day of week to RRULE day abbreviation.
     */
    public static function carbonDayToRruleDay(int $carbonDay): string
    {
        return match ($carbonDay) {
            Carbon::MONDAY => 'MO',
            Carbon::TUESDAY => 'TU',
            Carbon::WEDNESDAY => 'WE',
            Carbon::THURSDAY => 'TH',
            Carbon::FRIDAY => 'FR',
            Carbon::SATURDAY => 'SA',
            Carbon::SUNDAY => 'SU',
        };
    }

    /**
     * Convert RRULE day abbreviation to Carbon day of week.
     */
    public static function rruleDayToCarbonDay(string $rruleDay): int
    {
        return match ($rruleDay) {
            'MO' => Carbon::MONDAY,
            'TU' => Carbon::TUESDAY,
            'WE' => Carbon::WEDNESDAY,
            'TH' => Carbon::THURSDAY,
            'FR' => Carbon::FRIDAY,
            'SA' => Carbon::SATURDAY,
            'SU' => Carbon::SUNDAY,
        };
    }

    /**
     * Check if two recurrence patterns overlap in a given period.
     */
    public static function patternsOverlap(
        RecurrenceData $pattern1,
        RecurrenceData $pattern2,
        Carbon $periodStart,
        Carbon $periodEnd
    ): bool {
        $occurrences1 = $pattern1->getOccurrences(1000, $periodEnd);
        $occurrences2 = $pattern2->getOccurrences(1000, $periodEnd);

        foreach ($occurrences1 as $occurrence1) {
            if ($occurrence1->lt($periodStart)) {
                continue;
            }
            
            foreach ($occurrences2 as $occurrence2) {
                if ($occurrence2->lt($periodStart)) {
                    continue;
                }
                
                if ($occurrence1->equalTo($occurrence2)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Merge occurrences from multiple recurrence patterns.
     */
    public static function mergeOccurrences(
        array $patterns,
        int $limit = 100,
        ?Carbon $until = null
    ): array {
        $allOccurrences = [];

        foreach ($patterns as $pattern) {
            if ($pattern instanceof RecurrenceData) {
                $occurrences = $pattern->getOccurrences($limit, $until);
                $allOccurrences = array_merge($allOccurrences, $occurrences);
            }
        }

        // Sort by date
        usort($allOccurrences, fn($a, $b) => $a <=> $b);

        // Remove duplicates
        $unique = [];
        $seen = [];
        
        foreach ($allOccurrences as $occurrence) {
            $key = $occurrence->timestamp;
            if (!isset($seen[$key])) {
                $unique[] = $occurrence;
                $seen[$key] = true;
            }
        }

        return array_slice($unique, 0, $limit);
    }

    /**
     * Calculate the number of occurrences in a date range.
     */
    public static function countOccurrencesInRange(
        RecurrenceData $pattern,
        Carbon $start,
        Carbon $end
    ): int {
        $occurrences = $pattern->getOccurrences(10000, $end);
        $count = 0;

        foreach ($occurrences as $occurrence) {
            if ($occurrence->between($start, $end)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Find the Nth occurrence of a recurrence pattern.
     */
    public static function getNthOccurrence(
        RecurrenceData $pattern,
        int $n
    ): ?Carbon {
        if ($n < 1) {
            return null;
        }

        $occurrences = $pattern->getOccurrences($n);
        return $occurrences[$n - 1] ?? null;
    }

    /**
     * Check if a pattern has a finite number of occurrences.
     */
    public static function isFinite(RecurrenceData $pattern): bool
    {
        return $pattern->count !== null || $pattern->endDate !== null;
    }

    /**
     * Get the last occurrence of a finite recurrence pattern.
     */
    public static function getLastOccurrence(RecurrenceData $pattern): ?Carbon
    {
        if (!static::isFinite($pattern)) {
            return null;
        }

        // Get a large number of occurrences
        $occurrences = $pattern->getOccurrences(10000);
        
        return end($occurrences) ?: null;
    }
}
