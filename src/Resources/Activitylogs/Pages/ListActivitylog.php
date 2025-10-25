<?php

namespace Rmsramos\Activitylog\Resources\Activitylogs\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Rmsramos\Activitylog\Actions\ExportActivitiesAction;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Resources\Activitylogs\ActivitylogResource;

class ListActivitylog extends ListRecords
{
    protected static string $resource = ActivitylogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportActivitiesAction::make()
                ->visible(fn () => config('filament-activitylog.export.enabled', true)),

            Actions\Action::make('prune')
                ->label(__('filament-activitylog::action.prune'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('filament-activitylog::action.prune_confirm_title'))
                ->modalDescription(__('filament-activitylog::action.prune_confirm_description'))
                ->modalSubmitActionLabel(__('filament-activitylog::action.prune'))
                ->action(function () {
                    $days          = config('filament-activitylog.pruning.older_than_days', 30);
                    $activityModel = ActivityLogHelper::getActivityModelClass();

                    $deleted = $activityModel::query()
                        ->where('created_at', '<', now()->subDays($days))
                        ->delete();

                    Notification::make()
                        ->title(__('filament-activitylog::action.prune_success', ['count' => $deleted]))
                        ->success()
                        ->send();
                })
                ->visible(fn () => config('filament-activitylog.pruning.enabled', false)),
        ];
    }
}
