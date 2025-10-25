<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanAllowHtml;
use Filament\Infolists\Components\Entry;
use Filament\Support\Concerns\HasExtraAttributes;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Infolists\Concerns\HasModifyState;

class TimelineTitleEntry extends Entry
{
    use CanAllowHtml;
    use HasExtraAttributes;
    use HasModifyState;

    protected string $view = 'filament-activitylog::filament.infolists.components.time-line-title-entry';

    protected ?Closure $configureTitleUsing = null;

    protected ?Closure $shouldConfigureTitleUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTitleEntry();
    }

    /**
     * Configure custom title using a callback.
     */
    public function configureTitleUsing(?Closure $configureTitleUsing): static
    {
        $this->configureTitleUsing = $configureTitleUsing;

        return $this;
    }

    /**
     * Set condition for when to use custom title.
     */
    public function shouldConfigureTitleUsing(?Closure $condition): static
    {
        $this->shouldConfigureTitleUsing = $condition;

        return $this;
    }

    /**
     * Configure the title entry with default settings.
     */
    protected function configureTitleEntry(): void
    {
        $this
            ->hiddenLabel()
            ->modifyState(fn ($state) => $this->modifiedTitle($state));
    }

    /**
     * Generate the modified title for the timeline entry.
     */
    protected function modifiedTitle($state): string|HtmlString|Closure
    {
        // Use custom title if configured
        if ($this->configureTitleUsing !== null
            && $this->shouldConfigureTitleUsing !== null
            && $this->evaluate($this->shouldConfigureTitleUsing)) {
            return $this->evaluate($this->configureTitleUsing, ['state' => $state]);
        }

        // Always generate a meaningful title. Prefer explicit description when provided.
        $subjectName = $this->getSubjectName($state['subject'] ?? null);
        $causerName  = $this->getCauserName($state['causer'] ?? null);
        $eventLabel  = ActivityLogHelper::getEventLabel($state['event'] ?? '');
        $timestamp   = $this->formatTimestamp($state['update'] ?? now());
        $description = trim((string) ($state['description'] ?? ''));
        $eventText   = $description !== '' && $description !== ($state['event'] ?? '')
            ? $description
            : $eventLabel;

        return new HtmlString(
            __('activitylog::infolists.components.created_by_at', [
                'subject'   => $subjectName,
                'event'     => $eventText,
                'causer'    => $causerName,
                'update_at' => $timestamp,
            ])
        );
    }

    /**
     * Get the subject name from the model.
     */
    protected function getSubjectName($subject): string
    {
        if (! $subject) {
            return __('filament-activitylog::timeline.unknown_subject');
        }

        // Check for custom activity title name
        if (property_exists($subject, 'activityTitleName') && ! empty($subject::$activityTitleName)) {
            return $subject::$activityTitleName;
        }

        // Use model class name
        return Str::lower(Str::snake(class_basename($subject), ' '));
    }

    /**
     * Format the timestamp.
     */
    protected function formatTimestamp($timestamp): string
    {
        $relative = (bool) config('filament-activitylog.relative_time', true);
        $format   = config('filament-activitylog.datetime_format', 'Y-m-d H:i:s');

        try {
            $carbon = \Carbon\Carbon::parse($timestamp);

            if ($relative) {
                return $carbon->diffForHumans();
            }

            return $carbon->format($format);
        } catch (\Throwable) {
            return is_string($timestamp) ? $timestamp : (string) $timestamp;
        }
    }
}
