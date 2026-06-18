@php
    $yearOptions = $yearOptions ?? range(((int) date('Y')) + 1, 1900);
    $validUntilYearOptions = $validUntilYearOptions ?? range(((int) date('Y')) + 30, 1900);
    $selectedYear = (string) ($item['year'] ?? '');
    $selectedValidUntilYear = (string) ($item['valid_until_year'] ?? '');
@endphp

<div class="repeat-item" data-repeat-item>
    <div class="repeat-item-header">
        <strong>Sertifikasi/Pelatihan</strong>
        <button type="button" class="btn btn-outline-danger btn-sm" data-repeat-remove>
            <i class="bi bi-trash me-1"></i> Hapus
        </button>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Nama</label>
            <input type="text" name="certifications[{{ $index }}][name]" class="form-control" value="{{ $item['name'] ?? '' }}" placeholder="K3 Umum">
        </div>
        <div class="col-md-4">
            <label class="form-label">Penerbit/Penyelenggara</label>
            <input type="text" name="certifications[{{ $index }}][issuer]" class="form-control" value="{{ $item['issuer'] ?? '' }}" placeholder="Kemnaker RI">
        </div>
        <div class="col-md-3">
            <label class="form-label">Jenis</label>
            <select name="certifications[{{ $index }}][type]" class="form-select">
                @foreach (['Sertifikasi', 'Pelatihan'] as $type)
                    <option value="{{ $type }}" {{ ($item['type'] ?? 'Sertifikasi') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tahun</label>
            <select name="certifications[{{ $index }}][year]" class="form-select">
                <option value="">Pilih tahun</option>
                @foreach ($yearOptions as $year)
                    <option value="{{ $year }}" {{ $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
                @if ($selectedYear !== '' && !in_array((int) $selectedYear, $yearOptions, true))
                    <option value="{{ $selectedYear }}" selected>{{ $selectedYear }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Berlaku s/d</label>
            <select name="certifications[{{ $index }}][valid_until_year]" class="form-select">
                <option value="">Pilih tahun</option>
                @foreach ($validUntilYearOptions as $year)
                    <option value="{{ $year }}" {{ $selectedValidUntilYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
                @if ($selectedValidUntilYear !== '' && !in_array((int) $selectedValidUntilYear, $validUntilYearOptions, true))
                    <option value="{{ $selectedValidUntilYear }}" selected>{{ $selectedValidUntilYear }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" id="cert_lifetime_{{ $index }}" name="certifications[{{ $index }}][is_lifetime]" value="1" {{ !empty($item['is_lifetime']) ? 'checked' : '' }}>
                <label class="form-check-label" for="cert_lifetime_{{ $index }}">Seumur hidup / tanpa masa berlaku</label>
            </div>
        </div>
    </div>
</div>
