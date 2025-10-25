<?php

namespace Rmsramos\Activitylog\Resources\Activitylogs\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Actions\ViewBatchAction;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Resources\Activitylogs\ActivitylogResource;

class ViewActivitylog extends ViewRecord
{
    protected static string $resource = ActivitylogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewBatchAction::make()
                ->visible(fn (Model $record): bool => $record->batch_uuid !== null),

            Actions\Action::make('view_subject')
                ->label('View Subject')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (Model $record): ?string => ActivityLogHelper::getSubjectUrl($record))
                ->openUrlInNewTab()
                ->visible(fn (Model $record): bool => ActivityLogHelper::getSubjectUrl($record) !== null),

            Actions\Action::make('view_causer')
                ->label('View Causer')
                ->icon('heroicon-o-user')
                ->url(fn (Model $record): ?string => ActivityLogHelper::getCauserUrl($record))
                ->openUrlInNewTab()
                ->visible(fn (Model $record): bool => ActivityLogHelper::getCauserUrl($record) !== null),

            Actions\Action::make('restore_subject')
                ->label(fn (): string => config('filament-activitylog.restore.restore_action_label', __('filament-activitylog::tables.restore')))
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->requiresConfirmation(config('filament-activitylog.restore.require_confirmation', true))
                ->visible(fn (Model $record): bool => ! ActivitylogPlugin::get()->getIsRestoreModelActionHidden())
                ->disabled(fn (Model $record): bool => ! ActivityLogHelper::canRestoreActivity($record))
                ->action(function (): void {
                    $restored = ActivityLogHelper::restoreActivity($this->record);

                    if (! $restored) {
                        Notification::make()
                            ->title(__('filament-activitylog::notifications.restore_failed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->loadMissing('subject');

                    Notification::make()
                        ->title(__('filament-activitylog::notifications.restore_successful'))
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => config('filament-activitylog.resources.allow_delete', false)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // General Information
                \Filament\Infolists\Components\TextEntry::make('log_name')
                    ->label(__('filament-activitylog::tables.columns.log_name'))
                    ->badge()
                    ->columnSpan(1),

                \Filament\Infolists\Components\TextEntry::make('event')
                    ->label(__('filament-activitylog::tables.columns.event'))
                    ->badge()
                    ->color(fn (string $state): string => ActivityLogHelper::getEventColor($state))
                    ->formatStateUsing(fn (string $state): string => ActivityLogHelper::getEventLabel($state))
                    ->columnSpan(1),

                \Filament\Infolists\Components\TextEntry::make('description')
                    ->label(__('filament-activitylog::tables.columns.description'))
                    ->default('-')
                    ->columnSpan(1),

                \Filament\Infolists\Components\TextEntry::make('created_at')
                    ->label(__('filament-activitylog::tables.columns.created_at'))
                    ->dateTime()
                    ->since()
                    ->hint(fn (Model $record): string => $record->created_at->format('M d, Y H:i:s'))
                    ->columnSpan(1),

                // Subject Information
                \Filament\Infolists\Components\TextEntry::make('subject_type')
                    ->label(__('filament-activitylog::tables.columns.subject'))
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->visible(fn (Model $record): bool => $record->subject_type !== null)
                    ->columnSpan(2),

                // Causer Information
                \Filament\Infolists\Components\TextEntry::make('causer_type')
                    ->label(__('filament-activitylog::tables.columns.causer'))
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->visible(fn (Model $record): bool => $record->causer_type !== null)
                    ->columnSpan(2),

                \Filament\Infolists\Components\TextEntry::make('causer.name')
                    ->label('Causer Name')
                    ->default('-')
                    ->visible(fn (Model $record): bool => $record->causer !== null)
                    ->columnSpan(1),

                \Filament\Infolists\Components\TextEntry::make('causer.email')
                    ->label('Causer Email')
                    ->default('-')
                    ->visible(fn (Model $record): bool => $record->causer && method_exists($record->causer, 'getAttribute'))
                    ->columnSpan(1),

                // Properties
                \Rmsramos\Activitylog\Infolists\Components\PropertiesTableEntry::make('properties')
                    ->label(__('filament-activitylog::resource.view.properties'))
                    ->visible(fn (Model $record): bool => ! empty($record->properties['attributes'] ?? []) ||
                        ! empty($record->properties['old'] ?? [])
                    )
                    ->columnSpan(2),

                // Batch Information
                \Filament\Infolists\Components\TextEntry::make('batch_uuid')
                    ->label(__('filament-activitylog::batch.batch_uuid'))
                    ->copyable()
                    ->copyMessage(__('filament-activitylog::batch.copied'))
                    ->fontFamily('mono')
                    ->size('sm')
                    ->visible(fn (Model $record): bool => $record->batch_uuid !== null)
                    ->columnSpan(2),

                \Rmsramos\Activitylog\Infolists\Components\BatchActivitiesEntry::make('batch_uuid')
                    ->label(__('filament-activitylog::batch.activities_list'))
                    ->showCauser()
                    ->showSubject()
                    ->showTimestamp()
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (Model $record): bool => $record->batch_uuid !== null)
                    ->columnSpan(2),

                // Back to list button
                \Filament\Infolists\Components\ViewEntry::make('back_button')
                    ->view('filament-activitylog::filament.components.back-to-list-button')
                    ->columnSpan(2),
            ])
            ->columns(2);
    }
}
