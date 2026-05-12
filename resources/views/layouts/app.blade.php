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
                            <span class="nav-user-role {{ auth()->user()->isAdmin() ? 'role-admin' : 'role-user' }}">
                                <i class="bi {{ auth()->user()->isAdmin() ? 'bi-shield-lock-fill' : 'bi-person-fill' }}"></i>
                                {{ ucfirst(auth()->user()->role) }}
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

    {{-- Forecast Nav Transition — overlay muncul saat klik menu Prediksi ARIMA --}}
    @if(!request()->routeIs('forecast'))
    <style>
        #forecast-nav-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(11, 17, 32, 0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 2rem;
            text-align: center;
            animation: fno-in 0.3s ease forwards;
        }

        @keyframes fno-in {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .fno-lottie-wrap {
            width: clamp(180px, 35vw, 260px);
            height: clamp(180px, 35vw, 260px);
            filter: drop-shadow(0 0 30px rgba(34,197,94,0.2));
            animation: glow-fno 2.5s ease-in-out infinite alternate;
        }

        @keyframes glow-fno {
            0%   { filter: drop-shadow(0 0 20px rgba(34,197,94,0.14)); }
            100% { filter: drop-shadow(0 0 45px rgba(34,197,94,0.28)); }
        }


        .fno-title {
            font-size: clamp(1rem, 3vw, 1.35rem);
            font-weight: 800;
            background: linear-gradient(135deg, #4ade80, #22c55e, #86efac);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .fno-sub {
            font-size: 0.85rem;
            color: #64748b;
        }

        .fno-dots {
            display: flex; gap: 0.5rem; justify-content: center; margin-top: 0.5rem;
        }

        .fno-dots span {
            width: 7px; height: 7px; border-radius: 50%; background: #22c55e;
            animation: bd 1.4s ease-in-out infinite;
        }

        .fno-dots span:nth-child(2) { animation-delay:.2s; background:#4ade80; }
        .fno-dots span:nth-child(3) { animation-delay:.4s; background:#86efac; }

        @keyframes bd {
            0%,80%,100% { transform:scale(.6); opacity:.4; }
            40%          { transform:scale(1.15); opacity:1; }
        }
    </style>

    {{-- Overlay element --}}
    <div id="forecast-nav-overlay">
        <div class="fno-lottie-wrap">
            <lottie-player
                src="{{ asset('img/ARIMA_animation.json') }}"
                background="transparent"
                speed="1"
                loop autoplay
                style="width: 100%; height: 100%;">
            </lottie-player>
        </div>
        <div>
            <div class="fno-title">Menghitung Prediksi ARIMA</div>
            <div class="fno-sub">Membuka halaman prediksi...</div>
            <div class="fno-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <script>
    // Intercept klik nav link "Prediksi ARIMA" — tampilkan overlay sebelum navigate
    document.addEventListener('DOMContentLoaded', () => {
        const forecastUrl = window.FORECAST_URL || '/forecast';

        document.querySelectorAll('a[href]').forEach(link => {
            try {
                const href = new URL(link.href, location.origin).pathname;
                const target = new URL(forecastUrl, location.origin).pathname;
                if (href === target) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const overlay = document.getElementById('forecast-nav-overlay');
                        if (overlay) {
                            overlay.style.display = 'flex';
                        }
                        // Navigate setelah overlay muncul sebentar
                        setTimeout(() => {
                            window.location.href = link.href;
                        }, 400);
                    });
                }
            } catch (_) {}
        });
    });
    </script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    @endif

    @yield('scripts')

</body>
</html>
