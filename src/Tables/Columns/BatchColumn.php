<?php

namespace Rmsramos\Activitylog\Tables\Columns;

use Filament\Tables\Columns\Column;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;

class BatchColumn extends Column
{
    protected string $view = 'filament-activitylog::filament.tables.columns.batch-column';

    protected bool|\Closure $showCount = true;

    protected bool|\Closure $showIcon = true;

    protected bool|\Closure $clickable = false;

    public function showCount(bool|\Closure $condition = true): static
    {
        $this->showCount = $condition;

        return $this;
    }

    public function getShowCount(): bool
    {
        return $this->evaluate($this->showCount);
    }

    public function showIcon(bool|\Closure $condition = true): static
    {
        $this->showIcon = $condition;

        return $this;
    }

    public function getShowIcon(): bool
    {
        return $this->evaluate($this->showIcon);
    }

    public function clickable(bool|\Closure $condition = true): static
    {
        $this->clickable = $condition;

        return $this;
    }

    public function getClickable(): bool
    {
        return $this->evaluate($this->clickable);
    }

    public function getBatchCount(): ?int
    {
        $batchUuid = $this->getState();

        if (! $batchUuid) {
            return null;
        }

        $activityClass = ActivityLogHelper::getActivityModelClass();

        return $activityClass::query()
            ->where('batch_uuid', $batchUuid)
            ->count();
    }
}
