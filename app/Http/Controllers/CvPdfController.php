<?php

namespace App\Http\Controllers;

use App\Services\CvPreviewDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CvPdfController extends Controller
{
    public function download(Request $request, CvPreviewDataService $previewDataService)
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

        $missing = $this->missingRequirements($profile);

        if ($missing) {
            return redirect()
                ->route('cv.edit')
                ->with('error', 'PDF belum bisa dibuat. Lengkapi data wajib berikut: ' . implode(', ', $missing) . '.');
        }

        $pdf = Pdf::loadView('cv.pdf', [
            'profile' => $profile,
            'preview' => $previewDataService->build($profile, $request->user()),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        return $pdf->download($this->filename($profile->full_name));
    }

    private function filename(?string $name): string
    {
        $name = $name ?: 'karyawan';

        return 'CV-' . Str::slug($name) . '.pdf';
    }

    private function missingRequirements($profile): array
    {
        $missing = [];

        if (!$profile->full_name) {
            $missing[] = 'nama lengkap';
        }

        if (!$profile->birth_place) {
            $missing[] = 'tempat lahir';
        }

        if (!$profile->birth_date) {
            $missing[] = 'tanggal lahir';
        }

        if (!$profile->gender) {
            $missing[] = 'jenis kelamin';
        }

        if (!$profile->marital_status) {
            $missing[] = 'status pernikahan';
        }

        if (!$profile->address) {
            $missing[] = 'alamat';
        }

        if (!$profile->phone) {
            $missing[] = 'no. HP';
        }

        if (!$profile->email) {
            $missing[] = 'email';
        }

        if (!$profile->profile_summary) {
            $missing[] = 'ringkasan profil';
        }

        if (!count($profile->technical_skills ?: [])) {
            $missing[] = 'keahlian teknis';
        }

        if (!$profile->experiences->count()) {
            $missing[] = 'pengalaman kerja';
        }

        if (!$profile->educations->count()) {
            $missing[] = 'pendidikan';
        }

        return $missing;
    }
}
