@php
    $selectedField = (string) ($item['field'] ?? '');
    $selectedLevel = (string) ($item['level'] ?? '');
    $otherFieldId = 'achievement_other_field_' . $index;
    $otherLevelId = 'achievement_other_level_' . $index;
@endphp

<div class="repeat-item" data-repeat-item>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h6 fw-bold mb-0">Data Prestasi</h3>
        <button type="button" class="btn btn-outline-danger btn-sm" data-repeat-remove aria-label="Hapus prestasi">
            <i class="bi bi-trash me-1"></i> Hapus
        </button>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Bidang</label>
            <select name="achievements[{{ $index }}][field]" class="form-select @error("achievements.$index.field") is-invalid @enderror" data-option-toggle data-option-toggle-target="#{{ $otherFieldId }}" data-option-toggle-value="other">
                <option value="">Pilih bidang</option>
                @foreach ($achievementFieldOptions as $value => $label)
                    <option value="{{ $value }}" {{ $selectedField === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error("achievements.$index.field") <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8 {{ $selectedField === 'other' ? '' : 'd-none' }}" id="{{ $otherFieldId }}" data-option-toggle-panel>
            <label class="form-label">Bidang Lainnya</label>
            <input type="text" name="achievements[{{ $index }}][other_field]" class="form-control @error("achievements.$index.other_field") is-invalid @enderror" value="{{ $item['other_field'] ?? '' }}" maxlength="255" placeholder="Contoh: Sosial atau lingkungan" {{ $selectedField === 'other' ? '' : 'disabled' }}>
            @error("achievements.$index.other_field") <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Nama/Jenis Prestasi</label>
            <input type="text" name="achievements[{{ $index }}][achievement_type]" class="form-control @error("achievements.$index.achievement_type") is-invalid @enderror" value="{{ $item['achievement_type'] ?? '' }}" maxlength="255" placeholder="Contoh: Olimpiade Matematika">
            @error("achievements.$index.achievement_type") <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Peringkat</label>
            <input type="text" name="achievements[{{ $index }}][rank]" class="form-control @error("achievements.$index.rank") is-invalid @enderror" value="{{ $item['rank'] ?? '' }}" maxlength="100" placeholder="Contoh: Juara 1 atau Finalis">
            @error("achievements.$index.rank") <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label">Tingkat</label>
            <select name="achievements[{{ $index }}][level]" class="form-select @error("achievements.$index.level") is-invalid @enderror" data-option-toggle data-option-toggle-target="#{{ $otherLevelId }}" data-option-toggle-value="other">
                <option value="">Pilih tingkat</option>
                @foreach ($achievementLevelOptions as $value => $label)
                    <option value="{{ $value }}" {{ $selectedLevel === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error("achievements.$index.level") <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 {{ $selectedLevel === 'other' ? '' : 'd-none' }}" id="{{ $otherLevelId }}" data-option-toggle-panel>
            <label class="form-label">Tingkat Lainnya</label>
            <input type="text" name="achievements[{{ $index }}][other_level]" class="form-control @error("achievements.$index.other_level") is-invalid @enderror" value="{{ $item['other_level'] ?? '' }}" maxlength="255" placeholder="Tuliskan tingkat prestasi" {{ $selectedLevel === 'other' ? '' : 'disabled' }}>
            @error("achievements.$index.other_level") <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Periode</label>
            <input type="month" name="achievements[{{ $index }}][period]" class="form-control @error("achievements.$index.period") is-invalid @enderror" value="{{ $item['period'] ?? '' }}" min="1900-01" max="{{ date('Y') + 1 }}-12">
            @error("achievements.$index.period") <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
