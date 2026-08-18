@extends('layouts.app')

@section('title', 'Lupa Password - Vitae')

@section('content')
<div class="auth-wrap">
    @include('partials.page-header', [
    'eyebrow' => 'Pemulihan Akun',
    'title' => 'Lupa Password?',
    'subtitle' => 'Masukkan email akun Anda untuk menerima tautan pengaturan ulang password.',
    ])

    <div class="app-card">
        <div class="app-card-body">
            <p class="text-muted mb-4">
                Anda akan menerima tautan untuk mengatur ulang password, periksa folder spam jika tidak menemukan email di kotak masuk.
            </p>

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">
                        Email akun <span class="text-danger">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        autocomplete="email"
                        aria-describedby="email-help @error('email') email-error @enderror"
                        required>
                    <div id="email-help" class="form-text">Gunakan alamat email yang dipakai untuk masuk ke Vitae.</div>
                    @error('email')
                    <div id="email-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100" data-loading-text="Mengirim tautan reset...">
                    <i class="bi bi-send me-1" aria-hidden="true"></i>Kirim tautan reset
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted mt-3 mb-0">
        Ingat password Anda?
        <a href="{{ route('login') }}" class="fw-bold">Masuk di sini</a>
    </p>
</div>
@endsection