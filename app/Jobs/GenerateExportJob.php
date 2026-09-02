<?php

namespace App\Jobs;

use App\Exports\AuditsExport;
use App\Exports\ClientsExport;
use App\Exports\UsersExport;
use App\Models\ExportHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class GenerateExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public int $historyId,
        public string $type,
        public array $filters = [],
    ) {}

    public function handle(): void
    {
        $history = ExportHistory::find($this->historyId);

        if (! $history) {
            return;
        }

        $history->update(['status' => 'processing', 'progress' => 10]);
        Cache::put("export:progress:{$history->id}", 10, 600);

        try {
            $export = match ($this->type) {
                'users' => new UsersExport($this->filters),
                'clients' => new ClientsExport($this->filters),
                'audits' => new AuditsExport($this->filters),
                default => throw new \InvalidArgumentException("Unknown export type: {$this->type}"),
            };

            // Estimate row count for progress
            $history->update(['progress' => 30]);
            Cache::put("export:progress:{$history->id}", 30, 600);

            Excel::store($export, $history->file, 'exports');

            $history->update(['status' => 'completed', 'progress' => 100]);
            Cache::put("export:progress:{$history->id}", 100, 600);
            Cache::forget("export:progress:{$history->id}");
        } catch (\Throwable $e) {
            $history->update(['status' => 'failed', 'progress' => 0]);
            Cache::forget("export:progress:{$history->id}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $history = ExportHistory::find($this->historyId);

        if ($history) {
            $history->update(['status' => 'failed', 'progress' => 0]);
            Cache::forget("export:progress:{$history->id}");
        }
    }
}
