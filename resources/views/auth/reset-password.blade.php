@extends('layouts.app')

@section('title', 'Reset Password - Vitae')

@section('content')
<div class="auth-wrap">
    @include('partials.page-header', [
    'eyebrow' => 'Pemulihan Akun',
    'title' => 'Buat Password Baru',
    'subtitle' => 'Gunakan password baru yang kuat untuk menjaga keamanan akun Anda.',
    ])

    <div class="app-card">
        <div class="app-card-body">
            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Email akun</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        class="form-control readonly-field @error('email') is-invalid @enderror"
                        autocomplete="email"
                        aria-describedby="email-help @error('email') email-error @enderror"
                        readonly
                        required>
                    <div id="email-help" class="form-text">Email ini berasal dari tautan reset password Anda.</div>
                    @error('email')
                    <div id="email-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password baru <span class="text-danger">*</span>
                    </label>
                    <div class="input-group has-validation">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="new-password"
                            aria-describedby="password-help @error('password') password-error @enderror"
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
                        <div id="password-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div id="password-help" class="form-text">Gunakan minimal 8 karakter.</div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">
                        Konfirmasi password baru <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                            required>
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-password-toggle
                            data-password-target="password_confirmation"
                            aria-label="Tampilkan konfirmasi password"
                            aria-pressed="false"
                            title="Tampilkan konfirmasi password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" data-loading-text="Menyimpan password baru...">
                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Reset password
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted mt-3 mb-0">
        Kembali ke
        <a href="{{ route('login') }}" class="fw-bold">halaman masuk</a>
    </p>
</div>
@endsection

@push('scripts')
<script src="{{ \App\Support\VersionedAsset::url('js/password-toggle.js') }}"></script>
@endpush