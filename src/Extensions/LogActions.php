<?php

namespace Rmsramos\Activitylog\Extensions;

use Closure;
use Filament\Actions as PageActions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions as TableActions;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Activitylog\Facades\LogBatch;

class LogActions
{
    /**
     * Actions to configure for automatic logging.
     */
    public array $targets = [
        // Table Actions
        TableActions\CreateAction::class          => 'create',
        TableActions\EditAction::class            => 'edit',
        TableActions\DeleteAction::class          => 'delete',
        TableActions\ForceDeleteAction::class     => 'forceDelete',
        TableActions\RestoreAction::class         => 'restore',
        TableActions\ReplicateAction::class       => 'replicate',
        TableActions\AttachAction::class          => 'attach',
        TableActions\DetachAction::class          => 'detach',
        TableActions\DeleteBulkAction::class      => 'deleteBulk',
        TableActions\ForceDeleteBulkAction::class => 'forceDeleteBulk',
        TableActions\RestoreBulkAction::class     => 'restoreBulk',
        TableActions\DetachBulkAction::class      => 'detachBulk',

        // Page Actions
        PageActions\CreateAction::class      => 'create',
        PageActions\EditAction::class        => 'edit',
        PageActions\DeleteAction::class      => 'delete',
        PageActions\ForceDeleteAction::class => 'forceDelete',
        PageActions\RestoreAction::class     => 'restore',
        PageActions\ReplicateAction::class   => 'replicate',

        // Editable Columns
        Columns\CheckboxColumn::class  => 'editableColumn',
        Columns\SelectColumn::class    => 'editableColumn',
        Columns\TextInputColumn::class => 'editableColumn',
        Columns\ToggleColumn::class    => 'editableColumn',
    ];

    /**
     * Register automatic logging for all target actions.
     */
    public static function register(): void
    {
        (new static)->configure();
    }

    /**
     * Configure automatic logging for all target actions.
     */
    public function configure(): void
    {
        $config = config('filament-activitylog.auto_log.actions', []);

        foreach ($this->targets as $class => $method) {
            // Check if this action type is enabled in config
            $actionType = $this->getActionType($method);

            if (isset($config[$actionType]) && ! $config[$actionType]) {
                continue;
            }

            if (class_exists($class)) {
                $class::configureUsing(Closure::fromCallable([$this, $method]));
            }
        }
    }

    /**
     * Get the action type from method name.
     */
    protected function getActionType(string $method): string
    {
        return match ($method) {
            'create', 'replicate' => 'create',
            'edit', 'editableColumn' => 'edit',
            'delete', 'deleteBulk', 'forceDelete', 'forceDeleteBulk' => 'delete',
            'restore', 'restoreBulk' => 'restore',
            'attach' => 'attach',
            'detach', 'detachBulk' => 'detach',
            default => $method,
        };
    }

    /**
     * Log create action.
     */
    public function create($action): void
    {
        $action->after(function ($livewire, $record) {
            if ($record instanceof Model) {
                $this->addRelationManagerContext($livewire, $record);
            }
        });
    }

    /**
     * Log edit action.
     */
    public function edit($action): void
    {
        $action->after(function ($livewire, $record) {
            if ($record instanceof Model) {
                $this->addRelationManagerContext($livewire, $record);
            }
        });
    }

    /**
     * Log delete action.
     */
    public function delete($action): void
    {
        $action->after(function ($livewire, $record) {
            if ($record instanceof Model) {
                $this->addRelationManagerContext($livewire, $record);
            }
        });
    }

    /**
     * Log force delete action.
     */
    public function forceDelete($action): void
    {
        $action->before(function ($livewire, $record) {
            if ($record instanceof Model) {
                // Log before force delete since the record will be gone
                activity()
                    ->performedOn($record)
                    ->event('force_deleted')
                    ->withProperties($this->getRelationManagerProperties($livewire))
                    ->log('Force deleted');
            }
        });
    }

    /**
     * Log restore action.
     */
    public function restore($action): void
    {
        $action->after(function ($livewire, $record) {
            if ($record instanceof Model) {
                $this->addRelationManagerContext($livewire, $record);
            }
        });
    }

    /**
     * Log replicate action.
     */
    public function replicate($action): void
    {
        $action->after(function ($livewire, $replica) {
            if ($replica instanceof Model) {
                activity()
                    ->performedOn($replica)
                    ->event('replicated')
                    ->withProperties($this->getRelationManagerProperties($livewire))
                    ->log('Replicated');
            }
        });
    }

    /**
     * Log attach action.
     */
    public function attach($action): void
    {
        $action->after(function ($livewire, $record) {
            $this->logAttach($livewire, $record);
        });
    }

