<?php

namespace Rmsramos\Activitylog\Infolists\Concerns;

use Closure;
use Illuminate\Support\HtmlString;

trait HasModifyState
{
    protected $state;

    /**
     * Modify the state using a callback.
     */
    public function modifyState(Closure $callback): static
    {
        $this->state = $callback;

        return $this;
    }

    /**
     * Get the modified state.
     */
    public function getModifiedState(): null|string|HtmlString
    {
        return $this->evaluate($this->state);
    }

    /**
     * Get the causer name from various possible attributes.
     */
    protected function getCauserName($causer): string
    {
        if (! $causer) {
            return trans('activitylog::infolists.components.unknown');
        }

        return $causer->name
            ?? $causer->first_name
            ?? $causer->last_name
            ?? $causer->username
            ?? $causer->email
            ?? trans('activitylog::infolists.components.unknown');
    }
}
