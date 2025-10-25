<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\RepeatableEntry;

class TimelineRepeatableEntry extends RepeatableEntry
{
    protected string $view = 'filament-activitylog::filament.infolists.components.time-line-repeatable-entry';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureRepeatableEntry();
    }

    /**
     * Configure the repeatable entry with default settings.
     */
    protected function configureRepeatableEntry(): void
    {
        $this
            ->extraAttributes(['style' => 'margin-left:1.25rem;'])
            ->contained(false)
            ->hiddenLabel();
    }
}
