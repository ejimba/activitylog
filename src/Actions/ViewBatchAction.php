<?php

namespace Rmsramos\Activitylog\Actions;

use Filament\Actions\Action;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;

class ViewBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'viewBatch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-activitylog::action.view_batch'));

        $this->icon('heroicon-o-queue-list');

        $this->color('gray');

        $this->modalHeading(fn (Model $record): string => __('filament-activitylog::batch.modal_heading'));

        $this->modalDescription(fn (Model $record): ?string => $record->batch_uuid
            ? __('filament-activitylog::batch.modal_description', ['uuid' => substr($record->batch_uuid, 0, 8)])
            : null
        );

        $this->modalWidth(Width::FourExtraLarge);

        $this->modalSubmitAction(false);

        $this->modalCancelActionLabel(__('filament-activitylog::action.close'));

        $this->infolist(function (Model $record): Infolist {
            $batchActivities = ActivityLogHelper::activityQuery()
                ->where('batch_uuid', $record->batch_uuid)
                ->with(['causer', 'subject'])
                ->orderBy('created_at', 'asc')
                ->get();

            return Infolist::make()
                ->state([
                    'batch_uuid'       => $record->batch_uuid,
                    'total_activities' => $batchActivities->count(),
                    'started_at'       => $batchActivities->first()?->created_at,
                    'ended_at'         => $batchActivities->last()?->created_at,
                    'duration'         => $batchActivities->first() && $batchActivities->last()
                        ? $batchActivities->first()->created_at->diffInSeconds($batchActivities->last()->created_at)
                        : null,
                ])
                ->schema([
                    Infolists\Components\Section::make(__('filament-activitylog::batch.summary'))
                        ->schema([
                            Infolists\Components\TextEntry::make('batch_uuid')
                                ->label(__('filament-activitylog::batch.batch_id'))
                                ->copyable()
                                ->copyMessage(__('filament-activitylog::batch.copied'))
                                ->copyMessageDuration(1500)
                                ->fontFamily('mono')
                                ->size('sm'),

                            Infolists\Components\TextEntry::make('total_activities')
                                ->label(__('filament-activitylog::batch.total_activities'))
                                ->badge()
                                ->color('info'),

                            Infolists\Components\TextEntry::make('started_at')
                                ->label(__('filament-activitylog::batch.started_at'))
                                ->dateTime()
                                ->since()
                                ->description(fn ($state) => $state?->format('M d, Y H:i:s')),

                            Infolists\Components\TextEntry::make('ended_at')
                                ->label(__('filament-activitylog::batch.ended_at'))
                                ->dateTime()
                                ->since()
                                ->description(fn ($state) => $state?->format('M d, Y H:i:s')),

                            Infolists\Components\TextEntry::make('duration')
                                ->label(__('filament-activitylog::batch.duration'))
                                ->formatStateUsing(function ($state) {
                                    if ($state === null) {
                                        return '-';
                                    }

                                    if ($state < 1) {
                                        return '< 1s';
                                    }

                                    if ($state < 60) {
                                        return "{$state}s";
                                    }
                                    $minutes = floor($state / 60);
                                    $seconds = $state % 60;

                                    return "{$minutes}m {$seconds}s";
                                })
                                ->badge()
                                ->color('success'),
                        ])
                        ->columns(3),

                    Infolists\Components\Section::make(__('filament-activitylog::batch.activities'))
                        ->schema([
                            Infolists\Components\RepeatableEntry::make('activities')
                                ->label('')
                                ->schema([
                                    Infolists\Components\TextEntry::make('event')
                                        ->label(__('filament-activitylog::tables.columns.event'))
                                        ->badge()
                                        ->color(fn (string $state): string => ActivityLogHelper::getEventColor($state))
                                        ->formatStateUsing(fn (string $state): string => ActivityLogHelper::getEventLabel($state)),

                                    Infolists\Components\TextEntry::make('subject_type')
                                        ->label(__('filament-activitylog::tables.columns.subject'))
                                        ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),

                                    Infolists\Components\TextEntry::make('subject_id')
                                        ->label('ID'),

                                    Infolists\Components\TextEntry::make('causer.name')
                                        ->label(__('filament-activitylog::tables.columns.causer'))
                                        ->default('-'),

                                    Infolists\Components\TextEntry::make('description')
                                        ->label(__('filament-activitylog::tables.columns.description'))
                                        ->default('-')
                                        ->columnSpanFull(),

                                    Infolists\Components\TextEntry::make('created_at')
                                        ->label(__('filament-activitylog::tables.columns.created_at'))
                                        ->dateTime()
                                        ->since()
                                        ->description(fn ($state) => $state->format('H:i:s.u')),
                                ])
                                ->state($batchActivities->toArray())
                                ->columns(4)
                                ->contained(false),
                        ]),
                ]);
        });

        $this->visible(fn (Model $record): bool => $record->batch_uuid !== null);
    }
}
