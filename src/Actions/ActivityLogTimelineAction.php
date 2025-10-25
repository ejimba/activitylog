<?php

namespace Rmsramos\Activitylog\Actions;

use Filament\Actions\Action;
use Rmsramos\Activitylog\Actions\Concerns\ActionContent;

class ActivityLogTimelineAction extends Action
{
    use ActionContent {
        setUp as protected setUpActionContent;
    }

    protected function setUp(): void
    {
        $this->setUpActionContent();

        $config = config('filament-activitylog.timeline', []);

        $label = $config['action_label'] ?? __('filament-activitylog::timeline.action_label');

        if ($label === 'filament-activitylog::timeline.action_label') {
            $label = __('Activity Timeline');
        }

        $this->label($label);

        if (! empty($config['action_icon'])) {
            $this->icon($config['action_icon']);
        }

        if (array_key_exists('limit', $config) && $config['limit'] !== null) {
            $this->limit((int) $config['limit']);
        }

        if (! empty($config['with_relations']) && is_array($config['with_relations'])) {
            $this->withRelations($config['with_relations']);
        }

        if (! empty($config['icons']) && is_array($config['icons'])) {
            $this->timelineIcons($config['icons']);
        }

        if (! empty($config['colors']) && is_array($config['colors'])) {
            $this->timelineIconColors($config['colors']);
        }
    }
}
