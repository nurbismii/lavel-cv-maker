# Reset Password Vitae dan V-People Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan reset password berbasis email yang hanya berhasil ketika hash password baru tersimpan konsisten pada akun Vitae dan akun V-People yang terhubung.

**Architecture:** Laravel Password Broker menangani token dan notifikasi. `VPeopleAccountService` memvalidasi pasangan akun serta melakukan compare-and-swap hash eksternal, sedangkan `SynchronizedPasswordResetService` mengorkestrasi satu hash untuk dua database dan memulihkan hash V-People jika penulisan lokal gagal. Controller hanya menangani HTTP, pesan aman, event reset, dan logging.

**Tech Stack:** PHP 7.4, Laravel 8.83, PHPUnit 9.5, Eloquent/Query Builder, Blade, Bootstrap 5.

## Global Constraints

- Permintaan reset hanya menerima email.
- Respons permintaan tidak boleh mengungkapkan apakah email terdaftar.
- Reset ditolak untuk user tanpa akun V-People yang cocok berdasarkan NIK dan email.
- Password baru di-hash satu kali dan hash identik ditulis ke kedua database.
- Kegagalan V-People tidak boleh mengubah password lokal.
- Kegagalan lokal setelah perubahan V-People wajib memicu rollback kompensasi V-People.
- Token hanya dikonsumsi setelah callback Password Broker selesai tanpa exception.
- Jangan mencatat password plaintext, token reset, hash password, atau NIK plaintext.
- Tidak ada migration atau dependency baru.

---

## Pemetaan File

- Create `app/Services/SynchronizedPasswordResetService.php`: orkestrasi hash tunggal, update V-People, update lokal bersyarat, dan kompensasi.
- Modify `app/Services/VPeopleAccountService.php`: resolusi akun terhubung dan operasi compare-and-swap/restore hash.
- Create `app/Http/Controllers/Auth/ForgotPasswordController.php`: form serta pengiriman tautan dengan respons netral.
- Create `app/Http/Controllers/Auth/ResetPasswordController.php`: validasi form, Password Broker, sinkronisasi, event, dan error handling.
- Modify `routes/web.php`: empat route guest reset password dengan throttle.
- Modify `resources/views/auth/login.blade.php`: tautan lupa password.
- Create `resources/views/auth/forgot-password.blade.php`: form email.
- Create `resources/views/auth/reset-password.blade.php`: token, email, password, konfirmasi, toggle, dan loading state.
- Create `tests/Feature/PasswordResetTest.php`: pengujian HTTP, token, dua database, rollback, dan UI.
- Create `tests/Unit/VPeoplePasswordSyncTest.php`: pengujian batas layanan dan compare-and-swap.

### Task 1: Batas akun V-People dan compare-and-swap hash

**Files:**
- Modify: `app/Services/VPeopleAccountService.php`
- Create: `tests/Unit/VPeoplePasswordSyncTest.php`

**Interfaces:**
- Consumes: `User::$vpeople_nik_encrypted`, koneksi `vpeople`, tabel `vpeople.users`.
- Produces: `passwordResetAccount(User $user): array`, `replacePasswordHash(array $account, string $newHash): void`, dan `restorePasswordHash(array $account, string $failedHash): void`.
- Snapshot account: `['id' => string, 'password' => string, 'updated_at' => mixed]`.

- [ ] **Step 1: Tulis unit test gagal untuk resolusi akun dan update bersyarat**

Buat `tests/Unit/VPeoplePasswordSyncTest.php` dengan setup koneksi SQLite `vpeople_test`, tabel `users`, mock `VPeopleService`, lalu uji empat perilaku: user tanpa NIK ditolak, email tidak cocok ditolak, update mengganti tepat satu hash, dan snapshot basi ditolak. Gunakan isi inti berikut:

