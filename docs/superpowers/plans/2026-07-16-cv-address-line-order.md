# CV Address Line Order Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Display KTP, domicile, and administrative location as independently labelled lines in the required order.

**Architecture:** The existing shared `addresses` collection will become an ordered list of address and location entries. Blade/PDF and live preview will consume the same entry shape.

**Tech Stack:** Laravel, PHP, Blade, vanilla JavaScript, PHPUnit.

## Global Constraints

- Equal KTP and domicile values render only `Alamat Domisili`, followed by location when available.
- Different values render `Alamat KTP`, then `Alamat Domisili`, then `Kel/Desa, Kec, Kab/Kota, Prov`.
- The location entry must remain separate from domicile text.
- Do not modify `resources/views/cv/edit.blade.php`.

---

### Task 1: Update address collection and output rendering

**Files:**

- Modify: `app/Services/CvPreviewDataService.php`
- Modify: `public/js/cv-form.js`
- Modify: `tests/Unit/CvPreviewDataServiceTest.php`
- Modify: `tests/Unit/CvLivePreviewTest.php`

- [ ] **Step 1: Write failing assertions**

Add service tests asserting different values produce exactly:

```php
[
    ['label' => 'Alamat KTP', 'value' => 'Jl. KTP No. 10'],
    ['label' => 'Alamat Domisili', 'value' => 'Jl. Domisili No. 2'],
    ['label' => 'Kel/Desa, Kec, Kab/Kota, Prov', 'value' => 'Wawatu, Moramo, Konawe Selatan, Sulawesi Tenggara'],
]
```

Add a same-address assertion confirming domicile then location only. Add source-contract assertions for the matching JavaScript labels and location entry.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Unit/CvPreviewDataServiceTest.php tests/Unit/CvLivePreviewTest.php`

Expected: FAIL because current output combines location with domicile and puts KTP last.

- [ ] **Step 3: Implement minimal ordered entries**

In PHP, split `address()` into raw domicile and location formatters; construct KTP first only when distinct, domicile next, then a location entry. In JavaScript, use the same ordering and separate location entry in `livePreviewAddresses()`.

- [ ] **Step 4: Verify GREEN**

Run: `php artisan test tests/Unit/CvPreviewDataServiceTest.php tests/Unit/CvLivePreviewTest.php`

Expected: PASS.

- [ ] **Step 5: Full regression and commit**

Run: `php artisan test; node --check public/js/cv-form.js; git diff --check`

Expected: all tests pass and no whitespace errors.

```powershell
git add app/Services/CvPreviewDataService.php public/js/cv-form.js tests/Unit/CvPreviewDataServiceTest.php tests/Unit/CvLivePreviewTest.php
git commit -m "feat: reorder cv address output"
```

