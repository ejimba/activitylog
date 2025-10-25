<?php

namespace Rmsramos\Activitylog\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\Collection;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;

class BatchActivitiesEntry extends Entry
{
    protected string $view = 'filament-activitylog::filament.infolists.components.batch-activities-entry';

    protected int|\Closure|null $limit = null;

    protected bool|\Closure $showCauser = true;

    protected bool|\Closure $showSubject = true;

    protected bool|\Closure $showTimestamp = true;

    protected bool|\Closure $showProperties = true;

    protected bool|\Closure $collapsible = false;

    protected bool|\Closure $collapsed = false;

    public function limit(int|\Closure|null $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->evaluate($this->limit);
    }

    public function showCauser(bool|\Closure $condition = true): static
    {
        $this->showCauser = $condition;

        return $this;
    }

    public function getShowCauser(): bool
    {
        return $this->evaluate($this->showCauser);
    }

    public function showSubject(bool|\Closure $condition = true): static
    {
        $this->showSubject = $condition;

        return $this;
    }

    public function getShowSubject(): bool
    {
        return $this->evaluate($this->showSubject);
    }

    public function showTimestamp(bool|\Closure $condition = true): static
    {
        $this->showTimestamp = $condition;

        return $this;
    }

    public function getShowTimestamp(): bool
    {
        return $this->evaluate($this->showTimestamp);
    }

    public function showProperties(bool|\Closure $condition = true): static
    {
        $this->showProperties = $condition;

        return $this;
    }

    public function getShowProperties(): bool
    {
        return $this->evaluate($this->showProperties);
    }

    public function collapsible(bool|\Closure $condition = true): static
    {
        $this->collapsible = $condition;

        return $this;
    }

    public function getCollapsible(): bool
    {
        return $this->evaluate($this->collapsible);
    }

    public function collapsed(bool|\Closure $condition = false): static
    {
        $this->collapsed = $condition;

        return $this;
    }

    public function getCollapsed(): bool
    {
        return $this->evaluate($this->collapsed);
    }

    public function getBatchActivities(): Collection
    {
        $batchUuid = $this->getState();

        if (! $batchUuid) {
            return collect();
        }

        $query = ActivityLogHelper::activityQuery()
            ->where('batch_uuid', $batchUuid)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'asc');

        if ($limit = $this->getLimit()) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
