<?php

namespace Rmsramos\Activitylog\Loggers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LoggerRegistry
{
    /**
     * Registered loggers.
     *
     * @var array<string, string>
     */
    protected static array $loggers = [];

    /**
     * Discover loggers in the configured directory.
     */
    public function discover(): void
    {
        $directory = config('filament-activitylog.loggers.directory');
        $namespace = config('filament-activitylog.loggers.namespace');

        if (! File::exists($directory)) {
            return;
        }

        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = Str::after($file->getPathname(), $directory . DIRECTORY_SEPARATOR);
            $className    = $namespace . '\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                $relativePath
            );

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Logger::class)) {
                continue;
            }

            if (! property_exists($className, 'model')) {
                continue;
            }

            $model = $className::$model;

            if ($model) {
                static::register($model, $className);
            }
        }
    }

    /**
     * Register a logger for a model.
     */
    public static function register(string $model, string $logger): void
    {
        static::$loggers[$model] = $logger;
    }

    /**
     * Get the logger for a model.
     */
    public static function get(string $model): ?string
    {
        return static::$loggers[$model] ?? null;
    }

    /**
     * Check if a logger exists for a model.
     */
    public static function has(string $model): bool
    {
        return isset(static::$loggers[$model]);
    }

    /**
     * Get all registered loggers.
     */
    public static function all(): array
    {
        return static::$loggers;
    }

    /**
     * Resolve a logger instance for a model.
     */
    public static function resolve(string $model): ?Logger
    {
        $loggerClass = static::get($model);

        if (! $loggerClass) {
            return null;
        }

        return $loggerClass::make();
    }
}
