<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\VPeopleAccountException;
use App\Http\Controllers\Controller;
use App\Services\VPeopleAccountService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        if (request()->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request, VPeopleAccountService $vpeopleAccountService)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        if ($vpeopleAccountService->accountSyncEnabled()) {
            try {
                $vpeopleAccountService->ensureAccountForUser($request->user());
            } catch (VPeopleAccountException $exception) {
                return redirect()
                    ->route('verification.notice')
                    ->with('error', $exception->getMessage());
            } catch (Throwable $exception) {
                Log::warning('V-People account sync failed during email verification.', [
                    'exception' => get_class($exception),
                ]);

                return redirect()
                    ->route('verification.notice')
                    ->with('error', 'Sinkronisasi akun V-People sedang tidak tersedia. Silakan coba beberapa saat lagi.');
            }
        }

        $request->fulfill();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Email berhasil diverifikasi. Akun Anda sudah aktif.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            Log::warning('Email verification notification resend failed.', [
                'exception' => get_class($exception),
            ]);

            return back()->with('error', 'Email verifikasi belum bisa dikirim. Silakan coba beberapa saat lagi.');
        }

        return back()->with('success', 'Link verifikasi baru sudah dikirim ke email Anda.');
    }
}
