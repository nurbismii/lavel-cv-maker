@php
    $yearOptions = $yearOptions ?? range(((int) date('Y')) + 1, 1900);
    $selectedGraduationYear = (string) ($item['graduation_year'] ?? '');
@endphp

<div class="repeat-item" data-repeat-item>
    <div class="repeat-item-header">
        <strong>Pendidikan</strong>
        <button type="button" class="btn btn-outline-danger btn-sm" data-repeat-remove>
            <i class="bi bi-trash me-1"></i> Hapus
        </button>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label cv-required-label">Jenjang <span class="required-indicator" aria-hidden="true">*</span><span class="visually-hidden"> wajib diisi</span></label>
            <select name="educations[{{ $index }}][level]" class="form-select">
                <option value="">Pilih</option>
                @foreach ($educationLevels as $level)
                    <option value="{{ $level }}" {{ ($item['level'] ?? '') === $level ? 'selected' : '' }}>{{ $level }}</option>
                @endforeach
                @if (!empty($item['level']) && !in_array($item['level'], $educationLevels))
                    <option value="{{ $item['level'] }}" selected>{{ $item['level'] }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label cv-required-label">Nama Institusi <span class="required-indicator" aria-hidden="true">*</span><span class="visually-hidden"> wajib diisi</span></label>
            <input type="text" name="educations[{{ $index }}][institution]" class="form-control" value="{{ $item['institution'] ?? '' }}" placeholder="Nama sekolah/universitas">
        </div>
        <div class="col-md-3">
            <label class="form-label cv-required-label">Jurusan <span class="required-indicator" aria-hidden="true">*</span><span class="visually-hidden"> wajib diisi</span></label>
            <input type="text" name="educations[{{ $index }}][major]" class="form-control" value="{{ $item['major'] ?? '' }}" placeholder="Teknik Informatika">
        </div>
        <div class="col-md-2">
            <label class="form-label cv-required-label">Lulus <span class="required-indicator" aria-hidden="true">*</span><span class="visually-hidden"> wajib diisi</span></label>
            <select name="educations[{{ $index }}][graduation_year]" class="form-select">
                <option value="">Pilih tahun</option>
                @foreach ($yearOptions as $year)
                    <option value="{{ $year }}" {{ $selectedGraduationYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
                @if ($selectedGraduationYear !== '' && !in_array((int) $selectedGraduationYear, $yearOptions, true))
                    <option value="{{ $selectedGraduationYear }}" selected>{{ $selectedGraduationYear }}</option>
                @endif
            </select>
        </div>
    </div>
</div>
