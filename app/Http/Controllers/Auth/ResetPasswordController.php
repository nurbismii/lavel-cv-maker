<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\VPeopleAccountException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SynchronizedPasswordResetService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request, SynchronizedPasswordResetService $passwords)
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);
        $credentials['email'] = strtolower(trim($credentials['email']));

        $resetUser = null;

        try {
            $status = Password::reset(
                $credentials,
                function (User $user, string $password) use ($passwords, &$resetUser) {
                    $resetUser = $passwords->reset($user, $password);
                }
            );
        } catch (VPeopleAccountException $exception) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Password tidak dapat direset karena akun V-People tidak valid atau sedang tidak tersedia.');
        } catch (Throwable $exception) {
            Log::warning('Synchronized password reset failed.', [
                'exception' => get_class($exception),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Reset password sedang tidak tersedia. Silakan coba lagi.');
        }

        if ($status !== Password::PASSWORD_RESET || !$resetUser) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Tautan reset password tidak valid atau sudah kedaluwarsa.',
                ]);
        }

        event(new PasswordReset($resetUser));

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil direset. Silakan masuk dengan password baru.');
    }
}
