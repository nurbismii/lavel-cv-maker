<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Vitae'))</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/cv-hris-icon.svg') }}">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ file_exists(public_path('mix-manifest.json')) ? mix('css/app.css') : asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body>
    <div class="app-shell">
        <nav class="app-navbar navbar navbar-expand-lg">
            <div class="container-fluid app-container">
                <a class="navbar-brand app-brand" href="{{ url('/') }}">
                    <span class="app-brand-mark">
                        <img src="{{ asset('images/cv-hris-icon.svg') }}" alt="" class="app-brand-icon">
                    </span>
                    <span>
                        <span class="app-brand-title">Vitae</span>
                        <span class="app-brand-subtitle">V-People Integrated</span>
                    </span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appNavbar" aria-controls="appNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="appNavbar">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                                </button>
                            </form>
                        </li>
                        @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm" href="{{ route('register') }}">
                                <i class="bi bi-person-plus me-1"></i> Daftar
                            </a>
                        </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <main class="app-main">
            <div class="container-fluid app-container">
                @if (session('success'))
                <div class="alert alert-success app-alert" role="alert">
                    <strong>Berhasil.</strong>
                    <div>{{ session('success') }}</div>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger app-alert" role="alert">
                    <strong>Gagal.</strong>
                    <div>{{ session('error') }}</div>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>
    <script src="{{ file_exists(public_path('mix-manifest.json')) ? mix('js/app.js') : asset('js/app.js') }}"></script>
    @stack('scripts')
</body>

</html>
