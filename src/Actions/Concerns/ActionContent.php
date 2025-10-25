<?php

namespace Rmsramos\Activitylog\Actions\Concerns;

use Closure;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Infolists\Components\TimelineIconEntry;
use Rmsramos\Activitylog\Infolists\Components\TimelinePropertiesEntry;
use Rmsramos\Activitylog\Infolists\Components\TimelineRepeatableEntry;
use Rmsramos\Activitylog\Infolists\Components\TimelineTitleEntry;

trait ActionContent
{
    protected ?array $withRelations = null;

    protected ?array $timelineIcons = null;

    protected ?array $timelineIconColors = null;

    protected ?int $limit = 10;

    protected Closure $modifyQueryUsing;

    protected Closure|Builder $query;

    protected ?Closure $activitiesUsing = null;

    protected ?Closure $modifyTitleUsing = null;

    protected ?Closure $shouldModifyTitleUsing = null;

    /**
     * Get the default action name.
     */
    public static function getDefaultName(): ?string
    {
        return 'activity_timeline';
    }

    /**
     * Set up the action.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureInfolist();
        $this->configureModal();
        $this->initializeDefaults();
    }

    /**
     * Initialize default values.
     */
    protected function initializeDefaults(): void
    {
        $this->activitiesUsing        = null;
        $this->modifyTitleUsing       = null;
        $this->shouldModifyTitleUsing = fn () => true;
        $this->modifyQueryUsing       = fn ($builder) => $builder;
        $this->modalHeading           = __('filament-activitylog::timeline.modal.heading');
        $this->modalDescription       = __('filament-activitylog::timeline.modal.description');

        $this->query = function (?Model $record) {
            return $this->buildQuery($record);
        };
    }

    /**
     * Build the activity query.
     */
    protected function buildQuery(?Model $record): Builder
    {
        if (! $record) {
            return ActivityLogHelper::activityQuery()->whereRaw('1 = 0');
        }

        $subjectId = $record->getKey();

        $subjectTypes = array_unique([
            $record->getMorphClass(),
            get_class($record),
        ]);

        return ActivityLogHelper::activityQuery()
            ->with([
                'subject' => function ($query) {
                    if (method_exists($query, 'withTrashed')) {
                        $query->withTrashed();
                    }
                },
                'causer',
            ])
            ->where(function (Builder $query) use ($record, $subjectTypes, $subjectId) {
                $query->whereIn('subject_type', $subjectTypes)
                    ->where('subject_id', $subjectId);

                if ($relations = $this->getWithRelations()) {
                    $this->addRelationQueries($query, $record, $relations);
                }
            })
            ->latest();
    }

