@php
    $yearOptions = $yearOptions ?? range(((int) date('Y')) + 1, 1900);
    $selectedYear = (string) ($item['year'] ?? '');
@endphp

<div class="repeat-item repeat-item--compact" data-repeat-item>
    <div class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label">Nama Proyek</label>
            <input type="text" name="projects[{{ $index }}][name]" class="form-control" value="{{ $item['name'] ?? '' }}" placeholder="Business Process Mapping Departemen Smelter">
        </div>
        <div class="col-md-2">
            <label class="form-label">Tahun</label>
            <select name="projects[{{ $index }}][year]" class="form-select">
                <option value="">Pilih tahun</option>
                @foreach ($yearOptions as $year)
                    <option value="{{ $year }}" {{ $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
                @if ($selectedYear !== '' && !in_array((int) $selectedYear, $yearOptions, true))
                    <option value="{{ $selectedYear }}" selected>{{ $selectedYear }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger w-100" data-repeat-remove>
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
