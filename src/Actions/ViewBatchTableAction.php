<?php

namespace Rmsramos\Activitylog\Actions;

use Filament\Actions\Action;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;

class ViewBatchTableAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'viewBatch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-activitylog::action.view_batch'))
            ->icon('heroicon-o-queue-list')
            ->color('gray')
            ->modalHeading(fn ($record) => __('filament-activitylog::batch.modal_heading'))
            ->modalDescription(fn ($record) => __('filament-activitylog::batch.modal_description'))
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament-activitylog::action.close'))
            ->infolist(fn (Model $record): Infolist => Infolist::make()
                ->record($record)
                ->schema([
                    Infolists\Components\Section::make(__('filament-activitylog::batch.batch_info'))
                        ->schema([
                            Infolists\Components\TextEntry::make('batch_uuid')
                                ->label(__('filament-activitylog::batch.batch_id'))
                                ->copyable(),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label(__('filament-activitylog::batch.created_at'))
                                ->dateTime(),
                        ])
                        ->columns(2),

                    Infolists\Components\Section::make(__('filament-activitylog::batch.activities'))
                        ->schema([
                            Infolists\Components\RepeatableEntry::make('batch_activities')
                                ->label('')
                                ->getStateUsing(fn (Model $record) => ActivityLogHelper::activityQuery()
                                    ->where('batch_uuid', $record->batch_uuid)
                                    ->with(['causer', 'subject'])
                                    ->latest()
                                    ->get())
                                ->schema([
                                    Infolists\Components\TextEntry::make('event')
                                        ->label(__('filament-activitylog::tables.columns.event'))
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => ActivityLogHelper::getEventLabel($state))
                                        ->color(fn ($state) => ActivityLogHelper::getEventColor($state)),
                                    Infolists\Components\TextEntry::make('subject_type')
                                        ->label(__('filament-activitylog::tables.columns.subject'))
                                        ->formatStateUsing(fn ($state) => class_basename($state)),
                                    Infolists\Components\TextEntry::make('causer.name')
                                        ->label(__('filament-activitylog::tables.columns.causer'))
                                        ->default(__('activitylog::infolists.components.unknown')),
                                    Infolists\Components\TextEntry::make('description')
                                        ->label(__('filament-activitylog::tables.columns.description')),
                                    Infolists\Components\TextEntry::make('created_at')
                                        ->label(__('filament-activitylog::tables.columns.created_at'))
                                        ->dateTime()
                                        ->since(),
                                ])
                                ->columns(2),
                        ]),
                ])
            )
            ->visible(fn (Model $record): bool => ! empty($record->batch_uuid));
    }
}