    /**
     * Add relation queries to the builder.
     */
    protected function addRelationQueries(Builder $query, Model $record, array $relations): void
    {
        foreach ($relations as $relation) {
            try {
                if (! method_exists($record, $relation)) {
                    continue;
                }

                $relationInstance = $record->{$relation}();

                if (! $relationInstance instanceof BelongsToMany) {
                    continue;
                }

                $subjectType = $relationInstance->getPivotClass();
                $relatedIds  = $relationInstance->pluck($relationInstance->getTable() . '.id')->toArray();

                if (! empty($relatedIds)) {
                    $query->orWhere(function (Builder $q) use ($subjectType, $relatedIds) {
                        $q->where('subject_type', $subjectType)
                            ->whereIn('subject_id', $relatedIds);
                    });
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }

    /**
     * Configure the infolist.
     */
    protected function configureInfolist(): void
    {
        $this->infolist(function (?Model $record, Schema $schema) {
            $subject = null;

            $activityClass = ActivityLogHelper::getActivityModelClass();

            if ($record instanceof $activityClass) {
                $subject = $record->subject;
            } else {
                $subject = $record;
            }

            $activities = $this->getActivitiesForSubject($subject);
            return $schema
                ->state(['activities' => $activities])
                ->schema([
                    TimelineRepeatableEntry::make('activities')
                        ->placeholder(__('filament-activitylog::timeline.empty'))
                        ->schema([
                            TimelineIconEntry::make('event')
                                ->icon(fn ($state) => $this->getTimelineIcon($state))
                                ->color(fn ($state) => $this->getTimelineIconColor($state)),
                            TimelineTitleEntry::make('title')
                                ->configureTitleUsing($this->modifyTitleUsing)
                                ->shouldConfigureTitleUsing($this->shouldModifyTitleUsing),
                            TimelinePropertiesEntry::make('properties'),
                        ]),
                ]);
        });
    }

    /**
     * Configure the modal.
     */
    protected function configureModal(): void
    {
        $this
            ->modalWidth('3xl')
            ->slideOver()
            ->icon('heroicon-o-clock')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament-activitylog::action.close'));
    }

    /**
     * Get activities for the timeline.
     */
    protected function getActivities(): array
    {
        if ($this->activitiesUsing) {
            return $this->evaluate($this->activitiesUsing);
        }

        // Get the record from the action context
        $record = $this->getRecord();

        $query = $this->evaluate($this->query, ['record' => $record]);

        if (! $query) {
            return [];
        }

        $modifiedQuery = $this->evaluate($this->modifyQueryUsing, ['query' => $query]);

        // If modifyQueryUsing doesn't return a query, use the original
        if ($modifiedQuery instanceof Builder) {
            $query = $modifiedQuery;
        }

        if ($this->limit) {
            $query->limit($this->limit);
        }

        return $query->get()->map(fn (Model $activity) => $this->transformActivity($activity))->toArray();
    }

    /**
     * Get activities for a specific subject (used when record is an Activity).
     */
    protected function getActivitiesForSubject(?Model $subject): array
    {
        if (! $subject) {
            return [];
        }

        if ($this->activitiesUsing) {
            return $this->evaluate($this->activitiesUsing);
        }

        $query = $this->buildQuery($subject);

        if (! $query) {
            return [];
        }

        $modifiedQuery = $this->evaluate($this->modifyQueryUsing, ['query' => $query]);

        // If modifyQueryUsing doesn't return a query, use the original
        if ($modifiedQuery instanceof Builder) {
            $query = $modifiedQuery;
        }

        if ($this->limit) {
            $query->limit($this->limit);
        }

        return $query->get()->map(fn (Model $activity) => $this->transformActivity($activity))->toArray();
    }

    /**
     * Get the timeline icon for an event.
     */
    protected function getTimelineIcon(string $event): string
    {
        if ($this->timelineIcons && isset($this->timelineIcons[$event])) {
            return $this->timelineIcons[$event];
        }

        // Default icons for common events
        $defaultIcons = [
            'created'  => 'heroicon-o-plus-circle',
            'updated'  => 'heroicon-o-pencil-square',
            'deleted'  => 'heroicon-o-trash',
            'restored' => 'heroicon-o-arrow-path',
        ];

        return $defaultIcons[$event] ?? 'heroicon-o-clock';
    }

    /**
     * Normalize an activity model into timeline-friendly state.
     */
    protected function transformActivity(Model $activity): array
    {
        return [
            'event'       => $activity->event,
            'description' => $activity->description,
            'subject'     => $activity->subject,
            'causer'      => $activity->causer,
            'properties'  => $activity->properties?->toArray() ?? [],
            'update'      => $activity->created_at,
            'title'       => [
                'event'       => $activity->event,
                'description' => $activity->description,
                'subject'     => $activity->subject,
                'causer'      => $activity->causer,
                'update'      => $activity->created_at,
            ],
        ];
    }

    /**
     * Get the timeline icon color for an event.
     */
    protected function getTimelineIconColor(string $event): string
    {
        if ($this->timelineIconColors && isset($this->timelineIconColors[$event])) {
            return $this->timelineIconColors[$event];
        }

        // Default colors for common events
        $defaultColors = [
            'created'  => 'success',
            'updated'  => 'warning',
            'deleted'  => 'danger',
            'restored' => 'info',
        ];

        return $defaultColors[$event] ?? 'gray';
    }

    /**
     * Set custom timeline icons.
     */
    public function timelineIcons(array $icons): static
    {
        $this->timelineIcons = $icons;

        return $this;
    }

    /**
     * Set custom timeline icon colors.
     */
    public function timelineIconColors(array $colors): static
    {
        $this->timelineIconColors = $colors;

        return $this;
    }

    /**
     * Set the limit for activities.
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Include relations in the timeline.
     */
    public function withRelations(array $relations): static
    {
        $this->withRelations = array_values(array_filter($relations));

        return $this;
    }

    /**
     * Get the relations to include.
     */
    public function getWithRelations(): ?array
    {
        return $this->withRelations;
    }

    /**
     * Modify the query using a callback.
     */
    public function modifyQueryUsing(Closure $callback): static
    {
        $this->modifyQueryUsing = $callback;

        return $this;
    }

    /**
     * Set custom activities using a callback.
     */
    public function activitiesUsing(Closure $callback): static
    {
        $this->activitiesUsing = $callback;

        return $this;
    }

    /**
     * Modify the title using a callback.
     */
    public function modifyTitleUsing(Closure $callback): static
    {
        $this->modifyTitleUsing = $callback;

        return $this;
    }

    /**
     * Set condition for when to modify the title.
     */
    public function shouldModifyTitleUsing(Closure $callback): static
    {
        $this->shouldModifyTitleUsing = $callback;

        return $this;
    }
}
