@extends('layouts.app')

@section('title', 'Verifikasi Email - Vitae')

@section('content')
<div class="auth-wrap">
    @include('partials.page-header', [
    'eyebrow' => 'Verifikasi Email',
    'title' => 'Aktifkan Akun Anda',
    'subtitle' => 'Kami sudah mengirim link verifikasi ke email yang Anda daftarkan. Klik link tersebut untuk mengaktifkan akses Vitae.',
    ])

    <div class="app-card">
        <div class="app-card-body">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="verification-icon">
                    <i class="bi bi-envelope-check"></i>
                </div>
                <div>
                    <h2 class="h5 mb-2">Cek inbox email</h2>
                    <p class="text-muted mb-0">
                        Link verifikasi dikirim ke <strong>{{ auth()->user()->email }}</strong>.
                        Jika tidak terlihat, cek folder spam atau kirim ulang link verifikasi.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary w-100" data-loading-text="Mengirim ulang...">
                    <i class="bi bi-send me-1"></i> Kirim Ulang Link Verifikasi
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection