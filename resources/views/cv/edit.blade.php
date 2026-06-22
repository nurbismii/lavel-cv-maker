@extends('layouts.app')

@section('title', 'Form CV - Vitae')

@push('styles')
<link href="{{ asset('vendor/cropperjs/cropper.min.css') }}?v={{ filemtime(public_path('vendor/cropperjs/cropper.min.css')) }}" rel="stylesheet">
@endpush

@php
$skillText = old('technical_skills', implode(', ', $profile->technical_skills ?: []));
$softSkillText = old('non_technical_skills', implode(', ', $profile->non_technical_skills ?: []));

$experiences = old('experiences', $profile->experiences->map(function ($item) {
return [
'position' => $item->position,
'company' => $item->company,
'department' => $item->department,
'division' => $item->division,
'start_month' => optional($item->start_month)->format('Y-m'),
'end_month' => optional($item->end_month)->format('Y-m'),
'is_current' => $item->is_current ? 1 : 0,
'responsibilities' => \App\Support\CvResponsibilityRichText::toTextareaText($item->responsibilities ?: []),
];
})->toArray());

$educations = old('educations', $profile->educations->map(function ($item) {
return [
'level' => $item->level,
'institution' => $item->institution,
'major' => $item->major,
'graduation_year' => $item->graduation_year,
];
})->toArray());

$certifications = old('certifications', $profile->certifications->map(function ($item) {
return [
'name' => $item->name,
'issuer' => $item->issuer,
'year' => $item->year,
'valid_until_year' => $item->valid_until_year,
'is_lifetime' => $item->is_lifetime ? 1 : 0,
'type' => $item->type,
];
})->toArray());

$languages = old('languages', $profile->languages->map(function ($item) {
return [
'language' => $item->language,
'level' => $item->level,
];
})->toArray());

$projects = old('projects', $profile->projects->map(function ($item) {
return [
'name' => $item->name,
'year' => $item->year,
];
})->toArray());

$organizations = old('organizations', $profile->organizations->map(function ($item) {
return [
'organization_name' => $item->organization_name,
'role' => $item->role,
'start_year' => $item->start_year,
'end_year' => $item->end_year,
];
})->toArray());

$experiences = count($experiences) ? $experiences : [[]];
$educations = count($educations) ? $educations : [[]];
$certifications = count($certifications) ? $certifications : [[]];
$languages = count($languages) ? $languages : [[]];
$projects = count($projects) ? $projects : [[]];
$organizations = count($organizations) ? $organizations : [[]];

$educationLevels = ['SD', 'SMP', 'SMA', 'SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];
$languageLevels = ['Native', 'Lancar', 'Percakapan', 'Dasar', 'Pasif'];
$currentYear = (int) date('Y');
$yearOptions = range($currentYear + 1, 1900);
$validUntilYearOptions = range($currentYear + 30, 1900);
$locationOptions = $locationOptions ?? ['provinces' => [], 'regencies' => [], 'districts' => [], 'villages' => []];
$selectedProvinceId = old('province_id', $profile->province_id);
$selectedRegencyId = old('regency_id', $profile->regency_id);
$selectedDistrictId = old('district_id', $profile->district_id);
$selectedVillageId = old('village_id', $profile->village_id);
$selectedGender = old('gender', $profile->gender);

if (!in_array($selectedGender, ['L', 'P'])) {
$genderText = strtolower((string) $selectedGender);
$selectedGender = strpos($genderText, 'perempuan') !== false ? 'P' : (strpos($genderText, 'laki') !== false ? 'L' : null);
}

$photoUrl = $profile->photo_path ? route('cv.photo.show') . '?v=' . optional($profile->updated_at)->timestamp : null;
@endphp

@section('content')
@include('partials.page-header', [
'eyebrow' => 'Draft CV',
'title' => 'Lengkapi Data CV',
'subtitle' => 'Simpan draft kapan saja. Lengkapi data utama untuk bisa generate PDF.',
'actions' => new \Illuminate\Support\HtmlString('<a href="' . route('dashboard') . '" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>'),
])

@if ($errors->any())
<div class="alert alert-danger app-alert">
    <strong>Gagal menyimpan draft.</strong>
    <div>Periksa kembali field yang ditandai merah, lalu coba simpan lagi.</div>
</div>
@endif

@if (!empty($locationMasterError))
<div class="alert alert-warning app-alert">
    <strong>Master wilayah belum tersedia.</strong>
    <div>{{ $locationMasterError }}</div>
</div>
@endif

<div class="row g-4 align-items-start">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('cv.draft.save') }}" id="cvForm" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="cv-wizard" data-cv-wizard data-initial-step="{{ request('step') }}">
                <div class="app-card cv-wizard-header mb-4">
                    <div class="app-card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-start mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1" data-wizard-active-title>Data Pribadi</h2>
                                <p class="text-muted mb-0">Isi bertahap agar data CV lebih mudah dicek sebelum disimpan.</p>
                            </div>
                            <span class="badge badge-vpeople cv-wizard-counter">
                                Step <span data-wizard-current>1</span> dari <span data-wizard-total>7</span>
                            </span>
                        </div>

                        <div class="cv-wizard-progress mb-3" aria-hidden="true">
                            <div data-wizard-progress></div>
                        </div>

                        <div class="cv-wizard-steps" role="tablist" aria-label="Tahapan pengisian CV">
                            <button type="button" class="cv-wizard-step is-active" data-wizard-step-target="personal">
                                <span class="cv-wizard-step-number">1</span>
                                <span class="cv-wizard-step-text">
                                    <span class="cv-wizard-step-title">Data Pribadi</span>
                                    <span class="cv-wizard-step-subtitle">Identitas & kontak</span>
                                </span>
                            </button>
                            <button type="button" class="cv-wizard-step" data-wizard-step-target="experience">
                                <span class="cv-wizard-step-number">2</span>
                                <span class="cv-wizard-step-text">
                                    <span class="cv-wizard-step-title">Pengalaman</span>
                                    <span class="cv-wizard-step-subtitle">Riwayat kerja</span>
                                </span>
                            </button>
                            <button type="button" class="cv-wizard-step" data-wizard-step-target="education">
                                <span class="cv-wizard-step-number">3</span>
                                <span class="cv-wizard-step-text">
                                    <span class="cv-wizard-step-title">Pendidikan</span>
                                    <span class="cv-wizard-step-subtitle">Riwayat akademik</span>
                                </span>
                            </button>
                            <button type="button" class="cv-wizard-step" data-wizard-step-target="skills">
                                <span class="cv-wizard-step-number">4</span>
                                <span class="cv-wizard-step-text">
                                    <span class="cv-wizard-step-title">Keahlian</span>
                                    <span class="cv-wizard-step-subtitle">Teknis & non-teknis</span>
                                </span>
                            </button>
                            <button type="button" class="cv-wizard-step" data-wizard-step-target="certifications">
                                <span class="cv-wizard-step-number">5</span>
                                <span class="cv-wizard-step-text">
                                    <span class="cv-wizard-step-title">Sertifikasi</span>
                                    <span class="cv-wizard-step-subtitle">Pelatihan</span>
                                </span>
                            </button>
                            <button type="button" class="cv-wizard-step" data-wizard-step-target="extras">
                                <span class="cv-wizard-step-number">6</span>
                                <span class="cv-wizard-step-text">
                                    <span class="cv-wizard-step-title">Tambahan</span>
                                    <span class="cv-wizard-step-subtitle">Bahasa & proyek</span>
                                </span>
                            </button>
                            <button type="button" class="cv-wizard-step" data-wizard-step-target="summary">
                                <span class="cv-wizard-step-number">7</span>
                                <span class="cv-wizard-step-text">
                                    <span class="cv-wizard-step-title">Ringkasan</span>
                                    <span class="cv-wizard-step-subtitle">Finalisasi profil</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="app-card cv-wizard-panel mb-4" data-wizard-panel="personal" data-wizard-title="Data Pribadi">
                    <div class="app-card-header">
                        <h2 class="app-card-title h5">Data Pribadi</h2>
                        <p class="app-card-subtitle">Lengkapi data pribadi Anda</p>
                    </div>
                    <div class="app-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <span class="badge badge-vpeople ms-1">V-People</span>
                                <input type="text" class="form-control readonly-field" value="{{ $profile->full_name }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK</label>
                                <span class="badge badge-vpeople ms-1">V-People</span>
                                <input type="text" class="form-control readonly-field" value="{{ $vpeopleNik ?: 'Tersimpan aman' }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Foto CV</label>
                                <div class="cv-photo-uploader">
                                    <div class="cv-photo-frame {{ $photoUrl ? 'has-photo' : 'is-empty' }}" data-photo-frame data-photo-original="{{ $photoUrl }}">
                                        @if ($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="Foto CV" data-photo-preview>
                                        @else
                                        <div class="cv-photo-placeholder" data-photo-placeholder>
                                            <i class="bi bi-plus-lg"></i>
                                            <span>Foto kosong</span>
                                        </div>
                                        <img src="" alt="Preview foto CV" class="d-none" data-photo-preview>
                                        @endif
                                    </div>
                                    <div class="cv-photo-actions">
                                        <input type="file" name="photo" id="photoInput" class="form-control @error('photo') is-invalid @enderror" accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-photo-input>
                                        <div class="form-text">Opsional. Format JPG/PNG, maksimal 2MB. Upload ulang untuk mengganti foto.</div>
                                        @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                                        @if ($profile->photo_path)
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" id="removePhoto" name="remove_photo" value="1" data-photo-remove>
                                            <label class="form-check-label" for="removePhoto">Hapus foto saat menyimpan</label>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" name="birth_place" class="form-control @error('birth_place') is-invalid @enderror" value="{{ old('birth_place', $profile->birth_place) }}" placeholder="Contoh: Kendari">
                                @error('birth_place') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <span class="badge badge-vpeople ms-1">V-People</span>
                                <input type="text" class="form-control readonly-field" value="{{ optional($profile->birth_date)->format('d/m/Y') ?: '-' }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="">Pilih jenis kelamin</option>
                                    <option value="L" {{ $selectedGender === 'L' ? 'selected' : '' }}>Laki-Laki</option>
                                    <option value="P" {{ $selectedGender === 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Pernikahan</label>
                                <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                                    @foreach (['Belum', 'Menikah', 'Cerai'] as $status)
                                    <option value="{{ $status }}" {{ old('marital_status', $profile->marital_status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                    @if ($profile->marital_status && !in_array($profile->marital_status, ['Belum', 'Menikah', 'Cerai']))
                                    <option value="{{ $profile->marital_status }}" selected>{{ $profile->marital_status }}</option>
                                    @endif
                                </select>
                                @error('marital_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. HP</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $profile->phone) }}" placeholder="08xxxxxxxxxx">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control readonly-field @error('email') is-invalid @enderror" value="{{ old('email', $profile->email) }}" readonly>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Provinsi</label>
                                <select id="provinceSelect" name="province_id" class="form-select @error('province_id') is-invalid @enderror" data-location-child="#regencySelect">
                                    <option value="">Pilih provinsi</option>
                                    @foreach ($locationOptions['provinces'] as $province)
                                    <option value="{{ $province['id'] }}" {{ (string) $selectedProvinceId === (string) $province['id'] ? 'selected' : '' }}>
                                        {{ $province['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('province_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kabupaten/Kota</label>
                                <select id="regencySelect" name="regency_id" class="form-select @error('regency_id') is-invalid @enderror" data-location-parent="#provinceSelect" data-location-param="province_id" data-location-url="{{ route('cv.locations.regencies') }}" data-location-child="#districtSelect" data-location-placeholder="Pilih kabupaten/kota" {{ $selectedProvinceId ? '' : 'disabled' }}>
                                    <option value="">Pilih kabupaten/kota</option>
                                    @foreach ($locationOptions['regencies'] as $regency)
                                    <option value="{{ $regency['id'] }}" {{ (string) $selectedRegencyId === (string) $regency['id'] ? 'selected' : '' }}>
                                        {{ $regency['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('regency_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kecamatan</label>
                                <select id="districtSelect" name="district_id" class="form-select @error('district_id') is-invalid @enderror" data-location-parent="#regencySelect" data-location-param="regency_id" data-location-url="{{ route('cv.locations.districts') }}" data-location-child="#villageSelect" data-location-placeholder="Pilih kecamatan" {{ $selectedRegencyId ? '' : 'disabled' }}>
                                    <option value="">Pilih kecamatan</option>
                                    @foreach ($locationOptions['districts'] as $district)
                                    <option value="{{ $district['id'] }}" {{ (string) $selectedDistrictId === (string) $district['id'] ? 'selected' : '' }}>
                                        {{ $district['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kelurahan/Desa</label>
                                <select id="villageSelect" name="village_id" class="form-select @error('village_id') is-invalid @enderror" data-location-parent="#districtSelect" data-location-param="district_id" data-location-url="{{ route('cv.locations.villages') }}" data-location-placeholder="Pilih kelurahan/desa" {{ $selectedDistrictId ? '' : 'disabled' }}>
                                    <option value="">Pilih kelurahan/desa</option>
                                    @foreach ($locationOptions['villages'] as $village)
                                    <option value="{{ $village['id'] }}" {{ (string) $selectedVillageId === (string) $village['id'] ? 'selected' : '' }}>
                                        {{ $village['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('village_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror" placeholder="Alamat domisili lengkap">{{ old('address', $profile->address) }}</textarea>
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Area Kerja</label>
                                <span class="badge badge-vpeople ms-1">V-People</span>
                                <input type="text" class="form-control readonly-field" value="{{ $profile->work_area ?: '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Departemen</label>
                                <span class="badge badge-vpeople ms-1">V-People</span>
                                <input type="text" class="form-control readonly-field" value="{{ $profile->department ?: '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Divisi</label>
                                <span class="badge badge-vpeople ms-1">V-People</span>
                                <input type="text" class="form-control readonly-field" value="{{ $profile->division ?: '-' }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Jabatan/Posisi</label>
                                <input type="text" name="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $profile->position) }}" placeholder="Contoh: Mechanical Technician">
                                @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-card cv-wizard-panel mb-4" data-wizard-panel="experience" data-wizard-title="Pengalaman Kerja">
                    <div class="app-card-header">
                        <h2 class="app-card-title h5">Pengalaman Kerja</h2>
                        <p class="app-card-subtitle">Tulis tanggung jawab utama per pengalaman kerja.</p>
                    </div>
                    <div class="app-card-body">
                        <div data-repeat-list="experiences">
                            @foreach ($experiences as $index => $item)
                            @include('cv.partials.experience-row', ['index' => $index, 'item' => $item, 'profile' => $profile])
                            @endforeach
                        </div>
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-repeat-add="experiences">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                <div class="app-card cv-wizard-panel mb-4" data-wizard-panel="education" data-wizard-title="Pendidikan">
                    <div class="app-card-header">
                        <h2 class="app-card-title h5">Pendidikan</h2>
                        <p class="app-card-subtitle">Data pendidikan dari V-People dapat disesuaikan jika belum lengkap.</p>
                    </div>
                    <div class="app-card-body">
                        <div data-repeat-list="educations">
                            @foreach ($educations as $index => $item)
                            @include('cv.partials.education-row', ['index' => $index, 'item' => $item, 'educationLevels' => $educationLevels, 'yearOptions' => $yearOptions])
                            @endforeach
                        </div>
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-repeat-add="educations">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                <div class="app-card cv-wizard-panel mb-4" data-wizard-panel="skills" data-wizard-title="Keahlian">
                    <div class="app-card-header">
                        <h2 class="app-card-title h5">Keahlian</h2>
                        <p class="app-card-subtitle">Pisahkan keahlian dengan koma atau baris baru.</p>
                    </div>
                    <div class="app-card-body">
                        <div class="mb-3">
                            <label class="form-label">Keahlian Teknis</label>
                            <textarea name="technical_skills" rows="3" class="form-control @error('technical_skills') is-invalid @enderror" placeholder="Welding, SAP, Microsoft Excel, AutoCAD">{{ $skillText }}</textarea>
                            @error('technical_skills') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="form-label">Keahlian Non-teknis</label>
                            <textarea name="non_technical_skills" rows="3" class="form-control @error('non_technical_skills') is-invalid @enderror" placeholder="Kepemimpinan, Komunikasi, Problem Solving">{{ $softSkillText }}</textarea>
                            @error('non_technical_skills') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="app-card cv-wizard-panel mb-4" data-wizard-panel="certifications" data-wizard-title="Sertifikasi & Pelatihan">
                    <div class="app-card-header">
                        <h2 class="app-card-title h5">Sertifikasi & Pelatihan</h2>
                        <p class="app-card-subtitle">Opsional, tetapi membantu memperkuat kualitas CV.</p>
                    </div>
                    <div class="app-card-body">
                        <div data-repeat-list="certifications">
                            @foreach ($certifications as $index => $item)
                            @include('cv.partials.certification-row', ['index' => $index, 'item' => $item, 'yearOptions' => $yearOptions, 'validUntilYearOptions' => $validUntilYearOptions])
                            @endforeach
                        </div>
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-repeat-add="certifications">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>

                <div class="app-card cv-wizard-panel mb-4" data-wizard-panel="extras" data-wizard-title="Tambahan Opsional">
                    <div class="app-card-header">
                        <h2 class="app-card-title h5">Tambahan Opsional</h2>
                        <p class="app-card-subtitle">Bahasa, proyek, dan organisasi hanya tampil di CV jika diisi.</p>
                    </div>
                    <div class="app-card-body">
                        <div class="repeat-block">
                            <h3 class="h6 fw-bold mb-3">Bahasa</h3>
                            <div data-repeat-list="languages">
                                @foreach ($languages as $index => $item)
                                @include('cv.partials.language-row', ['index' => $index, 'item' => $item, 'languageLevels' => $languageLevels])
                                @endforeach
                            </div>
                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-repeat-add="languages">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah
                                </button>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="repeat-block">
                            <h3 class="h6 fw-bold mb-3">Proyek</h3>
                            <div data-repeat-list="projects">
                                @foreach ($projects as $index => $item)
                                @include('cv.partials.project-row', ['index' => $index, 'item' => $item, 'yearOptions' => $yearOptions])
                                @endforeach
                            </div>
                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-repeat-add="projects">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah
                                </button>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="repeat-block">
                            <h3 class="h6 fw-bold mb-3">Organisasi</h3>
                            <div data-repeat-list="organizations">
                                @foreach ($organizations as $index => $item)
                                @include('cv.partials.organization-row', ['index' => $index, 'item' => $item, 'yearOptions' => $yearOptions])
                                @endforeach
                            </div>
                            <div class="mt-3 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-repeat-add="organizations">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="app-card cv-wizard-panel mb-4" data-wizard-panel="summary" data-wizard-title="Ringkasan Profil">
                    <div class="app-card-header">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-start">
                            <div>
                                <h2 class="app-card-title h5">Ringkasan Profil</h2>
                                <p class="app-card-subtitle">Maksimal 300 karakter. Generate setelah data utama diisi agar hasilnya lebih relevan.</p>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm" formaction="{{ route('cv.summary.generate') }}" data-loading-text="Membuat ringkasan...">
                                <i class="bi bi-stars me-1"></i> Generate
                            </button>
                        </div>
                    </div>
                    <div class="app-card-body">
                        <textarea name="profile_summary" rows="4" maxlength="300" class="form-control @error('profile_summary') is-invalid @enderror js-countable" data-counter="#summaryCounter" placeholder="Contoh: Teknisi mekanik dengan pengalaman 5 tahun di industri smelter nikel...">{{ old('profile_summary', $profile->profile_summary) }}</textarea>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Tulis ringkas, spesifik, dan profesional.</small>
                            <small class="text-muted"><span id="summaryCounter">0</span>/300</small>
                        </div>
                        @error('profile_summary') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="app-savebar">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <div class="cv-wizard-action-group">
                    <button type="button" class="btn btn-outline-secondary" data-wizard-prev>
                        <i class="bi bi-chevron-left me-1"></i> Sebelumnya
                    </button>
                    <button type="button" class="btn btn-primary" data-wizard-next>
                        Berikutnya <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                    <button type="submit" class="btn btn-outline-primary" formaction="{{ route('cv.preview.save') }}" data-loading-text="Menyimpan dan membuka preview...">
                        <i class="bi bi-eye me-1"></i> Simpan & Preview
                    </button>
                    <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan draft...">
                        <i class="bi bi-save me-1"></i> Simpan Draft
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="app-card sticky-lg-top cv-side-panel">
            <div class="app-card-body">
                <h2 class="h5 fw-bold mb-2">Kelengkapan CV</h2>
                <p class="text-muted">Progress dihitung dari field utama yang diperlukan sebelum generate PDF.</p>
                <div class="progress cv-progress mb-3">
                    <div class="progress-bar" role="progressbar" style="width: {{ $completion }}%;" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-muted">Progress</span>
                    <strong>{{ $completion }}%</strong>
                </div>

                <div class="cv-checklist">
                    <div class="cv-check {{ $profile->birth_place ? 'is-done' : '' }}"><i class="bi bi-check-circle"></i> Tempat lahir</div>
                    <div class="cv-check {{ $profile->profile_summary ? 'is-done' : '' }}"><i class="bi bi-check-circle"></i> Ringkasan profil</div>
                    <div class="cv-check {{ count($profile->technical_skills ?: []) ? 'is-done' : '' }}"><i class="bi bi-check-circle"></i> Keahlian teknis</div>
                    <div class="cv-check {{ $profile->experiences()->exists() ? 'is-done' : '' }}"><i class="bi bi-check-circle"></i> Pengalaman kerja</div>
                    <div class="cv-check {{ $profile->educations()->exists() ? 'is-done' : '' }}"><i class="bi bi-check-circle"></i> Pendidikan</div>
                </div>

                <div class="alert alert-warning mt-4 mb-0">
                    <strong>Preview dan PDF</strong>
                    <div>PDF hanya bisa dibuat setelah field wajib lengkap.</div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" form="cvForm" class="btn btn-outline-primary" formaction="{{ route('cv.preview.save') }}" data-loading-text="Menyimpan dan membuka preview...">
                        <i class="bi bi-eye me-1"></i> Simpan & Preview
                    </button>
                    <a href="{{ route('cv.pdf.download') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@include('cv.partials.templates', [
'profile' => $profile,
'educationLevels' => $educationLevels,
'languageLevels' => $languageLevels,
'yearOptions' => $yearOptions,
'validUntilYearOptions' => $validUntilYearOptions,
])

<div class="modal fade" id="photoCropModal" tabindex="-1" aria-labelledby="photoCropModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="photoCropModalLabel">Crop Foto CV</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="cv-photo-crop-area">
                    <img src="" alt="Crop foto CV" data-photo-crop-image>
                </div>
                <div class="cv-photo-crop-toolbar">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-photo-crop-action="zoom-in">
                        <i class="bi bi-zoom-in me-1"></i> Perbesar
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-photo-crop-action="zoom-out">
                        <i class="bi bi-zoom-out me-1"></i> Perkecil
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-photo-crop-action="rotate-left">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Putar Kiri
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-photo-crop-action="rotate-right">
                        <i class="bi bi-arrow-clockwise me-1"></i> Putar Kanan
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-photo-crop-action="reset">
                        <i class="bi bi-arrow-repeat me-1"></i> Reset
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" data-photo-crop-apply>
                    <i class="bi bi-check2 me-1"></i> Gunakan Foto
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/cropperjs/cropper.min.js') }}?v={{ filemtime(public_path('vendor/cropperjs/cropper.min.js')) }}"></script>
<script src="{{ asset('js/cv-form.js') }}?v={{ filemtime(public_path('js/cv-form.js')) }}"></script>
@endpush
