<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CvPhotoController extends Controller
{
    public function show(Request $request)
    {
        $profile = $request->user()->cvProfile;

        if (!$profile || !$profile->photo_path || !Storage::disk('local')->exists($profile->photo_path)) {
            abort(404);
        }

        return response()->file(storage_path('app/' . $profile->photo_path), [
            'Content-Type' => Storage::disk('local')->mimeType($profile->photo_path) ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
