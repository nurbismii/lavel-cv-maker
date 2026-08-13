@php
    $documents = $documentsByType->get($documentType, collect());
    $document = $documents->first();
    $documentOption = $documentOptions[$documentType];
    $acceptsMultipleFiles = \App\Models\CvDocument::acceptsMultipleFiles($documentType);
    $documentErrorKey = 'documents.' . $documentType;
    $removeDocumentId = 'remove_linked_document_' . $documentType;
    $documentSize = $document && $document->file_size ? number_format($document->file_size / 1024, 0) . ' KB' : null;
@endphp

<div class="cv-linked-document {{ $document ? 'has-document' : '' }}" data-document-required="{{ $documentOption['required'] ? '1' : '0' }}" data-document-has-file="{{ $document ? '1' : '0' }}" data-document-label="{{ $documentOption['label'] }}">
    <div class="cv-linked-document-heading">
        <i class="bi bi-paperclip"></i>
        <span>Dokumen {{ $documentOption['label'] }}</span>
        @if ($documentOption['required'])
        <span class="required-indicator" aria-hidden="true">*</span>
        <span class="visually-hidden"> wajib diisi</span>
        @endif
    </div>

    @if ($document)
    <div class="cv-linked-document-list">
    @foreach ($documents as $document)
    @php $documentSize = $document->file_size ? number_format($document->file_size / 1024, 0) . ' KB' : null; @endphp
    <div class="cv-linked-document-current">
        <a href="{{ route('cv.documents.download', $document) }}" target="_blank" rel="noopener">
            <i class="bi bi-eye me-1"></i>{{ $document->original_name }}
        </a>
        @if ($documentSize)<small>{{ $documentSize }}</small>@endif
        @if ($acceptsMultipleFiles)
        <div class="cv-deferred-delete">
            <input class="btn-check" type="checkbox" name="remove_documents[{{ $documentType }}][{{ $document->id }}]" value="1" id="remove_document_{{ $documentType }}_{{ $document->id }}" data-deferred-remove {{ old('remove_documents.' . $documentType . '.' . $document->id) ? 'checked' : '' }}>
            <label class="btn btn-outline-danger btn-sm cv-deferred-delete-action" for="remove_document_{{ $documentType }}_{{ $document->id }}" title="Tandai file untuk dihapus" data-bs-toggle="tooltip" data-bs-title="Tandai file untuk dihapus">
                <span class="cv-delete-action-default"><i class="bi bi-trash"></i><span class="visually-hidden">Hapus {{ $document->original_name }}</span></span>
                <span class="cv-delete-action-undo"><i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan</span>
            </label>
            <small class="cv-deferred-delete-status">Akan dihapus saat disimpan.</small>
        </div>
        @endif
    </div>
    @endforeach
    </div>
    @endif

    @if ($acceptsMultipleFiles)
    <div class="cv-document-file-input-list" data-document-file-input-list>
        <div class="cv-document-file-input-item d-flex align-items-center gap-2 mb-2" data-document-file-input-item style="margin-bottom: .5rem;">
            <input id="document_{{ $documentType }}" type="file" name="documents[{{ $documentType }}][]" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="form-control form-control-sm flex-grow-1 @error($documentErrorKey) is-invalid @enderror" data-document-file-input>
            <button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0" data-document-file-remove aria-label="Batalkan file ini" title="Batalkan file ini">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-document-file-add>
        <i class="bi bi-plus-lg me-1"></i> Tambah File
    </button>
    <small class="text-muted d-block mt-1">PDF/JPG/PNG, maks. 5MB per file. Tambahkan file satu per satu.</small>
    @else
    <input id="document_{{ $documentType }}" type="file" name="documents[{{ $documentType }}]{{ $acceptsMultipleFiles ? '[]' : '' }}" {{ $acceptsMultipleFiles ? 'multiple' : '' }} accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="form-control form-control-sm @error($documentErrorKey) is-invalid @enderror">
    <small class="text-muted d-block mt-1">PDF/JPG/PNG, maks. 5MB{{ $document ? '. Pilih file untuk mengganti.' : '' }}</small>
    @endif
    @error($documentErrorKey) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

    @if ($document && !$acceptsMultipleFiles)
    <div class="cv-deferred-delete mt-2">
        <input class="btn-check" type="checkbox" name="remove_documents[{{ $documentType }}]" value="1" id="{{ $removeDocumentId }}" data-deferred-remove {{ old('remove_documents.' . $documentType) ? 'checked' : '' }}>
        <label class="btn btn-outline-danger btn-sm cv-deferred-delete-action" for="{{ $removeDocumentId }}" title="Tandai file untuk dihapus" data-bs-toggle="tooltip" data-bs-title="Tandai file untuk dihapus">
            <span class="cv-delete-action-default"><i class="bi bi-trash me-1"></i>Hapus file</span>
            <span class="cv-delete-action-undo"><i class="bi bi-arrow-counterclockwise me-1"></i>Batalkan</span>
        </label>
        <small class="cv-deferred-delete-status">File akan dihapus saat disimpan.</small>
    </div>
    @endif

</div>
