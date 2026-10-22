<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Dokumen nasabah disimpan di penyimpanan privat dan hanya dapat diakses pihak berwenang. */
class DocumentController extends Controller
{
    public function show(Request $request, ApplicationDocument $document)
    {
        Gate::authorize('view-application', $document->application);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        if ($request->user()->isStaff()) {
            AuditLog::record('document.view', $document->application, 'Membuka dokumen '.$document->original_name);
        }

        return Storage::disk('local')->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime ?? 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function surveyPhoto(Request $request, LoanApplication $application, int $index)
    {
        Gate::authorize('view-application', $application);
        abort_unless($request->user()->isStaff(), 403);
        $path = $application->survey?->photos[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
