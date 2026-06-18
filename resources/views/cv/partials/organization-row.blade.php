@php
    $yearOptions = $yearOptions ?? range(((int) date('Y')) + 1, 1900);
    $selectedStartYear = (string) ($item['start_year'] ?? '');
    $selectedEndYear = (string) ($item['end_year'] ?? '');
@endphp

<div class="repeat-item repeat-item--compact" data-repeat-item>
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Organisasi</label>
            <input type="text" name="organizations[{{ $index }}][organization_name]" class="form-control" value="{{ $item['organization_name'] ?? '' }}" placeholder="Serikat Pekerja Unit X">
        </div>
        <div class="col-md-3">
            <label class="form-label">Jabatan/Peran</label>
            <input type="text" name="organizations[{{ $index }}][role]" class="form-control" value="{{ $item['role'] ?? '' }}" placeholder="Ketua">
        </div>
        <div class="col-md-2">
            <label class="form-label">Mulai</label>
            <select name="organizations[{{ $index }}][start_year]" class="form-select">
                <option value="">Pilih tahun</option>
                @foreach ($yearOptions as $year)
                    <option value="{{ $year }}" {{ $selectedStartYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
                @if ($selectedStartYear !== '' && !in_array((int) $selectedStartYear, $yearOptions, true))
                    <option value="{{ $selectedStartYear }}" selected>{{ $selectedStartYear }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Selesai</label>
            <select name="organizations[{{ $index }}][end_year]" class="form-select">
                <option value="">Pilih tahun</option>
                @foreach ($yearOptions as $year)
                    <option value="{{ $year }}" {{ $selectedEndYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
                @if ($selectedEndYear !== '' && !in_array((int) $selectedEndYear, $yearOptions, true))
                    <option value="{{ $selectedEndYear }}" selected>{{ $selectedEndYear }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100" data-repeat-remove>
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
