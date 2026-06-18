@php
$displayName = trim((string) $user->name) ?: 'Karyawan';
$preview = 'Verifikasi email Anda untuk mengaktifkan akun Vitae.';
@endphp
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email {{ $appName }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell {
                width: 100% !important;
            }

            .email-card {
                border-radius: 0 !important;
            }

            .email-padding {
                padding: 24px !important;
            }

            .email-title {
                font-size: 24px !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background:#f8fafc; color:#0f172a; font-family:Arial, Helvetica, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">
        {{ $preview }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%; background:#f8fafc; margin:0; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" class="email-shell" width="600" cellspacing="0" cellpadding="0" style="width:600px; max-width:600px;">
                    <tr>
                        <td style="padding:0 0 16px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="48" style="width:48px;">
                                        <div style="width:44px; height:44px; border-radius:14px; background:#0f766e; color:#ffffff; font-size:16px; font-weight:800; line-height:44px; text-align:center; letter-spacing:.02em;">
                                            CV
                                        </div>
                                    </td>
                                    <td style="padding-left:12px;">
                                        <div style="font-size:16px; font-weight:800; color:#0f172a; line-height:1.2;">
                                            {{ $appName }}
                                        </div>
                                        <div style="font-size:12px; font-weight:700; color:#64748b; line-height:1.3; margin-top:2px;">
                                            V-People Integrated
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden; box-shadow:0 12px 30px rgba(15, 23, 42, .06);">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="height:6px; background:#0f766e; line-height:6px; font-size:0;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="email-padding" style="padding:34px 36px 30px 36px;">
                                        <div style="display:inline-block; margin:0 0 14px 0; padding:7px 11px; border-radius:999px; background:#ecfdf5; color:#0f766e; border:1px solid rgba(15,118,110,.18); font-size:12px; font-weight:800; letter-spacing:.04em; text-transform:uppercase;">
                                            Verifikasi Email
                                        </div>

                                        <h1 class="email-title" style="margin:0 0 12px 0; color:#0f172a; font-size:28px; line-height:1.2; font-weight:800; letter-spacing:0;">
                                            Aktifkan akun Anda
                                        </h1>

                                        <p style="margin:0 0 18px 0; color:#334155; font-size:15px; line-height:1.65;">
                                            Halo <strong>{{ $displayName }}</strong>, email ini digunakan untuk pendaftaran akun {{ $appName }}. Klik tombol di bawah untuk memastikan email ini benar milik Anda.
                                        </p>

                                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:26px 0 24px 0;">
                                            <tr>
                                                <td align="center" style="border-radius:12px; background:#0f766e;">
                                                    <a href="{{ $verificationUrl }}" style="display:inline-block; padding:14px 22px; color:#ffffff; background:#0f766e; border:1px solid #0f766e; border-radius:12px; font-size:15px; font-weight:800; text-decoration:none;">
                                                        Verifikasi Email
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 22px 0;">
                                            <tr>
                                                <td style="padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
                                                    <div style="font-size:13px; line-height:1.55; color:#475569;">
                                                        Link ini berlaku selama <strong>{{ $expirationMinutes }} menit</strong>. Setelah email terverifikasi, akses dashboard dan fitur Vitae akan aktif.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:0 0 10px 0; color:#64748b; font-size:13px; line-height:1.6;">
                                            Jika tombol tidak berfungsi, salin dan buka link berikut di browser:
                                        </p>

                                        <p style="margin:0; padding:12px 14px; background:#f1f5f9; border-radius:10px; color:#115e59; font-size:12px; line-height:1.5; word-break:break-all;">
                                            <a href="{{ $verificationUrl }}" style="color:#115e59; text-decoration:underline;">{{ $verificationUrl }}</a>
                                        </p>

                                        <p style="margin:22px 0 0 0; color:#64748b; font-size:13px; line-height:1.6;">
                                            Jika Anda tidak merasa mendaftar akun {{ $appName }}, abaikan email ini. Akun tidak akan aktif tanpa verifikasi email.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:18px 12px 0 12px; color:#94a3b8; font-size:12px; line-height:1.6;">
                            Email otomatis dari {{ $appName }}. Jangan membalas email ini.
                            @if ($supportEmail)
                            <br>Pengirim: {{ $supportEmail }}
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>