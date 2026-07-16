# CV Address Output Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Render one labelled domicile address when KTP and domicile match, and two labelled addresses when they differ, consistently in every CV output.

**Architecture:** `CvPreviewDataService` owns the normalization and ordered `addresses` collection used by the saved preview and PDF template. `cv-form.js` applies the same rule to unsaved live-preview form state.

**Tech Stack:** Laravel, PHP, Blade, vanilla JavaScript, PHPUnit, DomPDF.

## Global Constraints

- The first/single address label is exactly `Alamat Domisili`.
- A differing KTP entry is exactly `Alamat Sesuai KTP` and appears after domicile.
- Administrative locations append only to the domicile address.
- Case- and whitespace-equivalent legacy addresses must not be duplicated.
- Do not stage or alter unrelated local edits in `resources/views/cv/edit.blade.php`.

---

### Task 1: Provide shared address presentation data

**Files:**

- Create: `tests/Unit/CvPreviewDataServiceTest.php`
- Modify: `app/Services/CvPreviewDataService.php:36-98`

**Interfaces:**

- Produces: `build(): array` with `addresses: array<int, array{label: string, value: string}>`.

- [ ] **Step 1: Write the failing tests**

```php
public function test_build_returns_one_domicile_address_when_ktp_is_same(): void
{
    $preview = $this->previewFor(['address' => 'Jl. Merdeka No. 1', 'ktp_address' => 'Jl. Merdeka No. 1', 'domicile_same_as_ktp' => true]);

    $this->assertSame([['label' => 'Alamat Domisili', 'value' => 'Jl. Merdeka No. 1']], $preview['addresses']);
}

public function test_build_returns_domicile_and_ktp_when_different(): void
{
    $preview = $this->previewFor(['address' => 'Jl. Domisili No. 2', 'ktp_address' => 'Jl. KTP No. 10', 'domicile_same_as_ktp' => false]);

    $this->assertSame([
        ['label' => 'Alamat Domisili', 'value' => 'Jl. Domisili No. 2'],
        ['label' => 'Alamat Sesuai KTP', 'value' => 'Jl. KTP No. 10'],
    ], $preview['addresses']);
}

public function test_build_does_not_duplicate_legacy_equivalent_addresses(): void
{
    $preview = $this->previewFor(['address' => 'Jl. Merdeka No. 1', 'ktp_address' => '  JL.   merdeka no. 1 ', 'domicile_same_as_ktp' => false]);

    $this->assertCount(1, $preview['addresses']);
}
```

The helper creates a `CvProfile`, sets every relation consumed by `build()` to empty collections, and invokes the service with `new User()`.

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Unit/CvPreviewDataServiceTest.php`

Expected: FAIL because `addresses` does not exist.

- [ ] **Step 3: Implement the smallest formatter**

```php
// build()
'addresses' => $this->addresses($profile),

private function addresses(CvProfile $profile): array
{
    $domicile = $this->address($profile);
    $ktp = $this->cleanMultilineText($profile->ktp_address);
    $addresses = $domicile ? [['label' => 'Alamat Domisili', 'value' => $domicile]] : [];

    if ($ktp && !$profile->domicile_same_as_ktp && !$this->addressesMatch($domicile, $ktp)) {
        $addresses[] = ['label' => 'Alamat Sesuai KTP', 'value' => $ktp];
    }

    return $addresses;
}
```

Implement `cleanMultilineText()` to preserve line breaks, and `addressesMatch()` using trimmed, collapsed-whitespace `mb_strtolower` values.

- [ ] **Step 4: Verify GREEN and commit**

Run: `php artisan test tests/Unit/CvPreviewDataServiceTest.php`

Expected: PASS, 3 tests.

```powershell
git add app/Services/CvPreviewDataService.php tests/Unit/CvPreviewDataServiceTest.php
git commit -m "feat: format domicile and ktp addresses"
```

### Task 2: Render the shared collection in preview and PDF

**Files:**

- Modify: `resources/views/cv/templates/hris.blade.php:26`
- Modify: `tests/Unit/CvLivePreviewTest.php`

**Interfaces:**

- Consumes: `$preview['addresses']`; `cv/pdf.blade.php` already includes this shared template and needs no duplicate view logic.

- [ ] **Step 1: Write the failing contract test**

```php
public function test_cv_output_renders_shared_labelled_address_collection(): void
{
    $template = $this->hrisTemplate();

    $this->assertStringContainsString("@forelse ($preview['addresses'] as $address)", $template);
    $this->assertStringContainsString("{{ $address['label'] }}:", $template);
    $this->assertStringContainsString("nl2br(e($address['value']))", $template);
}
```

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Unit/CvLivePreviewTest.php --filter=shared_labelled_address_collection`