```php
<?php

namespace Tests\Unit;

use App\Exceptions\VPeopleAccountException;
use App\Models\User;
use App\Services\VPeopleAccountService;
use App\Services\VPeopleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class VPeoplePasswordSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.vpeople' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);
        DB::purge('vpeople');
        DB::reconnect('vpeople');
        Schema::connection('vpeople')->create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nik_karyawan');
            $table->string('email');
            $table->string('password');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function test_password_reset_account_rejects_user_without_linked_nik(): void
    {
        $this->expectException(VPeopleAccountException::class);
        $this->service()->passwordResetAccount(new User(['email' => 'user@example.test']));
    }

    public function test_password_reset_account_rejects_email_mismatch(): void
    {
        DB::connection('vpeople')->table('users')->insert($this->account(['email' => 'other@example.test']));
        $user = new User(['email' => 'user@example.test']);
        $user->vpeople_nik_encrypted = Crypt::encryptString('EMP001');

        $this->expectException(VPeopleAccountException::class);
        $this->service()->passwordResetAccount($user);
    }

    public function test_replace_and_restore_password_hash_use_snapshot_value(): void
    {
        DB::connection('vpeople')->table('users')->insert($this->account());
        $user = new User(['email' => 'user@example.test']);
        $user->vpeople_nik_encrypted = Crypt::encryptString('EMP001');
        $snapshot = $this->service()->passwordResetAccount($user);

        $this->service()->replacePasswordHash($snapshot, 'new-hash');
        $this->assertSame('new-hash', DB::connection('vpeople')->table('users')->value('password'));

        $this->service()->restorePasswordHash($snapshot, 'new-hash');
        $this->assertSame('old-hash', DB::connection('vpeople')->table('users')->value('password'));
    }

    public function test_replace_rejects_a_stale_password_snapshot(): void
    {
        DB::connection('vpeople')->table('users')->insert($this->account());
        $user = new User(['email' => 'user@example.test']);
        $user->vpeople_nik_encrypted = Crypt::encryptString('EMP001');
        $snapshot = $this->service()->passwordResetAccount($user);
        DB::connection('vpeople')->table('users')->update(['password' => 'concurrent-hash']);

        $this->expectException(VPeopleAccountException::class);
        $this->service()->replacePasswordHash($snapshot, 'new-hash');
    }

    private function service(): VPeopleAccountService
    {
        return new VPeopleAccountService(Mockery::mock(VPeopleService::class));
    }

    private function account(array $overrides = []): array
    {
        return array_merge([
            'id' => 'vp-1',
            'nik_karyawan' => 'EMP001',
            'email' => 'user@example.test',
            'password' => 'old-hash',
            'updated_at' => '2026-08-18 10:00:00',
        ], $overrides);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan RED**

Run: `php artisan test tests/Unit/VPeoplePasswordSyncTest.php`

Expected: FAIL karena tiga method baru belum tersedia pada `VPeopleAccountService`.

- [ ] **Step 3: Implementasikan resolusi akun dan compare-and-swap minimal**

Tambahkan method public berikut ke `VPeopleAccountService`; gunakan `Crypt::decryptString`, query `get()` untuk memastikan tepat satu akun, `strtolower(trim())` untuk email, serta `where('password', snapshot)` saat update. `replacePasswordHash()` mengubah `password` dan `updated_at`; `restorePasswordHash()` hanya memulihkan jika hash saat ini sama dengan hash gagal dan mengembalikan `updated_at` snapshot. Jika jumlah baris bukan satu, lempar `VPeopleAccountException` tanpa menyertakan NIK/hash.

```php
public function passwordResetAccount(User $user): array
{
    if (!$user->vpeople_nik_encrypted) {
        throw new VPeopleAccountException('Akun Vitae belum terhubung ke V-People.');
    }

    try {
        $nik = Crypt::decryptString($user->vpeople_nik_encrypted);
    } catch (Throwable $exception) {
        throw new VPeopleAccountException('Data penghubung akun V-People tidak valid.');
    }

    $accounts = DB::connection('vpeople')->table('users')
        ->where('nik_karyawan', $nik)
        ->get(['id', 'email', 'password', 'updated_at']);

    if ($accounts->count() !== 1) {
        throw new VPeopleAccountException('Akun V-People tidak ditemukan atau tidak unik.');
    }

    $account = $accounts->first();
    if (strtolower(trim((string) $account->email)) !== strtolower(trim((string) $user->email))) {
        throw new VPeopleAccountException('Email akun Vitae dan V-People tidak sesuai.');
    }

    return [
        'id' => (string) $account->id,
        'password' => (string) $account->password,
        'updated_at' => $account->updated_at,
    ];
}

