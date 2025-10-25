<?php

namespace Rmsramos\Activitylog\RelationManagers;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Actions\ViewBatchTableAction;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Resources\Activitylogs\ActivitylogResource;
use Rmsramos\Activitylog\Tables\Columns\BatchColumn;

class ActivitylogRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $recordTitleAttribute = 'description';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament-activitylog::relation_manager.title');
    }

    protected static string|BackedEnum|null $icon = null;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->recordUrl(fn (Model $record): string => ActivitylogResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label(__('filament-activitylog::tables.columns.log_name'))
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('event')
                    ->label(__('filament-activitylog::tables.columns.event'))
                    ->badge()
                    ->color(fn (string $state): string => ActivityLogHelper::getEventColor($state))
                    ->formatStateUsing(fn (string $state): string => ActivityLogHelper::getEventLabel($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label(__('filament-activitylog::tables.columns.causer'))
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('filament-activitylog::tables.columns.description'))
                    ->limit(50)
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                BatchColumn::make('batch_uuid')
                    ->label(__('filament-activitylog::tables.columns.batch'))
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-activitylog::tables.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn (Model $record): string => $record->created_at->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label(__('filament-activitylog::filters.event'))
                    ->options(function () {
                        return $this->getOwnerRecord()
                            ->activities()
                            ->distinct()
                            ->pluck('event', 'event')
                            ->mapWithKeys(fn ($event) => [
                                $event => ActivityLogHelper::getEventLabel($event),
                            ])
                            ->toArray();
                    })
                    ->multiple(),

                Tables\Filters\SelectFilter::make('causer_id')
                    ->label(__('filament-activitylog::filters.causer'))
                    ->options(function () {
                        $userModel = ActivityLogHelper::getUserModelClass();

                        if (! $userModel) {
                            return [];
                        }

                        $activityTable = ActivityLogHelper::makeActivityModel()->getTable();

                        return $userModel::query()
                            ->whereIn('id', function ($query) use ($activityTable) {
                                $query->select('causer_id')
                                    ->from($activityTable)
                                    ->whereNotNull('causer_id')
                                    ->distinct();
                            })
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('filament-activitylog::filters.date_from')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('filament-activitylog::filters.date_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewBatchTableAction::make()
                    ->visible(fn (Model $record): bool => $record->batch_uuid !== null &&
                        config('filament-activitylog.relation_manager.show_batch_action', true)
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => config('filament-activitylog.relation_manager.allow_bulk_delete', false)),
                ]),
            ])
            ->emptyStateHeading(__('filament-activitylog::relation_manager.empty_state.heading'))
            ->emptyStateDescription(__('filament-activitylog::relation_manager.empty_state.description'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! config('filament-activitylog.relation_manager.enabled', true)) {
            return false;
        }

        // Check if the model uses LogsActivity trait
        return ActivityLogHelper::classUsesTrait($ownerRecord, \Spatie\Activitylog\Traits\LogsActivity::class);
    }
}
