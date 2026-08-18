<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

class ForgotPasswordController extends Controller
{
    private const NEUTRAL_LINK_MESSAGE = 'Jika email terdaftar, tautan reset password telah dikirim.';

    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = strtolower(trim($validated['email']));

        try {
            Password::sendResetLink(['email' => $email]);
        } catch (Throwable $exception) {
            Log::warning('Password reset link delivery failed.', [
                'exception' => get_class($exception),
            ]);

            return back()->with('success', self::NEUTRAL_LINK_MESSAGE);
        }

        return back()->with('success', self::NEUTRAL_LINK_MESSAGE);
    }
}
