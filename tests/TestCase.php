<?php

namespace Andreia\FilamentRecurrence\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Andreia\FilamentRecurrence\FilamentRecurrenceServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentRecurrenceServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('filament-recurrence.timezone', 'UTC');
    }
}
