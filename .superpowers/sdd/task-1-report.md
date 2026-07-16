# Task 1 Implementation Report

## Scope completed

- Added `addresses` to `CvPreviewDataService::build()`.
- Kept the legacy `address` field and updated its address-line formatting to retain meaningful line breaks.
- Produced labelled domicile and KTP entries, adding administrative locations only to the domicile entry.
- Suppressed KTP output when the toggle is truthy or the normalized raw address values match.
- Did not modify `resources/views/cv/edit.blade.php`.

## TDD evidence

1. Added the three required service tests before implementation.
2. Ran `php artisan test tests/Unit/CvPreviewDataServiceTest.php`; all three failed with `Undefined index: addresses`.
3. Implemented the smallest service changes and reran the test successfully.
4. Strengthened the legacy-equivalence test with domicile administrative data; it failed because comparison mistakenly included the appended location line.
5. Compared normalized KTP and raw domicile values instead, then reran successfully.

## Final verification

- `php artisan test tests/Unit/CvPreviewDataServiceTest.php`: 3 passed.
- `php -l app/Services/CvPreviewDataService.php`: no syntax errors.
- `php -l tests/Unit/CvPreviewDataServiceTest.php`: no syntax errors.
- `git diff --check`: no whitespace errors.

## Self-review

- The output contract is `array<int, array{label: string, value: string}>`.
- Newline-separated address lines are retained while per-line excess whitespace is normalized.
- Administrative location names are never added to KTP values.
- Existing unrelated editor changes were left unmodified.
