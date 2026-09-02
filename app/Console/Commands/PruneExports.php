<?php

namespace App\Console\Commands;

use App\Models\ExportHistory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('exports:prune')]
#[Description('Hapus riwayat export & file di disk exports yang lebih dari 7 hari')]
class PruneExports extends Command
{
    public function handle(): int
    {
        $count = 0;

        ExportHistory::where('created_at', '<', now()->subDays(7))
            ->chunkById(100, function ($histories) use (&$count) {
                foreach ($histories as $history) {
                    Storage::disk('exports')->delete($history->file);
                    $history->delete();
                    $count++;
                }
            });

        $this->info("Pruned {$count} export(s) older than 7 days.");

        return self::SUCCESS;
    }
}
