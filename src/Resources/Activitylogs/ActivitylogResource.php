<?php

namespace Rmsramos\Activitylog\Resources\Activitylogs;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Actions\ViewBatchTableAction;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use UnitEnum;

class ActivitylogResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $slug = 'activitylogs';

    protected static ?string $recordTitleAttribute = 'description';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 100;

    protected static function plugin(): ActivitylogPlugin
    {
        return ActivitylogPlugin::get();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return parent::shouldRegisterNavigation() && static::plugin()->getNavigationItem();
    }

    public static function getModel(): string
    {
        return ActivityLogHelper::getActivityModelClass();
    }

    public static function getNavigationLabel(): string
    {
        return static::plugin()->getPluralLabel();
    }

    public static function getModelLabel(): string
    {
        return static::plugin()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return static::plugin()->getPluralLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return static::plugin()->getNavigationGroup() ?? static::$navigationGroup;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return static::plugin()->getNavigationIcon() ?? static::$navigationIcon;
    }

    public static function getNavigationSort(): ?int
    {
        return static::plugin()->getNavigationSort() ?? static::$navigationSort;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::plugin()->getNavigationCountBadge()) {
            return null;
        }

        $activityClass = static::getModel();

        return number_format($activityClass::query()->count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('log_name')
                            ->label(__('filament-activitylog::tables.columns.log_name'))
                            ->disabled(),
                        Forms\Components\TextInput::make('event')
                            ->label(__('filament-activitylog::tables.columns.event'))
                            ->disabled(),
                        Forms\Components\TextInput::make('subject_type')
                            ->label(__('filament-activitylog::tables.columns.subject'))
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),
                        Forms\Components\TextInput::make('subject_id')
                            ->label('Subject ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('causer_type')
                            ->label(__('filament-activitylog::tables.columns.causer'))
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),
                        Forms\Components\TextInput::make('causer_id')
                            ->label('Causer ID')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('filament-activitylog::tables.columns.description'))
                            ->disabled()
                            ->rows(2),
                        Forms\Components\KeyValue::make('properties')
                            ->label(__('filament-activitylog::tables.columns.properties'))
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label(__('filament-activitylog::tables.columns.created_at'))
                            ->displayFormat(static::plugin()->getDatetimeFormat() ?? null)
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label(__('filament-activitylog::tables.columns.log_name'))
                    ->formatStateUsing(fn (?string $state): string => $state ? ucwords($state) : '-')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('event')
                    ->label(__('filament-activitylog::tables.columns.event'))
                    ->badge()
                    ->color(fn (string $state): string => ActivityLogHelper::getEventColor($state))
                    ->formatStateUsing(fn (string $state): string => ActivityLogHelper::getEventLabel($state))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label(__('filament-activitylog::tables.columns.subject') . ' Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label(__('filament-activitylog::tables.columns.subject'))
                    ->formatStateUsing(function (Model $record): string {
                        if (! $record->subject) {
                            return $record->subject_id ? "#{$record->subject_id}" : '-';
                        }

                        $subject     = $record->subject;
                        $subjectType = $record->subject_type;

                        if ($subjectType) {
                            $resourceClass = ActivityLogHelper::getResourceFromModel($subjectType);

                            if ($resourceClass) {
                                return $resourceClass::getRecordTitle($subject);
                            }
                        }

                        return "#{$record->subject_id}";
                    })
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('subject_id', $direction))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label(__('filament-activitylog::tables.columns.causer'))
                    ->default('-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                ViewColumn::make('properties')
                    ->label(__('filament-activitylog::tables.columns.properties'))
                    ->view('filament-activitylog::filament.tables.columns.activity-logs-properties')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('filament-activitylog::tables.columns.description'))
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                static::makeCreatedAtColumn(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label(__('filament-activitylog::filters.log_name'))
                    ->options(function () {
                        $activityClass = ActivityLogHelper::getActivityModelClass();

                        return $activityClass::query()
                            ->distinct()
                            ->pluck('log_name', 'log_name')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('event')
                    ->label(__('filament-activitylog::filters.event'))
                    ->options(function () {
                        $activityClass = ActivityLogHelper::getActivityModelClass();

                        return $activityClass::query()
                            ->distinct()
                            ->pluck('event', 'event')
                            ->mapWithKeys(fn ($event) => [$event => ActivityLogHelper::getEventLabel($event)])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label(__('filament-activitylog::filters.subject_type'))
                    ->options(function () {
                        $activityClass = ActivityLogHelper::getActivityModelClass();

                        return $activityClass::query()
                            ->distinct()
                            ->pluck('subject_type', 'subject_type')
                            ->filter()
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
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
                    ->preload(),
                static::makeCreatedAtFilter(),
            ])
            ->actions([
                ViewBatchTableAction::make()
                    ->visible(fn (Model $record): bool => $record->batch_uuid !== null),
                ViewAction::make(),
            ])
            ->defaultSort(
                config('filament-activitylog.resources.default_sort_column', 'created_at'),
                config('filament-activitylog.resources.default_sort_direction', 'desc'),
            )
            ->paginationPageOptions(config('filament-activitylog.pagination.per_page_options', [10, 25, 50, 100]))
            ->defaultPaginationPageOption(config('filament-activitylog.pagination.default_per_page', 10))
            ->emptyStateHeading(__('filament-activitylog::resource.empty_state.heading'))
            ->emptyStateDescription(__('filament-activitylog::resource.empty_state.description'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list');

        if (config('filament-activitylog.resources.allow_bulk_delete', false)) {
            $table->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
        }

        return $table;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivitylog::route('/'),
            'view'  => Pages\ViewActivitylog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return (bool) config('filament-activitylog.resources.allow_delete', false);
    }

    public static function canDeleteAny(): bool
    {
        return (bool) config('filament-activitylog.resources.allow_bulk_delete', false);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $relations = [];

        if (config('filament-activitylog.performance.eager_load_subject', true)) {
            $relations['subject'] = function ($query): void {
                if (method_exists($query, 'withTrashed')) {
                    $query->withTrashed();
                }
            };
        }

        if (config('filament-activitylog.performance.eager_load_causer', true)) {
            $relations[] = 'causer';
        }

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query;
    }

    protected static function makeCreatedAtColumn(): Tables\Columns\TextColumn
    {
        $column = Tables\Columns\TextColumn::make('created_at')
            ->label(__('filament-activitylog::tables.columns.created_at'))
            ->dateTime(static::plugin()->getDatetimeFormat() ?? null)
            ->sortable()
            ->description(fn (Model $record): string => $record->created_at->format(static::plugin()->getDatetimeFormat() ?? 'M d, Y H:i:s'));

        if (config('filament-activitylog.relative_time', true)) {
            $column->since();
        }

        if ($callback = static::plugin()->getDatetimeColumnCallback()) {
            $column = $callback($column);
        }

        return $column;
    }

    protected static function makeCreatedAtFilter(): Tables\Filters\Filter
    {
        $plugin = static::plugin();

        return Tables\Filters\Filter::make('created_at')
            ->form([
                static::makeDatePicker('created_from', 'date_from'),
                static::makeDatePicker('created_until', 'date_until'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['created_from'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        $data['created_until'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data) use ($plugin): array {
                $indicators = [];
                $parser     = $plugin->getDateParser();

                if ($data['created_from'] ?? null) {
                    $formatted    = $parser($data['created_from'])->format($plugin->getDateFormat() ?? 'Y-m-d');
                    $indicators[] = Tables\Filters\Indicator::make(
                        __('filament-activitylog::filters.date_from') . ': ' . $formatted
                    )->removeField('created_from');
                }

                if ($data['created_until'] ?? null) {
                    $formatted    = $parser($data['created_until'])->format($plugin->getDateFormat() ?? 'Y-m-d');
                    $indicators[] = Tables\Filters\Indicator::make(
                        __('filament-activitylog::filters.date_until') . ': ' . $formatted
                    )->removeField('created_until');
                }

                return $indicators;
            });
    }

    protected static function makeDatePicker(string $field, string $labelKey): Forms\Components\DatePicker
    {
        $picker = Forms\Components\DatePicker::make($field)
            ->label(__("filament-activitylog::filters.{$labelKey}"))
            ->placeholder(__("filament-activitylog::filters.{$labelKey}"));

        if ($format = static::plugin()->getDateFormat()) {
            $picker->displayFormat($format);
        }

        if ($callback = static::plugin()->getDatePickerCallback()) {
            $picker = $callback($picker);
        }

        return $picker;
    }
}
