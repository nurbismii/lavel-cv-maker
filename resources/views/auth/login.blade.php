@extends('layouts.app')

@section('title', 'Masuk - CV HRIS')

@section('content')
    <div class="auth-wrap">
        @include('partials.page-header', [
            'eyebrow' => 'CV HRIS',
            'title' => 'Masuk ke Akun',
            'subtitle' => 'Gunakan email CV HRIS atau akun V-People yang sudah aktif.',
        ])

        <div class="app-card">
            <div class="app-card-body">
                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email atau NIK <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            autocomplete="username"
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password <span class="text-danger">*</span>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" data-loading-text="Memeriksa akun...">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-muted mt-3 mb-0">
            Belum punya akun?
            <a href="{{ route('register') }}" class="fw-bold">Daftar dengan V-People</a>
        </p>
    </div>
@endsection
