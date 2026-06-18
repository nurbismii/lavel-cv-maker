@extends('layouts.app')

@section('title', 'Daftar Akun Karyawan - CV HRIS')

@section('content')
    <div class="auth-wrap">
        @include('partials.page-header', [
            'eyebrow' => 'Validasi V-People',
            'title' => 'Daftar Akun Karyawan',
            'subtitle' => 'Masukkan NIK dan tanggal lahir sesuai data V-People. Jika cocok, akun CV HRIS dibuat dan perlu diverifikasi lewat email sebelum aktif.',
        ])

        <div class="app-card">
            <div class="app-card-body">
                <form method="POST" action="{{ route('register.store') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="nik" class="form-label">
                            NIK Karyawan <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="nik"
                            name="nik"
                            value="{{ old('nik') }}"
                            class="form-control @error('nik') is-invalid @enderror"
                            autocomplete="off"
                            required
                        >
                        <div class="form-text">NIK digunakan hanya untuk validasi awal ke V-People.</div>
                        @error('nik')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="birth_date" class="form-label">
                            Tanggal Lahir <span class="text-danger">*</span>
                        </label>
                        <input
                            type="date"
                            id="birth_date"
                            name="birth_date"
                            value="{{ old('birth_date') }}"
                            class="form-control @error('birth_date') is-invalid @enderror"
                            required
                        >
                        @error('birth_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email Login <span class="text-danger">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            autocomplete="email"
                            required
                        >
                        <div class="form-text">Email tidak tersedia di V-People, jadi diisi manual untuk login.</div>
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
                            autocomplete="new-password"
                            required
                        >
                        <div class="form-text">Gunakan minimal 8 karakter.</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">
                            Konfirmasi Password <span class="text-danger">*</span>
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100" data-loading-text="Memvalidasi V-People...">
                        <i class="bi bi-shield-check me-1"></i> Validasi dan Buat Akun
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-muted mt-3 mb-0">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="fw-bold">Masuk di sini</a>
        </p>
    </div>
@endsection
