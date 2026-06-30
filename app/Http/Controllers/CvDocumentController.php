<?php

namespace App\Http\Controllers;

use App\Models\CvDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CvDocumentController extends Controller
{
    public function download(Request $request, CvDocument $document)
    {
        $document->loadMissing('cvProfile');

        if (!$document->cvProfile || (int) $document->cvProfile->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($document->file_path, $document->original_name, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

