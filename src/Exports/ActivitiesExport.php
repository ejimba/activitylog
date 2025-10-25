<?php

namespace Rmsramos\Activitylog\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivitiesExport implements WithMultipleSheets
{
    public function __construct(
        protected Builder $query,
        protected array $options = []
    ) {}

    public function sheets(): array
    {
        $sheets = [
            new ActivitiesSheet($this->query, $this->options),
        ];

        if ($this->options['include_summary'] ?? config('filament-activitylog.export.excel.include_summary', true)) {
            $sheets[] = new SummarySheet($this->query);
        }

        return $sheets;
    }
}

class ActivitiesSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Builder $query,
        protected array $options = []
    ) {}

    public function query()
    {
        return $this->query->with(['causer', 'subject']);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Event',
            'Subject Type',
            'Subject ID',
            'Subject Title',
            'Causer',
            'Description',
            'Created At',
            'Updated At',
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->id,
            ucfirst(str_replace('_', ' ', $activity->event)),
            class_basename($activity->subject_type),
            $activity->subject_id,
            $this->getSubjectTitle($activity),
            $activity->causer?->name ?? 'System',
            $activity->description,
            $activity->created_at->toDateTimeString(),
            $activity->updated_at->toDateTimeString(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Activities';
    }

    protected function getSubjectTitle($activity): string
    {
        if (! $activity->subject) {
            return 'N/A';
        }

        $subject = $activity->subject;

        foreach (['title', 'name', 'label', 'email'] as $attribute) {
            if (isset($subject->{$attribute})) {
                return $subject->{$attribute};
            }
        }

        return class_basename($activity->subject_type) . ' #' . $activity->subject_id;
    }
}

class SummarySheet implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Builder $query
    ) {}

    public function query()
    {
        return (clone $this->query)
            ->selectRaw('event, count(*) as count')
            ->groupBy('event')
            ->orderByDesc('count');
    }

    public function headings(): array
    {
        return [
            'Event',
            'Count',
            'Percentage',
        ];
    }

    public function map($row): array
    {
        $total      = (clone $this->query)->count();
        $percentage = $total > 0 ? round(($row->count / $total) * 100, 2) : 0;

        return [
            ucfirst(str_replace('_', ' ', $row->event)),
            $row->count,
            $percentage . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
