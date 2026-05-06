<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Timezone
    |--------------------------------------------------------------------------
    |
    | The default timezone to use for recurrence calculations if none is
    | specified. This should match your application's timezone.
    |
    */
    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | The default date format to use for displaying recurrence dates.
    |
    */
    'date_format' => 'Y-m-d',

    /*
    |--------------------------------------------------------------------------
    | Time Format
    |--------------------------------------------------------------------------
    |
    | The default time format to use for displaying recurrence times.
    |
    */
    'time_format' => 'H:i',

    /*
    |--------------------------------------------------------------------------
    | Preview sentence date format
    |--------------------------------------------------------------------------
    |
    | PHP date format for start/end dates in the recurrence preview sentence
    | (human-readable description). Uses Carbon; prefer patterns without time.
    |
    */
    'preview_date_format' => 'j F Y',

    /*
    |--------------------------------------------------------------------------
    | Max Occurrences Preview
    |--------------------------------------------------------------------------
    |
    | The maximum number of occurrences to show in previews.
    |
    */
    'max_preview_occurrences' => 10,

    /*
    |--------------------------------------------------------------------------
    | Calendar preview (modal)
    |--------------------------------------------------------------------------
    |
    | Number of month grids in the preview. The package loads enough occurrences
    | to cover from the start date through the end of the last displayed month.
    |
    */
    'calendar_preview_month_count' => 2,

    /*
    |--------------------------------------------------------------------------
    | Available Frequencies
    |--------------------------------------------------------------------------
    |
    | The recurrence frequencies available in the form fields.
    |
    */
    'frequencies' => [
        'DAILY' => 'Daily',
        'WEEKLY' => 'Weekly',
        'MONTHLY' => 'Monthly',
        'YEARLY' => 'Yearly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Week Start Day
    |--------------------------------------------------------------------------
    |
    | The first day of the week (0 = Sunday, 1 = Monday, etc.)
    |
    */
    'week_start_day' => 1, // Monday

    /*
    |--------------------------------------------------------------------------
    | Enable Advanced Options
    |--------------------------------------------------------------------------
    |
    | Whether to show advanced recurrence options by default.
    |
    */
    'enable_advanced_options' => true,
];
