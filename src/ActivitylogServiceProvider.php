<?php

namespace Rmsramos\Activitylog;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Rmsramos\Activitylog\Extensions\LogActions;
use Rmsramos\Activitylog\Loggers\LoggerRegistry;
use Rmsramos\Activitylog\Services\ActivityExportService;
use Rmsramos\Activitylog\Services\ActivityNotificationService;
use Spatie\Activitylog\ActivitylogServiceProvider as SpatieActivitylogServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ActivitylogServiceProvider extends PackageServiceProvider
{
    public static string $name = 'activitylog';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('activitylog')
            ->hasConfigFile('filament-activitylog')
            ->hasViews('filament-activitylog')
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $installCommand) {
                $installCommand
                    ->publishConfigFile('filament-activitylog')
                    ->askToStarRepoOnGitHub('rmsramos/activitylog')
                    ->startWith(function (InstallCommand $command) {
                        $command->call('vendor:publish', [
                            '--provider' => SpatieActivitylogServiceProvider::class,
                            '--tag'      => 'activitylog-migrations',
                        ]);
                    });
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LoggerRegistry::class);
        $this->app->singleton(ActivityExportService::class);
        $this->app->singleton(ActivityNotificationService::class);
        $this->app->register(SpatieActivitylogServiceProvider::class);
    }

    public function packageBooted(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-activitylog');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-activitylog');

        $assets = [
            Css::make('activitylog-styles', __DIR__ . '/../resources/dist/activitylog.css'),
        ];

        FilamentAsset::register($assets, 'rmsramos/activitylog');

        if (config('filament-activitylog.loggers.auto_discover')) {
            $this->app->make(LoggerRegistry::class)->discover();
        }

        foreach (config('filament-activitylog.loggers.registered', []) as $loggerClass) {
            if (! is_string($loggerClass) || ! class_exists($loggerClass) || ! property_exists($loggerClass, 'model')) {
                continue;
            }

            $model = $loggerClass::$model ?? null;

            if ($model) {
                LoggerRegistry::register($model, $loggerClass);
            }
        }

        if (config('filament-activitylog.auto_log.enabled')) {
            LogActions::register();
        }

        if (config('filament-activitylog.notifications.enabled')) {
            $this->app->make(ActivityNotificationService::class)->register();
        }
    }
}
