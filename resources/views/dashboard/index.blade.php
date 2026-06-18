@extends('layouts.app')

@section('title', 'Dashboard - Vitae')

@section('content')
@include('partials.page-header', [
'eyebrow' => 'Dashboard Karyawan',
'title' => 'Vitae',
'subtitle' => 'Kelola data CV, lanjutkan draft, dan generate PDF setelah data wajib lengkap.',
])

<div class="row g-4">
    <div class="col-lg-8">
        <div class="app-card h-100">
            <div class="app-card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <span class="badge badge-vpeople mb-2">
                            <i class="bi bi-database-check me-1"></i> Terhubung V-People
                        </span>
                        <h2 class="h4 fw-bold mb-1">{{ optional($profile)->full_name ?? $user->name }}</h2>
                        <p class="text-muted mb-0">
                            {{ optional($profile)->position ?: 'Jabatan belum tersedia' }}
                            @if (optional($profile)->department)
                            <span class="mx-1">-</span>{{ optional($profile)->department }}
                            @endif
                        </p>
                    </div>

                    <div class="text-md-end">
                        <span class="badge bg-secondary">{{ strtoupper(optional($profile)->status ?? 'draft') }}</span>
                        <div class="small text-muted mt-2">
                            Sinkron terakhir:
                            {{ optional($user->vpeople_last_synced_at)->format('d/m/Y H:i') ?: '-' }}
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control readonly-field" value="{{ optional($profile)->email ?? $user->email }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. HP</label>
                        <input type="text" class="form-control readonly-field" value="{{ optional($profile)->phone ?: '-' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Divisi</label>
                        <input type="text" class="form-control readonly-field" value="{{ optional($profile)->division ?: '-' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Area Kerja</label>
                        <input type="text" class="form-control readonly-field" value="{{ optional($profile)->work_area ?: '-' }}" readonly>
                    </div>
                </div>

                <div class="d-grid d-md-flex gap-2 mt-4">
                    <a href="{{ route('cv.edit') }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-1"></i> Lengkapi CV
                    </a>
                    <a href="{{ route('cv.preview') }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> Preview CV
                    </a>
                    <a href="{{ route('cv.pdf.download') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-body">
                <h3 class="h5 fw-bold mb-2">Progress CV</h3>
                <p class="text-muted">Draft awal sudah dibuat dari data V-People. Data yang belum tersedia seperti tempat lahir dan detail pengalaman akan diisi pada form CV.</p>

                <div class="progress cv-progress mb-3">
                    <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('cv.edit') }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-1"></i> Lanjut Isi Draft
                    </a>
                    <a href="{{ route('cv.preview') }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> Lihat Preview
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection