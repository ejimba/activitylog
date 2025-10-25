<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\IconEntry;

class TimelineIconEntry extends IconEntry
{
    protected string $view = 'filament-activitylog::filament.infolists.components.time-line-icon-entry';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureIconEntry();
    }

    /**
     * Configure the icon entry with default settings.
     */
    protected function configureIconEntry(): void
    {
        $this
            ->hiddenLabel()
            ->size('w-4 h-4');
    }
}
