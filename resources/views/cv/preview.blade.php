@extends('layouts.app')

@section('title', 'Preview CV - Vitae')

@section('content')
@include('partials.page-header', [
'eyebrow' => 'Preview CV',
'title' => 'Preview Vitae',
'subtitle' => 'Periksa tampilan akhir CV sebelum masuk fase generate PDF. Data yang tampil berasal dari draft terakhir yang tersimpan.',
'actions' => new \Illuminate\Support\HtmlString(
'<div class="d-grid d-md-flex gap-2">' .
    '<a href="' . route('cv.edit') . '" class="btn btn-outline-secondary"><i class="bi bi-pencil-square me-1"></i> Edit CV</a>' .
    '<a href="' . route('cv.pdf.download') . '" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i> Download PDF</a>' .
    '</div>'
),
])

<div class="row g-4 align-items-start">
    <div class="col-xl-9">
        @include('cv.templates.hris', ['profile' => $profile, 'preview' => $preview])
    </div>

    <div class="col-xl-3">
        <div class="app-card sticky-xl-top cv-side-panel">
            <div class="app-card-body">
                <h2 class="h5 fw-bold mb-2">Catatan Preview</h2>
                <p class="text-muted">Section opsional hanya muncul jika memiliki data. Pendidikan dibatasi maksimal 2 data dengan prioritas jenjang tertinggi.</p>

                <div class="d-grid gap-2">
                    <a href="{{ route('cv.edit') }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-1"></i> Edit Draft
                    </a>
                    <a href="{{ route('cv.pdf.download') }}" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection