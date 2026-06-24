@php
    $relationshipOptions = $emergencyRelationshipOptions ?? \App\Models\CvEmergencyContact::RELATIONSHIPS;
    $selectedRelationship = (string) ($item['relationship'] ?? '');
    $phoneError = $errors->first('emergency_contacts.' . $index . '.phone');
    $nameError = $errors->first('emergency_contacts.' . $index . '.name');
    $relationshipError = $errors->first('emergency_contacts.' . $index . '.relationship');
@endphp

<div class="repeat-item repeat-item--compact" data-repeat-item>
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Nomor Kontak Darurat</label>
            <input type="text" name="emergency_contacts[{{ $index }}][phone]" inputmode="numeric" pattern="[0-9]*" maxlength="13" class="form-control {{ $phoneError ? 'is-invalid' : '' }}" value="{{ $item['phone'] ?? '' }}" placeholder="081234567890">
            @if ($phoneError) <div class="invalid-feedback">{{ $phoneError }}</div> @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">Nama Kontak Darurat</label>
            <input type="text" name="emergency_contacts[{{ $index }}][name]" class="form-control {{ $nameError ? 'is-invalid' : '' }}" value="{{ $item['name'] ?? '' }}" placeholder="Nama lengkap">
            @if ($nameError) <div class="invalid-feedback">{{ $nameError }}</div> @endif
        </div>
        <div class="col-md-3">
            <label class="form-label">Hubungan</label>
            <select name="emergency_contacts[{{ $index }}][relationship]" class="form-select {{ $relationshipError ? 'is-invalid' : '' }}">
                <option value="">Pilih hubungan</option>
                @foreach ($relationshipOptions as $relationship)
                    <option value="{{ $relationship }}" {{ $selectedRelationship === $relationship ? 'selected' : '' }}>{{ $relationship }}</option>
                @endforeach
                @if ($selectedRelationship !== '' && !in_array($selectedRelationship, $relationshipOptions))
                    <option value="{{ $selectedRelationship }}" selected>{{ $selectedRelationship }}</option>
                @endif
            </select>
            @if ($relationshipError) <div class="invalid-feedback">{{ $relationshipError }}</div> @endif
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger w-100" data-repeat-remove>
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
