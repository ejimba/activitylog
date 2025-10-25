<?php

namespace Rmsramos\Activitylog\Loggers;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

abstract class Logger
{
    /**
     * The model class this logger is for.
     */
    public static ?string $model = null;

    /**
     * The resource class associated with this logger.
     */
    public static ?string $resource = null;

    /**
     * Custom label for the model.
     */
    protected ?string $label = null;

    /**
     * Field definitions for logging.
     */
    protected array $fields = [];

    /**
     * Relation manager definitions.
     */
    protected array $relationManagers = [];

    /**
     * Current relation manager context.
     */
    protected ?string $currentRelationManager = null;

    /**
     * The new model instance.
     */
    protected ?Model $newModel = null;

    /**
     * The old model instance.
     */
    protected ?Model $oldModel = null;

    /**
     * Create a new logger instance.
     */
    public function __construct(?Model $newModel = null, ?Model $oldModel = null)
    {
        $this->newModel = $newModel;
        $this->oldModel = $oldModel;
    }

    /**
     * Make a new logger instance.
     */
    public static function make(?Model $newModel = null, ?Model $oldModel = null): static
    {
        return new static($newModel, $oldModel);
    }

    /**
     * Get the label for this logger.
     */
    public function getLabel(): string|Htmlable
    {
        if ($this->label) {
            return $this->label;
        }

        if (static::$resource) {
            return static::$resource::getModelLabel();
        }

        if (static::$model) {
            return class_basename(static::$model);
        }

        return 'Unknown';
    }

    /**
     * Set the label for this logger.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Define fields for logging.
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get the fields for logging.
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Define relation managers for logging.
     */
    public function relationManagers(array $relationManagers): static
    {
        $this->relationManagers = $relationManagers;

        return $this;
    }

    /**
     * Get the relation managers.
     */
    public function getRelationManagers(): array
    {
        return $this->relationManagers;
    }

    /**
     * Set the current relation manager context.
     */
    public function relationManager(string $name): static
    {
        $this->currentRelationManager = $name;

        return $this;
    }

    /**
     * Get the current relation manager.
     */
    public function getCurrentRelationManager(): ?string
    {
        return $this->currentRelationManager;
    }

    /**
     * Get the field label for a given field name.
     */
    public function getFieldLabel(string $fieldName): string
    {
        $fields = $this->getFields();

        foreach ($fields as $field) {
            if ($field->getName() === $fieldName) {
                return $field->getLabel();
            }
        }

        return $fieldName;
    }

    /**
     * Format a field value for display.
     */
    public function formatFieldValue(string $fieldName, mixed $value): mixed
    {
        $fields = $this->getFields();

        foreach ($fields as $field) {
            if ($field->getName() === $fieldName && $field->hasFormatter()) {
                return $field->format($value);
            }
        }

        return $this->defaultFormat($value);
    }

    /**
     * Default formatting for values.
     */
    protected function defaultFormat(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(config('filament-activitylog.datetime_format'));
        }

        return $value;
    }

    /**
     * Get the resource class.
     */
    public static function getResource(): ?string
    {
        return static::$resource;
    }

    /**
     * Get the model class.
     */
    public static function getModel(): ?string
    {
        return static::$model;
    }

    /**
     * Hook called when an activity is created.
     */
    public function onCreated(Model $model): void
    {
        //
    }

    /**
     * Hook called when an activity is updated.
     */
    public function onUpdated(Model $model, array $changes): void
    {
        //
    }

    /**
     * Hook called when an activity is deleted.
     */
    public function onDeleted(Model $model): void
    {
        //
    }

    /**
     * Hook called when an activity is restored.
     */
    public function onRestored(Model $model): void
    {
        //
    }

    /**
     * Hook called when a relation is attached.
     */
    public function onAttached(Model $model, string $relation, $relatedIds): void
    {
        //
    }

    /**
     * Hook called when a relation is detached.
     */
    public function onDetached(Model $model, string $relation, $relatedIds): void
    {
        //
    }
}
