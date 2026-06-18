<div class="repeat-item repeat-item--compact" data-repeat-item>
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Bahasa</label>
            <input type="text" name="languages[{{ $index }}][language]" class="form-control" value="{{ $item['language'] ?? '' }}" placeholder="Indonesia">
        </div>
        <div class="col-md-5">
            <label class="form-label">Tingkat</label>
            <select name="languages[{{ $index }}][level]" class="form-select">
                <option value="">Pilih</option>
                @foreach ($languageLevels as $level)
                    <option value="{{ $level }}" {{ ($item['level'] ?? '') === $level ? 'selected' : '' }}>{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger w-100" data-repeat-remove>
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
