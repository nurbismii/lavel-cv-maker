<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\VPeopleAccountException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CvUserProvisioningService;
use App\Services\VPeopleAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(
        Request $request,
        VPeopleAccountService $vpeopleAccountService,
        CvUserProvisioningService $provisioningService
    ) {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email atau NIK wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $identifier = strtolower(trim($credentials['email']));
        $remember = $request->filled('remember');

        if (
            filter_var($identifier, FILTER_VALIDATE_EMAIL) &&
            Auth::attempt(['email' => $identifier, 'password' => $credentials['password']], $remember)
        ) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        if ($vpeopleAccountService->loginEnabled()) {
            try {
                $vpeopleLogin = $vpeopleAccountService->authenticate($identifier, $credentials['password']);

                if ($vpeopleLogin) {
                    $user = $this->findOrCreateLocalUserFromVPeopleLogin($vpeopleLogin, $provisioningService);

                    Auth::login($user, $remember);
                    $request->session()->regenerate();

                    if (!$user->hasVerifiedEmail() && $user->wasRecentlyCreated) {
                        $user->sendEmailVerificationNotification();
                    }

                    return redirect()->intended(route('dashboard'));
                }
            } catch (VPeopleAccountException $exception) {
                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors([
                        'email' => $exception->getMessage(),
                    ]);
            } catch (Throwable $exception) {
                Log::warning('V-People login failed.', [
                    'exception' => get_class($exception),
                ]);

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->with('error', 'Login V-People sedang tidak tersedia. Silakan coba beberapa saat lagi.');
            }
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'Email/NIK atau password tidak sesuai.',
            ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil keluar.');
    }

    private function findOrCreateLocalUserFromVPeopleLogin(
        array $vpeopleLogin,
        CvUserProvisioningService $provisioningService
    ): User {
        $account = $vpeopleLogin['account'];
        $employee = $vpeopleLogin['employee'];
        $email = strtolower(trim($account['email']));
        $nikHash = $provisioningService->hashNik($employee['nik']);

        $existingUser = User::where('vpeople_nik_hash', $nikHash)->first();

        if ($existingUser) {
            return $existingUser;
        }

        return DB::transaction(function () use ($email, $employee, $nikHash, $provisioningService) {
            if (User::where('vpeople_nik_hash', $nikHash)->exists()) {
                return User::where('vpeople_nik_hash', $nikHash)->first();
            }

            if (User::where('email', $email)->exists()) {
                throw new VPeopleAccountException('Email V-People sudah digunakan oleh akun CV HRIS lain.');
            }

            return $provisioningService->createFromVPeopleEmployee($employee, $email);
        });
    }
}
