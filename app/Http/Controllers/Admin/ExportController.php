<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExportHistory;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function download(ExportHistory $history): StreamedResponse
    {
        abort_unless(Storage::disk('exports')->exists($history->file), 404);

        return Storage::disk('exports')->download($history->file);
    }
}
