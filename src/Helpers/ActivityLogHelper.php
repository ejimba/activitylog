<?php

namespace Rmsramos\Activitylog\Helpers;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLogHelper
{
    /**
     * Resolve the configured user model class, if available.
     */
    public static function getUserModelClass(): ?string
    {
        $model = config('filament-activitylog.user_model')
            ?? config('auth.providers.users.model');

        return ($model && class_exists($model)) ? $model : null;
    }

    /**
     * Resolve the configured activity model class.
     */
    public static function getActivityModelClass(): string
    {
        $model = config('activitylog.activity_model', SpatieActivity::class);

        return (is_string($model) && class_exists($model))
            ? $model
            : SpatieActivity::class;
    }

    /**
     * Create a new instance of the configured activity model.
     */
    public static function makeActivityModel(): Model
    {
        $modelClass = static::getActivityModelClass();

        return app($modelClass);
    }

    /**
     * Get a query builder for the configured activity model.
     */
    public static function activityQuery(): Builder
    {
        $modelClass = static::getActivityModelClass();

        return $modelClass::query();
    }

    /**
     * Check if a class uses a specific trait.
     */
    public static function classUsesTrait(object|string $class, string $trait): bool
    {
        $traits = class_uses_recursive($class);

        return in_array($trait, $traits);
    }

    /**
     * Get the resource plural name from a model class.
     */
    public static function getResourcePluralName(string|Model $model): ?string
    {
        if ($model instanceof Model) {
            $model = get_class($model);
        }

        $resources = Filament::getResources();

        foreach ($resources as $resource) {
            if ($resource::getModel() === $model) {
                return Str::of($resource)
                    ->afterLast('\\')
                    ->beforeLast('Resource')
                    ->kebab()
                    ->plural()
                    ->toString();
            }
        }

        return Str::of($model)
            ->afterLast('\\')
            ->kebab()
            ->plural()
            ->toString();
    }

    /**
     * Get the Filament resource class registered for a model.
     */
    public static function getResourceFromModel(string|Model $model): ?string
    {
        if ($model instanceof Model) {
            $model = get_class($model);
        }

        foreach (Filament::getResources() as $resource) {
            if ($resource::getModel() === $model) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * Attempt to build a Filament resource URL for the provided record.
     */
    public static function getRecordUrl(Model $record): ?string
    {
        $resource = static::getResourceFromModel($record);

        if (! $resource) {
            return null;
        }

        try {
            $panel = Filament::getCurrentOrDefaultPanel();

            if ($resource::hasPage('view')) {
                return $resource::getUrl('view', ['record' => $record->getKey()], panel: $panel->getId());
            }

            if ($resource::hasPage('edit')) {
                return $resource::getUrl('edit', ['record' => $record->getKey()], panel: $panel->getId());
            }
        } catch (\Throwable $throwable) {
            report($throwable);
        }

        return null;
    }

    /**
     * Resolve the subject URL for an activity.
     */
    public static function getSubjectUrl(Model $activity): ?string
    {
        if (! static::isActivityInstance($activity)) {
            return null;
        }

        if (! $activity->subject_type || ! $activity->subject_id) {
            return null;
        }

        try {
            $model = app($activity->subject_type)->find($activity->subject_id);

            return $model ? static::getRecordUrl($model) : null;
        } catch (\Throwable $throwable) {
            report($throwable);

            return null;
        }
    }

    /**
     * Resolve the causer URL for an activity.
     */
    public static function getCauserUrl(Model $activity): ?string
    {
        if (! static::isActivityInstance($activity)) {
            return null;
        }

        if (! $activity->causer_type || ! $activity->causer_id) {
            return null;
        }

        try {
            $model = app($activity->causer_type)->find($activity->causer_id);

            return $model ? static::getRecordUrl($model) : null;
        } catch (\Throwable $throwable) {
            report($throwable);

            return null;
        }
    }

    /**
     * Format a value for display within activity properties.
     */
    public static function formatPropertyValue(mixed $value): string
    {
        if (is_null($value)) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(config('filament-activitylog.datetime_format'));
        }

        if (is_string($value) && strlen($value) > 100) {
            return Str::limit($value, 100);
        }

        return (string) $value;
    }

    /**
     * Get a human readable model name.
     */
    public static function getModelName(string $modelClass): string
    {
        return Str::of($modelClass)
            ->afterLast('\\')
            ->headline()
            ->toString();
    }

    /**
     * Discover project models using the LogsActivity trait.
     */
    public static function getLoggableModels(): array
    {
        $models    = [];
        $modelPath = app_path('Models');

        if (! is_dir($modelPath)) {
            return $models;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modelPath)
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = Str::after($file->getPathname(), app_path() . DIRECTORY_SEPARATOR);
            $className    = 'App\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            if (static::classUsesTrait($className, \Spatie\Activitylog\Traits\LogsActivity::class)) {
                $models[] = $className;
            }
        }

