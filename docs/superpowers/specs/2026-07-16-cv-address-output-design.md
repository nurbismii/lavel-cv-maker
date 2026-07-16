# CV Address Output Design

## Goal

Ensure the saved CV preview, generated PDF, and in-form live preview display domicile and KTP addresses consistently.

## Address rules

1. The primary address is always labelled `Alamat Domisili`.
2. When `domicile_same_as_ktp` is true, render only the domicile address.
3. When the domicile and KTP addresses normalize to the same value, render only the domicile address. This protects legacy data where the toggle was not saved correctly.
4. When the addresses differ and a KTP address exists, render both entries in this order:
   - `Alamat Domisili`
   - `Alamat Sesuai KTP`
5. The existing administrative location (village, district, regency, and province) remains appended only to the domicile address.
6. Empty values are not rendered. Existing drafts that only have `address` remain valid and display one domicile address.

## Architecture

`CvPreviewDataService` will prepare a structured `addresses` collection and preserve the existing `address` value only where existing consumers need it during the transition. The saved preview template and PDF view will iterate over this shared collection. The browser live-preview script will implement the same rules from the unsaved form values, including the current state of the domicile-same-as-KTP toggle.

## Testing

Regression tests will cover: one domicile address when the toggle is on, two labeled addresses when values differ, and one address for legacy identical values. Existing preview and PDF-related tests will be run after the change.
