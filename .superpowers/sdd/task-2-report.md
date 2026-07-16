# Task 2 Report: Render shared address collection

## Scope

- Updated `resources/views/cv/templates/hris.blade.php` to render the address data supplied by `CvPreviewDataService`.
- Added a source-contract regression test in `tests/Unit/CvLivePreviewTest.php`.
- Did not change `resources/views/cv/edit.blade.php` or `resources/views/cv/pdf.blade.php`.

## TDD evidence

1. Added `test_cv_output_renders_shared_labelled_address_collection()` before changing the Blade template.
2. Ran `php artisan test tests/Unit/CvLivePreviewTest.php --filter=shared_labelled_address_collection`.
   - RED: one assertion failed because the template still used `$preview['address']` and did not contain the `@forelse` collection loop.
3. Replaced the single address paragraph with a `@forelse ($preview['addresses'] as $address)` loop.
   - Each entry renders `<strong>{{ $address['label'] }}:</strong>`.
   - Address values use `{!! nl2br(e($address['value'])) !!}` to escape HTML while preserving line breaks.
   - Empty collections render `Alamat Domisili belum diisi`.
4. Re-ran the focused test and the whole `CvLivePreviewTest` suite.

## Validation

- `php artisan test tests/Unit/CvLivePreviewTest.php --filter=shared_labelled_address_collection` — PASS (1 test)
- `php artisan test tests/Unit/CvLivePreviewTest.php` — PASS (7 tests)

## Scope and concerns

- The PDF view was intentionally left unchanged because it includes this shared HRIS template.
- Existing uncommitted changes to `resources/views/cv/edit.blade.php` belong to another task and were not staged or committed.
