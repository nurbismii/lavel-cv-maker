<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\VPeopleAccountException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterEmployeeRequest;
use App\Models\User;
use App\Services\CvUserProvisioningService;
use App\Services\VPeopleAccountService;
use App\Services\VPeopleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(
        RegisterEmployeeRequest $request,
        VPeopleService $vpeopleService,
        VPeopleAccountService $vpeopleAccountService,
        CvUserProvisioningService $provisioningService
    ) {
        try {
            $employee = $vpeopleService->findActiveEmployeeByNikAndBirthDate(
                $request->input('nik'),
                $request->input('birth_date')
            );
        } catch (Throwable $exception) {
            Log::warning('V-People lookup failed during employee registration.', [
                'exception' => get_class($exception),
            ]);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Validasi data karyawan sedang tidak tersedia. Silakan coba beberapa saat lagi.');
        }

        if (!$employee) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'nik' => 'Data karyawan tidak ditemukan atau tidak sesuai.',
                ]);
        }

        $nikHash = $provisioningService->hashNik($employee['nik']);

        if (User::where('vpeople_nik_hash', $nikHash)->exists()) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'nik' => 'Akun untuk data karyawan ini sudah terdaftar.',
                ]);
        }

        try {
            if ($vpeopleAccountService->accountSyncEnabled()) {
                $vpeopleAccountService->assertAccountCanBeProvisioned($employee, $request->input('email'));
            }
        } catch (VPeopleAccountException $exception) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'email' => $exception->getMessage(),
                ]);
        } catch (Throwable $exception) {
            Log::warning('V-People account preflight failed during registration.', [
                'exception' => get_class($exception),
            ]);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Validasi akun V-People sedang tidak tersedia. Silakan coba beberapa saat lagi.');
        }

        try {
            $user = DB::transaction(function () use ($employee, $request, $provisioningService) {
                return $provisioningService->createFromVPeopleEmployee(
                    $employee,
                    $request->input('email'),
                    $request->input('password')
                );
            });
        } catch (Throwable $exception) {
            Log::warning('Employee account registration failed.', [
                'exception' => get_class($exception),
            ]);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Pembuatan akun sedang tidak tersedia. Silakan coba beberapa saat lagi.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            Log::warning('Email verification notification failed after registration.', [
                'exception' => get_class($exception),
            ]);

            return redirect()
                ->route('verification.notice')
                ->with('error', 'Akun berhasil dibuat, tetapi email verifikasi belum bisa dikirim. Silakan coba kirim ulang.');
        }

        return redirect()
            ->route('verification.notice')
            ->with('success', 'Akun Vitae berhasil dibuat. Silakan cek email untuk mengaktifkan akun.');
    }
}
