<div class="repeat-item" data-repeat-item>
    <div class="repeat-item-header">
        <strong>Pengalaman Kerja</strong>
        <button type="button" class="btn btn-outline-danger btn-sm" data-repeat-remove>
            <i class="bi bi-trash me-1"></i> Hapus
        </button>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama Posisi/Jabatan</label>
            <input type="text" name="experiences[{{ $index }}][position]" class="form-control" value="{{ $item['position'] ?? '' }}" placeholder="Mechanical Technician">
        </div>
        <div class="col-md-6">
            <label class="form-label">Nama Perusahaan</label>
            <input type="text" name="experiences[{{ $index }}][company]" class="form-control" value="{{ $item['company'] ?? 'PT VDNI' }}" placeholder="PT VDNI">
        </div>
        <div class="col-md-6">
            <label class="form-label">Departemen</label>
            <span class="badge badge-vpeople ms-1">V-People</span>
            <input type="text" name="experiences[{{ $index }}][department]" class="form-control readonly-field" value="{{ $profile->department ?: '-' }}" readonly>
        </div>
        <div class="col-md-6">
            <label class="form-label">Divisi</label>
            <span class="badge badge-vpeople ms-1">V-People</span>
            <input type="text" name="experiences[{{ $index }}][division]" class="form-control readonly-field" value="{{ $profile->division ?: '-' }}" readonly>
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
            <label class="form-label">Tanggung Jawab</label>
            @php
                $responsibilitiesEditorHtml = \App\Support\CvResponsibilityRichText::toEditorHtml($item['responsibilities'] ?? null);
            @endphp
            <div class="rich-text-editor" data-rich-text-editor>
                <div class="rich-text-toolbar" role="toolbar" aria-label="Format tanggung jawab">
                    <button type="button" class="rich-text-toolbar-button" data-rich-text-command="bold" title="Tebal" aria-label="Tebal">
                        <i class="bi bi-type-bold"></i>
                    </button>
                    <button type="button" class="rich-text-toolbar-button" data-rich-text-command="italic" title="Miring" aria-label="Miring">
                        <i class="bi bi-type-italic"></i>
                    </button>
                    <button type="button" class="rich-text-toolbar-button" data-rich-text-command="underline" title="Garis bawah" aria-label="Garis bawah">
                        <i class="bi bi-type-underline"></i>
                    </button>
                    <span class="rich-text-toolbar-divider" aria-hidden="true"></span>
                    <button type="button" class="rich-text-toolbar-button" data-rich-text-command="insertUnorderedList" title="Daftar poin" aria-label="Daftar poin">
                        <i class="bi bi-list-ul"></i>
                    </button>
                    <button type="button" class="rich-text-toolbar-button" data-rich-text-command="insertOrderedList" title="Daftar nomor" aria-label="Daftar nomor">
                        <i class="bi bi-list-ol"></i>
                    </button>
                    <span class="rich-text-toolbar-divider" aria-hidden="true"></span>
                    <button type="button" class="rich-text-toolbar-button" data-rich-text-command="removeFormat" title="Hapus format" aria-label="Hapus format">
                        <i class="bi bi-eraser"></i>
                    </button>
                </div>
                <div class="rich-text-input form-control" contenteditable="true" role="textbox" aria-multiline="true" data-rich-text-input data-placeholder="Tulis tanggung jawab, pencapaian, atau lingkup kerja">{!! $responsibilitiesEditorHtml !!}</div>
                <textarea name="experiences[{{ $index }}][responsibilities]" class="d-none" data-rich-text-value>{{ $responsibilitiesEditorHtml }}</textarea>
            </div>
            <div class="form-text">Gunakan toolbar untuk membuat poin, penekanan teks, atau daftar bernomor.</div>
        </div>
    </div>
</div>
