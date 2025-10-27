<?php

namespace Rmsramos\Activitylog\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;
use Rmsramos\Activitylog\Services\ActivityExportService;

class ExportActivitiesAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'export_activities';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-activitylog::action.export'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->form([

                DatePicker::make('date_from')
                    ->label('From Date')
                    ->nullable(),

                DatePicker::make('date_to')
                    ->label('To Date')
                    ->nullable(),

                Select::make('event')
                    ->label('Event Type')
                    ->options(function () {
                        return ActivityLogHelper::activityQuery()
                            ->distinct()
                            ->pluck('event', 'event')
                            ->mapWithKeys(fn ($event) => [$event => ucfirst(str_replace('_', ' ', $event))])
                            ->toArray();
                    })
                    ->searchable()
                    ->nullable(),
            ])
            ->action(function (array $data) {
                $exportService = app(ActivityExportService::class);

                // Build query
                $query = ActivityLogHelper::activityQuery()->with(['causer', 'subject']);

                // Apply filters
                if (! empty($data['date_from'])) {
                    $query->where('created_at', '>=', $data['date_from']);
                }

                if (! empty($data['date_to'])) {
                    $query->where('created_at', '<=', $data['date_to']);
                }

                if (! empty($data['event'])) {
                    $query->where('event', $data['event']);
                }

                // Check record limit
                $count      = $query->count();
                $maxRecords = config('filament-activitylog.export.limits.max_records', 10000);

                if ($count > $maxRecords) {
                    Notification::make()
                        ->title('Export limit exceeded')
                        ->body("Cannot export more than {$maxRecords} records. Please narrow your filters. Current: {$count} records.")
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $filePath = $exportService->exportToExcel($query);

                    // Get download URL
                    $fileName    = basename($filePath);
                    $downloadUrl = null;
                    $routeName   = config('filament-activitylog.export.download_route');

                    if ($routeName === null) {
                        $routeName = 'filament-activitylog.export.download';
                    }

                    if ($routeName && app('router')->has($routeName)) {
                        $downloadUrl = route($routeName, ['file' => $fileName]);
                    }

                    $body = $downloadUrl
                        ? new HtmlString("Successfully exported {$count} activities. <a href=\"{$downloadUrl}\" target=\"_blank\" class=\"fi-notification-link\">Download file</a>")
                        : "Successfully exported {$count} activities. File saved as {$fileName}.";

                    Notification::make()
                        ->title('Export completed')
                        ->body($body)
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Export failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