Expected: FAIL because the template uses `$preview['address']`.

- [ ] **Step 3: Replace the single address paragraph**

```blade
@forelse ($preview['addresses'] as $address)
    <p class="cv-output-contact"><strong>{{ $address['label'] }}:</strong> {!! nl2br(e($address['value'])) !!}</p>
@empty
    <p class="cv-output-contact">Alamat Domisili belum diisi</p>
@endforelse
```

- [ ] **Step 4: Verify GREEN and commit**

Run: `php artisan test tests/Unit/CvLivePreviewTest.php --filter=shared_labelled_address_collection`

Expected: PASS, 1 test.

```powershell
git add resources/views/cv/templates/hris.blade.php tests/Unit/CvLivePreviewTest.php
git commit -m "feat: label addresses in cv output"
```

### Task 3: Match the rule in live preview

**Files:**

- Modify: `public/js/cv-form.js:1906-1983`
- Modify: `tests/Unit/CvLivePreviewTest.php`

**Interfaces:**

- Produces: `collectLivePreviewData().addresses` and `renderLivePreviewAddresses(addresses)`.

- [ ] **Step 1: Write the failing source contract**

```php
public function test_live_preview_builds_and_renders_labelled_addresses(): void
{
    $script = $this->script();

    foreach ([
        "ktp_address: livePreviewFieldValue('ktp_address')",
        'function livePreviewAddresses',
        'function renderLivePreviewAddresses',
        'Alamat Domisili',
        'Alamat Sesuai KTP',
        'renderLivePreviewAddresses(data.addresses)',
    ] as $expected) {
        $this->assertStringContainsString($expected, $script);
    }
}
```

- [ ] **Step 2: Verify RED**

Run: `php artisan test tests/Unit/CvLivePreviewTest.php --filter=live_preview_builds_and_renders_labelled_addresses`

Expected: FAIL because live preview creates only `data.address`.

- [ ] **Step 3: Implement collection and rendering**

```javascript
function livePreviewAddresses(data) {
    var domicile = cleanLivePreviewList([data.address, data.location]).join('\n');
    var ktp = cleanLivePreviewMultilineText(data.ktp_address || '');
    var addresses = domicile ? [{ label: 'Alamat Domisili', value: domicile }] : [];

    if (ktp && !data.domicile_same_as_ktp && normalizeLivePreviewAddress(domicile) !== normalizeLivePreviewAddress(ktp)) {
        addresses.push({ label: 'Alamat Sesuai KTP', value: ktp });
    }

    return addresses;
}

function renderLivePreviewAddresses(addresses) {
    return addresses.length ? addresses.map(function (address) {
        return '<p class="cv-output-contact"><strong>' + escapeHtml(address.label) + ':</strong> ' + nl2br(escapeHtml(address.value)) + '</p>';
    }).join('') : '<p class="cv-output-contact">Alamat Domisili belum diisi</p>';
}
```

Collect KTP/toggle values, calculate `data.addresses`, replace the hard-coded address paragraph, and add whitespace/case `normalizeLivePreviewAddress()`.

- [ ] **Step 4: Verify GREEN and commit**

Run: `php artisan test tests/Unit/CvLivePreviewTest.php --filter=live_preview_builds_and_renders_labelled_addresses`

Expected: PASS, 1 test.

```powershell
git add public/js/cv-form.js tests/Unit/CvLivePreviewTest.php
git commit -m "feat: sync live preview address labels"
```

### Task 4: Regression verification

**Files:**

- Verify: `tests/Unit/CvPreviewDataServiceTest.php`, `tests/Unit/CvLivePreviewTest.php`, `tests/Feature/CvDocumentTest.php`

- [ ] **Step 1: Run all relevant tests**

Run: `php artisan test tests/Unit/CvPreviewDataServiceTest.php tests/Unit/CvLivePreviewTest.php tests/Feature/CvDocumentTest.php`

Expected: PASS with zero failures.

- [ ] **Step 2: Check scope and whitespace**

Run: `git diff --check; git status --short; git diff -- resources/views/cv/edit.blade.php`

Expected: no whitespace errors; the unrelated edit-form change remains unstaged and unchanged.

