<?php

namespace Rmsramos\Activitylog\Loggers;

use Closure;

class Field
{
    protected string $name;

    protected ?string $label = null;

    protected ?Closure $formatter = null;

    protected bool $hidden = false;

    protected ?string $type = null;

    /**
     * Create a new field instance.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Make a new field instance.
     */
    public static function make(string $name): static
    {
        return new static($name);
    }

    /**
     * Set the label for the field.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the field name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the field label.
     */
    public function getLabel(): string
    {
        return $this->label ?? str($this->name)->headline()->toString();
    }

    /**
     * Set a custom formatter for the field.
     */
    public function formatter(Closure $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Check if the field has a custom formatter.
     */
    public function hasFormatter(): bool
    {
        return $this->formatter !== null;
    }

    /**
     * Format the field value.
     */
    public function format(mixed $value): mixed
    {
        if ($this->formatter) {
            return ($this->formatter)($value);
        }

        return $value;
    }

    /**
     * Hide the field from activity logs.
     */
    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Check if the field is hidden.
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set the field type.
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the field type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Mark as boolean field.
     */
    public function boolean(): static
    {
        return $this->type('boolean')->formatter(fn ($value) => $value ? 'Yes' : 'No');
    }

    /**
     * Mark as date field.
     */
    public function date(?string $format = null): static
    {
        $format = $format ?? config('filament-activitylog.date_format');

        return $this->type('date')->formatter(function ($value) use ($format) {
            if (! $value) {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format($format);
            }

            return \Carbon\Carbon::parse($value)->format($format);
        });
    }

    /**
     * Mark as datetime field.
     */
    public function datetime(?string $format = null): static
    {
        $format = $format ?? config('filament-activitylog.datetime_format');

        return $this->type('datetime')->formatter(function ($value) use ($format) {
            if (! $value) {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format($format);
            }

            return \Carbon\Carbon::parse($value)->format($format);
        });
    }

    /**
     * Mark as JSON field.
     */
    public function json(): static
    {
        return $this->type('json')->formatter(function ($value) {
            if (is_string($value)) {
                return $value;
            }

            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        });
    }

    /**
     * Mark as array field.
     */
    public function array(): static
    {
        return $this->type('array')->formatter(function ($value) {
            if (is_array($value)) {
                return implode(', ', $value);
            }

            return $value;
        });
    }

    /**
     * Mark as money field.
     */
    public function money(string $currency = 'USD'): static
    {
        return $this->type('money')->formatter(function ($value) use ($currency) {
            if (! $value) {
                return null;
            }

            return $currency . ' ' . number_format($value, 2);
        });
    }

    /**
     * Mark as enum field.
     */
    public function enum(string $enumClass): static
    {
        return $this->type('enum')->formatter(function ($value) use ($enumClass) {
            if (! $value) {
                return null;
            }

            if (enum_exists($enumClass)) {
                $enum = $enumClass::tryFrom($value);

                return $enum?->name ?? $value;
            }

            return $value;
        });
    }

    /**
     * Mark as relationship field.
     */
    public function relationship(string $titleAttribute = 'name'): static
    {
        return $this->type('relationship')->formatter(function ($value) use ($titleAttribute) {
            if (! $value) {
                return null;
            }

            if (is_object($value)) {
                return $value->{$titleAttribute} ?? $value->id ?? 'Unknown';
            }

            return $value;
        });
    }
}
