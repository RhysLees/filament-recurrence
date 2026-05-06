<?php

namespace Andreia\FilamentRecurrence\Tests;

use Andreia\FilamentRecurrence\Support\OccurrenceCalendar;
use Carbon\Carbon;

test('builds configured number of month grids with occurrence markers', function () {
    config(['filament-recurrence.calendar_preview_month_count' => 2]);

    $occurrences = [
        Carbon::parse('2024-10-07'),
        Carbon::parse('2024-10-21'),
        Carbon::parse('2024-11-04'),
    ];

    $calendar = OccurrenceCalendar::buildTwoMonths($occurrences);

    expect($calendar['months'])->toHaveCount(2)
        ->and($calendar['weekday_labels'])->toHaveCount(7);

    $foundPrimary = false;
    $foundSecondary = false;

    foreach ($calendar['months'] as $month) {
        expect($month['weeks'])->not->toBeEmpty();

        foreach ($month['weeks'] as $week) {
            foreach ($week as $cell) {
                if ($cell['date_key'] === '2024-10-07') {
                    expect($cell['is_occurrence'])->toBeTrue()
                        ->and($cell['is_first_occurrence'])->toBeTrue();
                    $foundPrimary = true;
                }
                if ($cell['date_key'] === '2024-10-21') {
                    expect($cell['is_occurrence'])->toBeTrue()
                        ->and($cell['is_first_occurrence'])->toBeFalse();
                    $foundSecondary = true;
                }
            }
        }
    }

    expect($foundPrimary)->toBeTrue()
        ->and($foundSecondary)->toBeTrue();
});

test('uses fallback anchor when occurrences are empty', function () {
    config(['filament-recurrence.calendar_preview_month_count' => 2]);

    $calendar = OccurrenceCalendar::buildTwoMonths([], Carbon::parse('2025-03-15'));

    expect($calendar['months'])->toHaveCount(2)
        ->and($calendar['months'][0]['label'])->toContain('2025')
        ->and($calendar['months'][1]['label'])->toContain('2025');
});

test('preview occurrence limit spans through end of last displayed month', function () {
    config(['filament-recurrence.calendar_preview_month_count' => 2]);

    expect(OccurrenceCalendar::previewOccurrenceLimitForMonthGrids(Carbon::parse('2026-05-06')))
        ->toBe(56);
});

test('preview occurrence limit scales with calendar_preview_month_count', function () {
    config(['filament-recurrence.calendar_preview_month_count' => 3]);

    // Through July 31 from May 6: remainder of May + June + July.
    expect(OccurrenceCalendar::previewOccurrenceLimitForMonthGrids(Carbon::parse('2026-05-06')))
        ->toBe(87);
});

test('preview occurrence limit without start date uses upper bound from month count', function () {
    config(['filament-recurrence.calendar_preview_month_count' => 2]);

    expect(OccurrenceCalendar::previewOccurrenceLimitForMonthGrids(null))->toBe(62);
});
