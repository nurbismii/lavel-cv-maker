# Task 3 Report: Live Preview Address Rules

## Scope

- Updated `public/js/cv-form.js`.
- Updated `tests/Unit/CvLivePreviewTest.php`.
- Did not modify `resources/views/cv/edit.blade.php`.

## Implementation

- Live-preview data now collects `ktp_address` and the checked state of `data-domicile-same-toggle`.
- `livePreviewAddresses(data)` builds the labelled address collection used by the preview:
  - The domicile value combines domicile text and selected location.
  - The KTP value never receives the selected location.
  - KTP is omitted when the toggle is enabled or its normalized value matches the domicile text (case- and whitespace-insensitive).
  - KTP is also omitted when there is no domicile output, matching `CvPreviewDataService`.
- `renderLivePreviewAddresses(addresses)` renders the labelled values with escaped multiline text and returns `Alamat Domisili belum diisi` for an empty collection.

## TDD Evidence

1. Added `test_live_preview_uses_the_shared_domicile_and_ktp_address_rules` before changing production JavaScript.
2. Ran `php artisan test --filter=CvLivePreviewTest`; it failed as expected because `ktp_address` was not collected.
3. Implemented the minimal address collection and rendering functions.
4. Re-ran the focused test: 8 passed.

## Verification

- `php artisan test --filter=CvLivePreviewTest` — 8 passed.
- `node --check public/js/cv-form.js` — passed.
- `git diff --check` — passed.
