<div class="repeat-item" data-repeat-item>
    @php
        $departmentPlaceholder = $profile->department ?: 'Contoh: Maintenance';
        $divisionPlaceholder = $profile->division ?: 'Contoh: Mechanical';
    @endphp

    <div class="repeat-item-header">
        <strong>Pengalaman Kerja</strong>
        <button type="button" class="btn btn-outline-danger btn-sm" data-repeat-remove>
            <i class="bi bi-trash me-1"></i> Hapus
        </button>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="exp_copy_current_{{ $index }}" data-copy-current-job>
                <label class="form-check-label" for="exp_copy_current_{{ $index }}">Autofill</label>
                <div class="form-text">Isi otomatis dari data pekerjaan di PT VDNI</div>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nama Posisi/Jabatan</label>
            <input type="text" name="experiences[{{ $index }}][position]" class="form-control" value="{{ $item['position'] ?? '' }}" placeholder="Mechanical Technician">
        </div>
        <div class="col-md-6">
            <label class="form-label">Nama Perusahaan</label>
            <input type="text" name="experiences[{{ $index }}][company]" class="form-control" value="{{ $item['company'] ?? '' }}" placeholder="PT VDNI">
        </div>
        <div class="col-md-6">
            <label class="form-label">Departemen</label>
            <input type="text" name="experiences[{{ $index }}][department]" class="form-control" value="{{ $item['department'] ?? '' }}" placeholder="{{ $departmentPlaceholder }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Divisi</label>
            <input type="text" name="experiences[{{ $index }}][division]" class="form-control" value="{{ $item['division'] ?? '' }}" placeholder="{{ $divisionPlaceholder }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Mulai</label>
            <input type="month" name="experiences[{{ $index }}][start_month]" class="form-control" value="{{ $item['start_month'] ?? '' }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Selesai</label>
            <input type="month" name="experiences[{{ $index }}][end_month]" class="form-control" value="{{ $item['end_month'] ?? '' }}" data-current-target>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="exp_current_{{ $index }}" name="experiences[{{ $index }}][is_current]" value="1" data-current-checkbox {{ !empty($item['is_current']) ? 'checked' : '' }}>
                <label class="form-check-label" for="exp_current_{{ $index }}">Masih bekerja sampai sekarang</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Job Description</label>
            @php
                $responsibilitiesText = \App\Support\CvResponsibilityRichText::toTextareaText($item['responsibilities'] ?? null);
            @endphp
            <textarea name="experiences[{{ $index }}][responsibilities]" rows="5" class="form-control" placeholder="Contoh: Melakukan perawatan mesin&#10;Membuat laporan harian&#10;Memastikan area kerja aman">{{ $responsibilitiesText }}</textarea>
            <div class="form-text">Tekan Enter untuk membuat baris baru.</div>
        </div>
    </div>
</div>
