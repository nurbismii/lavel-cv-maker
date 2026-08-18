@extends('layouts.app')

@section('title', 'Masuk - Vitae')

@section('content')
<div class="auth-wrap">
    @include('partials.page-header', [
    'eyebrow' => 'Vitae',
    'title' => 'Masuk ke Akun',
    'subtitle' => 'Gunakan email Vitae atau akun V-People yang sudah aktif.',
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
                        required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group has-validation">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="current-password"
                            required>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-password-toggle
                            data-password-target="password"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                            title="Tampilkan password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            Ingat saya
                        </label>
                    </div>
                    <a href="{{ route('password.request') }}" class="btn btn-link link-primary fw-semibold d-inline-flex align-items-center px-2 py-2" style="min-height: 44px">
                        <i class="bi bi-key me-1" aria-hidden="true"></i>Lupa password?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary w-100" data-loading-text="Memeriksa akun...">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted mt-3 mb-0">
        Belum punya akun?
        <a href="{{ route('register') }}" class="fw-bold">Daftar </a>
    </p>
</div>
@endsection

@push('scripts')
<script src="{{ \App\Support\VersionedAsset::url('js/password-toggle.js') }}"></script>
@endpush