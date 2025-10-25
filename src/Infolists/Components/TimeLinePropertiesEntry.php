<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\HtmlString;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Infolists\Concerns\HasModifyState;
use Rmsramos\Activitylog\Loggers\LoggerRegistry;

class TimelinePropertiesEntry extends Entry
{
    use HasModifyState;

    protected string $view = 'filament-activitylog::filament.infolists.components.time-line-propertie-entry';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurePropertiesEntry();
    }

    /**
     * Configure the properties entry with default settings.
     */
    protected function configurePropertiesEntry(): void
    {
        $this
            ->hiddenLabel()
            ->modifyState(fn ($state) => $this->modifiedProperties($state));
    }

    /**
     * Generate the modified properties display.
     */
    protected function modifiedProperties($state): ?HtmlString
    {
        $properties = $state['properties'] ?? [];

        if (empty($properties)) {
            return null;
        }

        $rows = $this->getPropertyChanges($properties, $state);

        if (empty($rows)) {
            return null;
        }

        return $this->renderPropertiesTable($rows);
    }

    /**
     * Render the properties table.
     */
    protected function renderPropertiesTable(array $rows): HtmlString
    {
        $tableHtml = '<div class="mt-2 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">';
        $tableHtml .= '<table class="w-full text-sm">';
        $tableHtml .= '<thead class="bg-gray-50 dark:bg-gray-800">';
        $tableHtml .= '<tr>';
        $tableHtml .= '<th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Field</th>';
        $tableHtml .= '<th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">Old Value</th>';
        $tableHtml .= '<th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300">New Value</th>';
        $tableHtml .= '</tr>';
        $tableHtml .= '</thead>';
        $tableHtml .= '<tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">';

        foreach ($rows as $row) {
            $tableHtml .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">';
            $tableHtml .= '<td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-100">' . $row['field'] . '</td>';
            $tableHtml .= '<td class="px-3 py-2 text-gray-600 dark:text-gray-400">' . ($row['old'] ?? '-') . '</td>';
            $tableHtml .= '<td class="px-3 py-2 text-gray-600 dark:text-gray-400">' . ($row['new'] ?? '-') . '</td>';
            $tableHtml .= '</tr>';
        }

        $tableHtml .= '</tbody>';
        $tableHtml .= '</table>';
        $tableHtml .= '</div>';

        return new HtmlString($tableHtml);
    }

    /**
     * Get the property changes from the activity.
     */
    protected function getPropertyChanges(array $properties, array $state): array
    {
        $rows = [];

        if (isset($properties['old'], $properties['attributes'])) {
            $rows = $this->compareOldAndNewValues(
                $properties['old'],
                $properties['attributes'],
                $state
            );
        } elseif (isset($properties['attributes'])) {
            $rows = $this->getNewValues($properties['attributes'], $state);
        } elseif (isset($properties['old'])) {
            $rows = $this->getOldValues($properties['old'], $state);
        }

        return $rows;
    }

    /**
     * Compare old and new values.
     */
    protected function compareOldAndNewValues(array $oldValues, array $newValues, array $state): array
    {
        $rows   = [];
        $logger = $this->getLogger($state);

        foreach ($newValues as $key => $newValue) {
            $oldValue          = $oldValues[$key] ?? null;
            $formattedOldValue = $this->formatValue($oldValue, $key, $logger);
            $formattedNewValue = $this->formatValue($newValue, $key, $logger);

            if ($oldValue != $newValue) {
                $fieldLabel = $this->getFieldLabel($key, $logger);

                $rows[] = [
                    'field' => htmlspecialchars($fieldLabel),
                    'old'   => htmlspecialchars($formattedOldValue),
                    'new'   => htmlspecialchars($formattedNewValue),
                ];
            }
        }

        return $rows;
    }

    /**
     * Get new values for created records.
     */
    protected function getNewValues(array $newValues, array $state): array
    {
        $rows   = [];
        $logger = $this->getLogger($state);

        foreach ($newValues as $key => $value) {
            $fieldLabel     = $this->getFieldLabel($key, $logger);
            $formattedValue = $this->formatValue($value, $key, $logger);

            $rows[] = [
                'field' => htmlspecialchars($fieldLabel),
                'old'   => null,
                'new'   => htmlspecialchars($formattedValue),
            ];
        }

        return $rows;
    }

    /**
     * Get old values for deleted records.
     */
    protected function getOldValues(array $oldValues, array $state): array
    {
        return $this->getNewValues($oldValues, $state);
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
    protected function getLogger(array $state)
    {
        if (! isset($state['subject'])) {
            return null;
        }

        $subjectType = get_class($state['subject']);

        return LoggerRegistry::resolve($subjectType);
    }
}
