<?php

namespace Rmsramos\Activitylog\Services;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;

class ActivityExportService
{
    /**
     * Export activities to Excel.
     */
    public function exportToExcel(Builder $query, ?string $fileName = null, array $options = []): string
    {
        $fileName = $fileName ?: 'activities-' . now()->format('Y-m-d-His') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        $disk     = config('filament-activitylog.export.disk', 'local');

        $export = new \Rmsramos\Activitylog\Exports\ActivitiesExport($query, $options);

        Excel::store($export, $filePath, $disk);

        return Storage::disk($disk)->path($filePath);
    }

    /**
     * Generate activity summary report.
     */
    public function generateSummaryReport(array $filters = []): array
    {
        $query = $this->applyFilters(ActivityLogHelper::activityQuery(), $filters);

        $total = (clone $query)->count();

        return [
            'query'            => $query,
            'total_activities' => $total,
            'by_event'         => (clone $query)
                ->selectRaw('event, count(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'by_model' => (clone $query)
                ->selectRaw('subject_type, count(*) as count')
                ->groupBy('subject_type')
                ->pluck('count', 'subject_type')
                ->toArray(),
            'by_user' => (clone $query)
                ->selectRaw('causer_id, count(*) as count')
                ->whereNotNull('causer_id')
                ->groupBy('causer_id')
                ->with('causer:id,name')
                ->get()
                ->mapWithKeys(fn ($item) => [$item->causer?->name ?? 'Unknown' => $item->count])
                ->toArray(),
            'recent_activities' => (clone $query)->latest()->limit(10)->get(),
        ];
    }

    /**
     * Generate audit trail report.
     */
    public function generateAuditTrailReport(array $filters = []): array
    {
        $query = $this->applyFilters(ActivityLogHelper::activityQuery(), $filters);

        $activities = (clone $query)->with(['causer', 'subject'])->latest()->get();

        return [
            'query'            => $query,
            'activities'       => $activities,
            'total_activities' => $activities->count(),
            'total_changes'    => $activities->count(),
            'date_range'       => [
                'from' => $filters['date_from'] ?? $query->min('created_at'),
                'to'   => $filters['date_to'] ?? $query->max('created_at'),
            ],
        ];
    }

    /**
     * Generate user activity report.
     */
    public function generateUserActivityReport(array $filters = []): array
    {
        $query = $this->applyFilters(ActivityLogHelper::activityQuery(), $filters);

        $userActivitiesQuery = (clone $query)
            ->select('causer_type', 'causer_id')
            ->selectRaw('count(*) as activity_count')
            ->whereNotNull('causer_id')
            ->whereNotNull('causer_type')
            ->groupBy('causer_type', 'causer_id')
            ->orderByDesc('activity_count');

        $userActivities = $userActivitiesQuery->get()->load('causer');

        return [
            'query'             => $query,
            'user_activities'   => $userActivities,
            'most_active_users' => $userActivities->take(10)->values(),
            'total_users'       => $userActivities->count(),
            'total_activities'  => (clone $query)->count(),
        ];
    }

    /**
     * Generate model activity report.
     */
    public function generateModelActivityReport(array $filters = []): array
    {
        $query = $this->applyFilters(ActivityLogHelper::activityQuery(), $filters);

        $modelActivities = (clone $query)
            ->selectRaw('subject_type, count(*) as activity_count')
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->orderByDesc('activity_count')
            ->get();

        return [
            'query'                => $query,
            'model_activities'     => $modelActivities,
            'most_modified_models' => $modelActivities->take(10),
            'total_models'         => $modelActivities->count(),
            'total_activities'     => (clone $query)->count(),
        ];
    }

    public function generateReport(string $type, array $filters = []): array
    {
        return match ($type) {
            'summary'        => $this->generateSummaryReport($filters),
            'audit_trail'    => $this->generateAuditTrailReport($filters),
            'user_activity'  => $this->generateUserActivityReport($filters),
            'model_activity' => $this->generateModelActivityReport($filters),
            default          => throw new InvalidArgumentException("Unsupported report type [{$type}]."),
        };
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        return $query;
    }

    /**
     * Clean up old export files.
     */
    public function cleanupOldExports(int $days = 7): int
    {
        $disk    = config('filament-activitylog.export.disk', 'local');
        $storage = Storage::disk($disk);
        $files   = $storage->files('exports');
        $deleted = 0;

        foreach ($files as $file) {
            if ($storage->lastModified($file) < now()->subDays($days)->timestamp) {
                $storage->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

}
