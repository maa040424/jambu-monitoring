<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Monitoring Kebun Jambu Kristal</title>
    <meta name="description" content="Dashboard IoT untuk monitoring kondisi kebun jambu kristal secara real-time dengan prediksi ARIMA.">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5.3 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- Custom CSS --}}
    <link href="{{ asset('css/dashboard.css') }}?v={{ time() }}" rel="stylesheet">

    {{-- Lottie Player (animasi loading) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-player/2.0.8/lottie-player.js"></script>
</head>
<body>

    {{-- Navbar --}}
    <nav class="navbar-custom" id="main-navbar">
        <div class="nav-container">
            {{-- Top row: Brand + Hamburger --}}
            <div class="nav-top">
                <a class="nav-brand" href="{{ route('dashboard') }}">
                    <video class="nav-mascot" autoplay loop muted playsinline>
                        <source src="{{ asset('img/maskot_.mp4') }}" type="video/mp4">
                    </video>
                    <span class="nav-brand-text">Jambu<span class="nav-brand-accent">Monitor</span></span>
                </a>

                <button class="nav-hamburger" id="nav-hamburger" onclick="toggleNavMenu()" aria-label="Toggle menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>

            {{-- Navigation content --}}
            <div class="nav-content" id="nav-content">
                {{-- Navigation links --}}
                <div class="nav-links">
                    <a href="{{ route('dashboard') }}" class="nav-link-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-display"></i>
                        <span>Realtime</span>
                    </a>
                    <a href="{{ route('forecast') }}" class="nav-link-item {{ request()->routeIs('forecast') ? 'active' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Prediksi ARIMA</span>
                    </a>
                    <a href="{{ route('profil.kebun') }}" class="nav-link-item {{ request()->routeIs('profil.kebun') ? 'active' : '' }}">
                        <i class="bi bi-tree-fill"></i>
                        <span>Profil Kebun</span>
                    </a>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('users.index') }}" class="nav-link-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span>Kelola User</span>
                    </a>
                    @endif
                </div>

                {{-- Right side: controls --}}
                <div class="nav-controls">
                    {{-- Theme toggle --}}
                    <button class="nav-btn nav-btn-ghost" id="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                        <i class="bi theme-icon" id="theme-icon"></i>
                        <span id="theme-label">Dark</span>
                    </button>

                    {{-- Mode badge --}}
                    <a href="{{ route('mode.select') }}" class="nav-badge nav-badge-mode {{ session('data_mode', 'dummy') === 'dummy' ? 'mode-dummy' : 'mode-real' }}" id="mode-badge" title="Klik untuk ganti mode">
                        {{ session('data_mode', 'dummy') === 'dummy' ? '🧪 Dummy' : '📡 Real' }}
                    </a>

                    {{-- Connection status --}}
                    <span class="nav-badge nav-badge-live" id="connection-badge">
                        <i class="bi bi-circle-fill pulse-dot"></i> Live
                    </span>

                    {{-- Device status (ESP32 di kebun) --}}
                    <span class="nav-badge nav-badge-device" id="device-status-badge" title="Status alat ESP32 di kebun">
                        <i class="bi bi-cpu"></i> <span id="device-status-text">Cek...</span>
                    </span>

                    {{-- Live clock --}}
                    <span class="nav-clock" id="nav-clock" title="Waktu saat ini (WIB)">
                        <i class="bi bi-clock"></i>
                        <span id="nav-clock-time">--:--:--</span>
                    </span>

                    {{-- Divider (desktop only) --}}
                    <div class="nav-divider"></div>

                    {{-- User info --}}
                    @auth
                    <div class="nav-user">
                        <div class="nav-user-info">
                            <span class="nav-user-name">{{ auth()->user()->name }}</span>
                            @php
                                $roleClass = 'role-user';
                                $roleIcon = 'bi-person-fill';
                                if (auth()->user()->role === 'superadmin') {
                                    $roleClass = 'role-superadmin';
                                    $roleIcon = 'bi-shield-fill-check';
                                } elseif (auth()->user()->role === 'admin') {
                                    $roleClass = 'role-admin';
                                    $roleIcon = 'bi-shield-lock-fill';
                                }
                            @endphp
                            <span class="nav-user-role {{ $roleClass }}">
                                <i class="bi {{ $roleIcon }}"></i>
                                {{ auth()->user()->role === 'superadmin' ? 'Super Admin' : ucfirst(auth()->user()->role) }}
                            </span>
                        </div>
                        <a href="{{ route('password.edit') }}" class="nav-btn nav-btn-ghost" title="Ubah Password" style="font-size: 0.75rem;">
                            <i class="bi bi-key"></i>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-btn nav-btn-logout" title="Logout">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="container-fluid px-3 px-md-4 py-4">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="text-center py-3 mt-4">
        <small class="text-muted">
            &copy; {{ date('Y') }} Monitoring Kebun Jambu Kristal &mdash; IoT Dashboard v1.0
        </small>
    </footer>

    {{-- Chart.js 4 --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Shared JS (theme, chart helpers) --}}
    <script src="{{ asset('js/common.js') }}?v={{ time() }}"></script>

    {{-- Pass server-side data to JS --}}
    <script>
        window.INITIAL_MODE = '{{ session("data_mode", "dummy") }}';
        window.USER_IS_ADMIN = {{ auth()->user()?->isAdmin() ? 'true' : 'false' }};
        window.FORECAST_URL  = '{{ route("forecast") }}';
    </script>






    @yield('scripts')

</body>
</html>
