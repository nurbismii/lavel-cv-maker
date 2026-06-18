<?php

namespace App\Http\Controllers;

use App\Services\CvPreviewDataService;
use Illuminate\Http\Request;

class CvPreviewController extends Controller
{
    public function show(Request $request, CvPreviewDataService $previewDataService)
    {
        $profile = $request->user()
            ->cvProfile()
            ->with([
                'experiences',
                'educations',
                'certifications',
                'languages',
                'projects',
                'organizations',
            ])
            ->first();

        if (!$profile) {
            return redirect()
                ->route('cv.edit')
                ->with('error', 'Draft CV belum tersedia. Lengkapi data CV terlebih dahulu.');
        }

        return view('cv.preview', [
            'profile' => $profile,
            'preview' => $previewDataService->build($profile, $request->user()),
        ]);
    }
}
