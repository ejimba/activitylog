<?php

namespace Rmsramos\Activitylog\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadExportController
{
    public function __invoke(string $file): StreamedResponse
    {
        $fileName = basename($file);
        $disk     = config('filament-activitylog.export.disk', 'local');
        $path     = 'exports/' . $fileName;

        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download($path);
    }
}