public function replacePasswordHash(array $account, string $newHash): void
{
    $updated = DB::connection('vpeople')->table('users')
        ->where('id', $account['id'])
        ->where('password', $account['password'])
        ->update(['password' => $newHash, 'updated_at' => now()]);

    if ($updated !== 1) {
        throw new VPeopleAccountException('Password V-People berubah bersamaan atau tidak dapat diperbarui.');
    }
}

public function restorePasswordHash(array $account, string $failedHash): void
{
    $updated = DB::connection('vpeople')->table('users')
        ->where('id', $account['id'])
        ->where('password', $failedHash)
        ->update([
            'password' => $account['password'],
            'updated_at' => $account['updated_at'],
        ]);

    if ($updated !== 1) {
        throw new VPeopleAccountException('Pemulihan password V-People gagal.');
    }
}
```

- [ ] **Step 4: Jalankan unit test dan pastikan GREEN**

Run: `php artisan test tests/Unit/VPeoplePasswordSyncTest.php`

Expected: 4 tests PASS.

- [ ] **Step 5: Commit batas layanan**

```bash
git add app/Services/VPeopleAccountService.php tests/Unit/VPeoplePasswordSyncTest.php
git commit -m "feat: add vpeople password sync primitives"
```

### Task 2: Orkestrator reset dua database dan rollback kompensasi

**Files:**
- Create: `app/Services/SynchronizedPasswordResetService.php`
- Modify: `tests/Unit/VPeoplePasswordSyncTest.php`

**Interfaces:**
- Consumes: tiga method Task 1, `Hash::make`, local user connection/table.
- Produces: `reset(User $user, string $plainPassword): User`.

- [ ] **Step 1: Tambahkan failing test hash identik dan rollback**

Perluas setup test dengan koneksi SQLite default, schema user lokal, dan instance User persisted. Tambahkan dua test: reset berhasil menghasilkan hash identik serta `Hash::check()` true; trigger SQLite yang menggagalkan `UPDATE OF password ON users` menyebabkan exception dan hash V-People kembali ke nilai lama. Assertion inti:

```php
public function test_synchronized_reset_writes_one_hash_to_both_databases(): void
{
    $user = $this->localUser();
    DB::connection('vpeople')->table('users')->insert($this->account());

    $result = $this->resetService()->reset($user, 'Password-Baru-123');
    $localHash = $result->fresh()->password;
    $externalHash = DB::connection('vpeople')->table('users')->value('password');

    $this->assertSame($localHash, $externalHash);
    $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Password-Baru-123', $localHash));
}

public function test_local_write_failure_restores_vpeople_hash(): void
{
    $user = $this->localUser();
    DB::connection('vpeople')->table('users')->insert($this->account());
    DB::statement("CREATE TRIGGER fail_local_password_update BEFORE UPDATE OF password ON users BEGIN SELECT RAISE(ABORT, 'local write failed'); END");

    try {
        $this->resetService()->reset($user, 'Password-Baru-123');
        $this->fail('Reset seharusnya gagal.');
    } catch (\Throwable $exception) {
        $this->assertSame('old-hash', DB::connection('vpeople')->table('users')->value('password'));
        $this->assertSame('local-old-hash', $user->fresh()->password);
    }
}
```

- [ ] **Step 2: Jalankan test dan pastikan RED**

Run: `php artisan test tests/Unit/VPeoplePasswordSyncTest.php`

Expected: FAIL karena `SynchronizedPasswordResetService` belum ada.

- [ ] **Step 3: Implementasikan orkestrator minimal**

Buat `SynchronizedPasswordResetService` dengan dependency `VPeopleAccountService`. Ambil snapshot akun dan hash lokal, buat hash baru satu kali, update V-People terlebih dahulu, lalu update local menggunakan kondisi `id + old password`. Jika local update melempar exception atau jumlah baris bukan satu, panggil restore eksternal. Jika restore juga gagal, catat `Log::critical` hanya dengan `user_id` dan kelas exception lalu lempar `VPeopleAccountException` generik. Setelah sukses, set atribut model dari database dan return user.

```php
<?php

namespace App\Services;

use App\Exceptions\VPeopleAccountException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SynchronizedPasswordResetService
{
    private $vpeopleAccounts;

    public function __construct(VPeopleAccountService $vpeopleAccounts)
    {
        $this->vpeopleAccounts = $vpeopleAccounts;
    }

    public function reset(User $user, string $plainPassword): User
    {
        $account = $this->vpeopleAccounts->passwordResetAccount($user);
        $oldLocalHash = (string) $user->password;
        $newHash = Hash::make($plainPassword);
        $this->vpeopleAccounts->replacePasswordHash($account, $newHash);

        try {
            $updated = DB::connection($user->getConnectionName())
                ->table($user->getTable())
                ->where($user->getKeyName(), $user->getKey())
                ->where('password', $oldLocalHash)
                ->update([
                    'password' => $newHash,
                    'remember_token' => Str::random(60),
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new RuntimeException('Local password changed concurrently.');
            }
        } catch (Throwable $localException) {
            try {
                $this->vpeopleAccounts->restorePasswordHash($account, $newHash);
            } catch (Throwable $restoreException) {
                Log::critical('V-People password compensation failed.', [
                    'user_id' => $user->getKey(),
                    'exception' => get_class($restoreException),
                ]);

                throw new VPeopleAccountException('Sinkronisasi password gagal dipulihkan.', 0, $restoreException);
            }

            throw $localException;
        }

        return $user->fresh();
    }
}
```

- [ ] **Step 4: Jalankan seluruh unit test sync**

Run: `php artisan test tests/Unit/VPeoplePasswordSyncTest.php`

Expected: seluruh test PASS tanpa warning.

- [ ] **Step 5: Commit orkestrator**

```bash
git add app/Services/SynchronizedPasswordResetService.php tests/Unit/VPeoplePasswordSyncTest.php
git commit -m "feat: synchronize password reset across databases"
```

### Task 3: HTTP password broker, notifikasi, dan token

**Files:**
- Create: `app/Http/Controllers/Auth/ForgotPasswordController.php`
- Create: `app/Http/Controllers/Auth/ResetPasswordController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/PasswordResetTest.php`

**Interfaces:**
- Consumes: `Password::sendResetLink`, `Password::reset`, `SynchronizedPasswordResetService::reset`.
- Produces: route `password.request`, `password.email`, `password.reset`, dan `password.update`.

- [ ] **Step 1: Tulis failing feature test request link**

Setup `PasswordResetTest` menggunakan dua SQLite in-memory connection dan schema minimal `users`, `password_resets`, serta `vpeople.users`. Gunakan `Notification::fake()`. Test GET request form, POST email user mengirim `Illuminate\Auth\Notifications\ResetPassword`, POST email asing memberikan flash success netral yang sama, dan route POST memiliki throttle. Ambil token dengan `Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) { $token = $notification->token; return true; });`.

- [ ] **Step 2: Jalankan test request link dan pastikan RED**

Run: `php artisan test tests/Feature/PasswordResetTest.php --filter=request`

Expected: FAIL karena route/controller belum ada.

- [ ] **Step 3: Tambahkan route dan ForgotPasswordController**

Tambahkan import controller dan route berikut di group `guest`:

```php
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:5,1')
    ->name('password.update');
```

Controller forgot melakukan validasi `required|email|max:255`, normalisasi lowercase/trim, memanggil broker, selalu redirect kembali dengan success netral untuk `RESET_LINK_SENT`, `INVALID_USER`, dan `RESET_THROTTLED`, serta hanya mengembalikan error umum jika pengiriman melempar exception. Log hanya kelas exception.

- [ ] **Step 4: Jalankan test request link dan pastikan GREEN**

Run: `php artisan test tests/Feature/PasswordResetTest.php --filter=request`

Expected: test request link PASS.

- [ ] **Step 5: Tulis failing feature test penyelesaian reset**

Tambahkan test berikut menggunakan token nyata dari `Password::createToken($user)`:

- token salah menghasilkan error `email` dan tidak mengubah dua hash;
- password kurang dari 8 karakter atau confirmation berbeda gagal validasi;
- user tanpa `vpeople_nik_encrypted` mendapatkan flash error, hash lokal tetap, dan token tetap valid melalui `Password::tokenExists($user, $token)`;
- akun V-People tidak ditemukan/email berbeda menghasilkan hasil yang sama;
- reset sukses redirect login, dua hash identik, `Hash::check(password baru)` true, password lama false, dan token tidak berlaku;
- trigger kegagalan update lokal memulihkan hash V-People dan token tetap valid.

- [ ] **Step 6: Jalankan test penyelesaian dan pastikan RED**

Run: `php artisan test tests/Feature/PasswordResetTest.php --filter=reset`

Expected: FAIL karena `ResetPasswordController` belum tersedia.

- [ ] **Step 7: Implementasikan ResetPasswordController**

`showResetForm()` mengirim `token` dan query `email` ke view. `reset()` memvalidasi token/email/password/confirmed/min:8. Panggil `Password::reset()` dengan callback yang menjalankan synchronizer dan menyimpan hasil user ke variabel closure. Tangkap `VPeopleAccountException` sebagai flash error aman, tangkap `Throwable` lain dengan log `user/email` tidak boleh dimasukkan; log hanya kelas exception. Jika status bukan `PASSWORD_RESET`, kembalikan error generik token/email. Setelah sukses dispatch `Illuminate\Auth\Events\PasswordReset` dan redirect login.

```php
$resetUser = null;
try {
    $status = Password::reset($credentials, function (User $user, string $password) use ($passwords, &$resetUser) {
        $resetUser = $passwords->reset($user, $password);
    });
} catch (VPeopleAccountException $exception) {
    return back()->withInput($request->only('email'))->with('error', 'Password tidak dapat direset karena akun V-People tidak valid atau sedang tidak tersedia.');
} catch (Throwable $exception) {
    Log::warning('Synchronized password reset failed.', ['exception' => get_class($exception)]);
    return back()->withInput($request->only('email'))->with('error', 'Reset password sedang tidak tersedia. Silakan coba lagi.');
}

if ($status !== Password::PASSWORD_RESET || !$resetUser) {
    return back()->withInput($request->only('email'))->withErrors(['email' => 'Tautan reset password tidak valid atau sudah kedaluwarsa.']);
}

event(new PasswordReset($resetUser));
return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan masuk dengan password baru.');
```

- [ ] **Step 8: Jalankan feature test dan pastikan GREEN**

Run: `php artisan test tests/Feature/PasswordResetTest.php`

Expected: seluruh test token, sinkronisasi, dan rollback PASS.

- [ ] **Step 9: Commit HTTP flow**

```bash
git add app/Http/Controllers/Auth/ForgotPasswordController.php app/Http/Controllers/Auth/ResetPasswordController.php routes/web.php tests/Feature/PasswordResetTest.php
git commit -m "feat: add secure password reset flow"
```

### Task 4: UI reset password dan feedback

**Files:**
- Modify: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/forgot-password.blade.php`
- Create: `resources/views/auth/reset-password.blade.php`
- Modify: `tests/Feature/PasswordResetTest.php`

**Interfaces:**
- Consumes: empat route Task 3 dan `public/js/password-toggle.js`.
- Produces: form guest responsif dengan loading state, error fields, dan password toggles aksesibel.

- [ ] **Step 1: Tulis failing UI assertions**

Tambahkan assertion bahwa login memuat teks `Lupa password?` dan URL route request; forgot form memiliki field email, CSRF, action `password.email`, `autocomplete=email`, dan loading text; reset form memiliki hidden token, email readonly dari query, dua password field, dua toggle aksesibel, CSRF, action `password.update`, dan loading text.

- [ ] **Step 2: Jalankan test UI dan pastikan RED**

Run: `php artisan test tests/Feature/PasswordResetTest.php --filter=form`

Expected: FAIL karena view belum ada.

- [ ] **Step 3: Implementasikan tiga perubahan Blade**

Gunakan `@extends('layouts.app')`, partial `partials.page-header`, wrapper `auth-wrap`, `app-card`, Bootstrap validation, dan pola tombol yang sama dengan login/register. Tambahkan link lupa password di dekat checkbox Ingat saya. Forgot form menampilkan penjelasan respons netral. Reset form menyertakan:

```blade
<input type="hidden" name="token" value="{{ $token }}">
<input type="email" id="email" name="email" value="{{ old('email', $email) }}" readonly>
```

Password dan confirmation menggunakan `autocomplete="new-password"`, `data-password-toggle`, `data-password-target`, `aria-label`, serta script `VersionedAsset::url('js/password-toggle.js')`. Semua tombol submit memiliki `data-loading-text`.

- [ ] **Step 4: Jalankan UI dan seluruh feature test**

Run: `php artisan test tests/Feature/PasswordResetTest.php`

Expected: seluruh test PASS.

- [ ] **Step 5: Commit UI**

```bash
git add resources/views/auth/login.blade.php resources/views/auth/forgot-password.blade.php resources/views/auth/reset-password.blade.php tests/Feature/PasswordResetTest.php
git commit -m "feat: add password reset screens"
```

### Task 5: Verifikasi regresi dan pemeriksaan production

**Files:**
- Verify only; tidak ada file baru kecuali perbaikan yang dibuktikan test gagal.

**Interfaces:**
- Consumes: seluruh fitur Tasks 1–4.
- Produces: bukti test, route, style, dan konfigurasi production siap.

- [ ] **Step 1: Jalankan test fokus**

Run: `php artisan test tests/Unit/VPeoplePasswordSyncTest.php tests/Feature/PasswordResetTest.php`

Expected: semua test PASS, tanpa warning/error.

- [ ] **Step 2: Jalankan seluruh suite**

Run: `php artisan test`

Expected: seluruh suite PASS. Jika test lama gagal, bedakan regresi dari kegagalan environment dan jangan mengubah test lama tanpa root cause.

- [ ] **Step 3: Periksa route dan diff**

Run: `php artisan route:list --name=password`

Expected: empat route password dengan middleware `web`, `guest`, dan throttle pada dua POST.

Run: `git diff --check`

Expected: tidak ada whitespace error.

- [ ] **Step 4: Pemeriksaan konfigurasi production**

Pastikan mail transport dapat mengirim, `APP_URL` menggunakan URL HTTPS yang benar, tabel `password_resets` tersedia, dan user koneksi V-People memiliki izin `SELECT` serta `UPDATE` pada `users`. Jangan menampilkan isi credential saat pemeriksaan.

- [ ] **Step 5: Commit koreksi verifikasi jika ada**

Jika verifikasi memerlukan koreksi yang dibuktikan failing test, commit hanya file terkait dengan pesan `fix: harden synchronized password reset`. Jika tidak ada koreksi, jangan buat commit kosong.
