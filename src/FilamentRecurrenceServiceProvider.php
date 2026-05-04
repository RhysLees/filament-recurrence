<?php

namespace Andreia\FilamentRecurrence;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentRecurrenceServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-recurrence';

    public static string $viewNamespace = 'filament-recurrence';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make(
                'filament-recurrence-repeat-on-weekdays',
                __DIR__ . '/../resources/css/repeat-on-weekdays.css',
            ),
        ], 'andreia/filament-recurrence');
    }
}
