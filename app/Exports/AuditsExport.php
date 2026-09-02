<?php

namespace App\Exports;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditsExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        return Audit::query()
            ->with('user')
            ->when($this->filters['event'] ?? null, function (Builder $q, $v) {
                if ($v !== 'all') {
                    $q->where('event', $v);
                }
            })
            ->when($this->filters['type'] ?? null, function (Builder $q, $v) {
                if ($v !== 'all') {
                    $q->where('auditable_type', 'ilike', "%{$v}%");
                }
            })
            ->when($this->filters['date_from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')
            ->select(['id', 'event', 'auditable_type', 'auditable_id', 'user_id', 'ip_address', 'user_agent', 'old_values', 'new_values', 'tags', 'created_at']);
    }

    public function headings(): array
    {
        return [__('Waktu'), __('Event'), __('Auditable'), __('Actor'), __('IP'), __('User Agent'), __('Old Values'), __('New Values'), __('Tags')];
    }

    /**
     * @param  Audit  $audit
     */
    public function map($audit): array
    {
        return [
            $audit->created_at?->format('d M Y H:i'),
            $audit->event,
            class_basename($audit->auditable_type ?? '-').' #'.$audit->auditable_id,
            optional($audit->user)->name ?? __('System'),
            $audit->ip_address ?? '-',
            $audit->user_agent ?? '-',
            $this->formatJson($audit->old_values),
            $this->formatJson($audit->new_values),
            $audit->tags ?? '-',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $values
     */
    private function formatJson(?array $values): string
    {
        if (empty($values)) {
            return '-';
        }

        return json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