        return $models;
    }

    /**
     * Get the configured color for an event.
     */
    public static function getEventColor(string $event): string
    {
        $colors = config('filament-activitylog.timeline.colors', []);

        if (isset($colors[$event])) {
            return $colors[$event];
        }

        if (isset($colors['default'])) {
            return $colors['default'];
        }

        return match ($event) {
            'created' => 'success',
            'updated' => 'info',
            'deleted', 'force_deleted' => 'danger',
            'restored'   => 'warning',
            'attached'   => 'success',
            'detached'   => 'danger',
            'replicated' => 'info',
            default      => 'gray',
        };
    }

    /**
     * Get the configured icon for an event.
     */
    public static function getEventIcon(string $event): string
    {
        $icons = config('filament-activitylog.timeline.icons', []);

        if (isset($icons[$event])) {
            return $icons[$event];
        }

        if (isset($icons['default'])) {
            return $icons['default'];
        }

        return match ($event) {
            'created' => 'heroicon-o-plus-circle',
            'updated' => 'heroicon-o-pencil-square',
            'deleted', 'force_deleted' => 'heroicon-o-trash',
            'restored'   => 'heroicon-o-arrow-path',
            'attached'   => 'heroicon-o-link',
            'detached'   => 'heroicon-o-link-slash',
            'replicated' => 'heroicon-o-document-duplicate',
            default      => 'heroicon-o-information-circle',
        };
    }

    /**
     * Convert an event name into a human-friendly label.
     */
    public static function getEventLabel(string $event): string
    {
        $labels = config('filament-activitylog.timeline.labels', []);

        if (isset($labels[$event])) {
            $label = $labels[$event];

            if (is_string($label)) {
                $translated = __($label);

                return $translated !== $label ? $translated : $label;
            }

            return (string) $label;
        }

        return Str::of($event)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /**
     * Determine if an activity can be restored.
     */
    public static function canRestoreActivity(Model $activity): bool
    {
        if (! static::isActivityInstance($activity)) {
            return false;
        }

        if (! config('filament-activitylog.restore.enabled')) {
            return false;
        }

        $allowedEvents = config('filament-activitylog.restore.allowed_events', ['updated', 'deleted']);

        if (! in_array($activity->event, $allowedEvents, true)) {
            return false;
        }

        if (! $activity->properties->has('old')) {
            return false;
        }

        if (! $activity->subject) {
            return false;
        }

        return true;
    }

    /**
     * Restore the subject of an activity using the recorded "old" properties.
     */
    public static function restoreActivity(Model $activity): bool
    {
        if (! static::canRestoreActivity($activity)) {
            return false;
        }

        try {
            $oldProperties = $activity->properties->get('old');
            $subject       = $activity->subject;

            if (! $subject || empty($oldProperties)) {
                return false;
            }

            $subject->update($oldProperties);

            activity()
                ->performedOn($subject)
                ->event('restored_from_activity')
                ->withProperties([
                    'restored_from_activity_id' => $activity->id,
                    'restored_properties'       => $oldProperties,
                ])
                ->log('Restored from activity #' . $activity->id);

            return true;
        } catch (\Throwable $throwable) {
            report($throwable);

            return false;
        }
    }

    protected static function isActivityInstance(Model $activity): bool
    {
        $activityClass = static::getActivityModelClass();

        return $activity instanceof $activityClass;
    }
}
