<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Loggers\LoggerRegistry;

class PropertiesTableEntry extends Entry
{
    protected string $view = 'filament-activitylog::filament.infolists.components.properties-table-entry';

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
    }

    public function getState(): mixed
    {
        $record = $this->getRecord();

        return $this->buildTableData($record);
    }

    /**
     * Build the table data from activity properties.
     */
    protected function buildTableData($record): ?array
    {
        $properties = $record->properties ?? [];
        $attributes = $properties['attributes'] ?? [];
        $old        = $properties['old'] ?? [];

        if (empty($attributes) && empty($old)) {
            return null;
        }

        $logger = $this->getLogger($record);
        $rows   = [];

        // Get all unique keys from both attributes and old
        $allKeys = array_unique(array_merge(array_keys($attributes), array_keys($old)));

        foreach ($allKeys as $key) {
            $newValue = $attributes[$key] ?? null;
            $oldValue = $old[$key] ?? null;

            // Skip if values haven't changed
            if ($this->valuesAreEqual($oldValue, $newValue)) {
                continue;
            }

            $rows[] = [
                'field' => $this->getFieldLabel($key, $logger),
                'old'   => $this->formatValue($oldValue, $key, $logger),
                'new'   => $this->formatValue($newValue, $key, $logger),
            ];
        }

        return $rows;
    }

    /**
     * Check if two values are equal.
     */
    protected function valuesAreEqual($oldValue, $newValue): bool
    {
        // Handle null comparisons
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        // Use loose comparison for scalar values
        return $oldValue == $newValue;
    }

    /**
     * Format a value for display.
     */
    protected function formatValue(mixed $value, string $key, $logger): string
    {
        if ($logger) {
            return $logger->formatFieldValue($key, $value);
        }

        return ActivityLogHelper::formatPropertyValue($value);
    }

    /**
     * Get the field label.
     */
    protected function getFieldLabel(string $key, $logger): string
    {
        if ($logger) {
            return $logger->getFieldLabel($key);
        }

        return Str($key)->headline()->toString();
    }

    /**
     * Get the logger for the subject.
     */
    protected function getLogger($record)
    {
        if (! $record->subject_type) {
            return null;
        }

        return LoggerRegistry::resolve($record->subject_type);
    }
}