    /**
     * Log detach action.
     */
    public function detach($action): void
    {
        $action->after(function ($livewire, $record) {
            $this->logDetach($livewire, $record);
        });
    }

    /**
     * Log bulk delete action.
     */
    public function deleteBulk($action): void
    {
        $action->after(function ($livewire, $records) {
            LogBatch::startBatch();

            foreach ($records as $record) {
                if ($record instanceof Model) {
                    $this->addRelationManagerContext($livewire, $record);
                }
            }
            LogBatch::endBatch();
        });
    }

    /**
     * Log bulk force delete action.
     */
    public function forceDeleteBulk($action): void
    {
        $action->before(function ($livewire, $records) {
            LogBatch::startBatch();

            foreach ($records as $record) {
                if ($record instanceof Model) {
                    activity()
                        ->performedOn($record)
                        ->event('force_deleted')
                        ->withProperties($this->getRelationManagerProperties($livewire))
                        ->log('Force deleted');
                }
            }
            LogBatch::endBatch();
        });
    }

    /**
     * Log bulk restore action.
     */
    public function restoreBulk($action): void
    {
        $action->after(function ($livewire, $records) {
            LogBatch::startBatch();

            foreach ($records as $record) {
                if ($record instanceof Model) {
                    $this->addRelationManagerContext($livewire, $record);
                }
            }
            LogBatch::endBatch();
        });
    }

    /**
     * Log bulk detach action.
     */
    public function detachBulk($action): void
    {
        $action->after(function ($livewire, $records) {
            LogBatch::startBatch();

            foreach ($records as $record) {
                $this->logDetach($livewire, $record);
            }
            LogBatch::endBatch();
        });
    }

    /**
     * Log editable column changes.
     */
    public function editableColumn($column): void
    {
        if (! config('filament-activitylog.auto_log.editable_columns', true)) {
            return;
        }

        $column->afterStateUpdated(function ($record, $state, $column) {
            if ($record instanceof Model) {
                activity()
                    ->performedOn($record)
                    ->event('updated')
                    ->withProperties([
                        'attributes' => [$column->getName() => $state],
                        'old'        => [$column->getName() => $record->getOriginal($column->getName())],
                    ])
                    ->log('Updated ' . $column->getLabel());
            }
        });
    }

    /**
     * Log attach relationship.
     */
    protected function logAttach($livewire, $record): void
    {
        if (! $livewire instanceof RelationManager) {
            return;
        }

        $owner            = $livewire->getOwnerRecord();
        $relationshipName = $livewire->getRelationshipName();

        activity()
            ->performedOn($owner)
            ->event('attached')
            ->withProperties([
                'relation_manager' => [
                    'name'          => $relationshipName,
                    'attached_id'   => $record->getKey(),
                    'attached_type' => get_class($record),
                ],
            ])
            ->log("Attached {$relationshipName}");
    }

    /**
     * Log detach relationship.
     */
    protected function logDetach($livewire, $record): void
    {
        if (! $livewire instanceof RelationManager) {
            return;
        }

        $owner            = $livewire->getOwnerRecord();
        $relationshipName = $livewire->getRelationshipName();

        activity()
            ->performedOn($owner)
            ->event('detached')
            ->withProperties([
                'relation_manager' => [
                    'name'          => $relationshipName,
                    'detached_id'   => $record->getKey(),
                    'detached_type' => get_class($record),
                ],
            ])
            ->log("Detached {$relationshipName}");
    }

    /**
     * Add relation manager context to the last activity.
     */
    protected function addRelationManagerContext($livewire, Model $record): void
    {
        $properties = $this->getRelationManagerProperties($livewire);

        if (empty($properties)) {
            return;
        }

        if (! method_exists($record, 'activities')) {
            return;
        }

        $activitiesRelation = $record->activities();

        if (! $activitiesRelation instanceof Relation) {
            return;
        }

        $lastActivity = $activitiesRelation->latest()->first();

        if (! $lastActivity) {
            return;
        }

        $lastActivity->properties = array_merge(
            $lastActivity->properties->toArray(),
            $properties
        );

        $lastActivity->save();
    }

    /**
     * Get relation manager properties.
     */
    protected function getRelationManagerProperties($livewire): array
    {
        if (! $livewire instanceof RelationManager) {
            return [];
        }

        return [
            'relation_manager' => [
                'name'       => $livewire->getRelationshipName(),
                'owner_id'   => $livewire->getOwnerRecord()->getKey(),
                'owner_type' => get_class($livewire->getOwnerRecord()),
            ],
        ];
    }
}
