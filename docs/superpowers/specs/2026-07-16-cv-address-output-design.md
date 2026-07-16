# CV Address Output Design

## Goal

Ensure the saved CV preview, generated PDF, and in-form live preview display domicile and KTP addresses consistently.

## Address rules

1. When `domicile_same_as_ktp` is true, render only `Alamat Domisili`.
2. When the domicile and KTP addresses normalize to the same value, render only `Alamat Domisili`. This protects legacy data where the toggle was not saved correctly.
3. When the addresses differ and both values exist, render both entries in this order:
   - `Alamat KTP`
   - `Alamat Domisili`
4. The administrative location is a third, separate entry after the address entries, labelled `Kel/Desa, Kec, Kab/Kota, Prov`.
5. The administrative location is rendered only when it has data and belongs to the domicile address.
6. Empty values are not rendered. Existing drafts that only have `address` remain valid and display one domicile address followed by location when available.

## Architecture

`CvPreviewDataService` will prepare an ordered structured `addresses` collection containing KTP, domicile, and location entries as applicable. The saved preview template and PDF view will iterate over this shared collection. The browser live-preview script will implement the same ordering from unsaved form values, including the current state of the domicile-same-as-KTP toggle.

## Testing

Regression tests will cover: one domicile address when the toggle is on, two labeled addresses when values differ, and one address for legacy identical values. Existing preview and PDF-related tests will be run after the change.
