<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Monitoring Kebun Jambu Kristal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* ── Full-screen background image ── */
        .login-bg {
            position: fixed;
            inset: 0;
            background-image: url('{{ asset("img/background-landingPage.png") }}');
            background-size: cover;
            background-position: center;
            filter: brightness(0.45) saturate(1.1);
            z-index: 0;
            transform: scale(1.04); /* sedikit zoom agar tidak ada pinggiran putih */
            transition: transform 20s ease;
        }

        /* Overlay gradient gelap — biar card terbaca */
        .login-overlay {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 70% at 50% 50%, rgba(11,17,32,0.55) 0%, rgba(11,17,32,0.82) 100%);
            z-index: 1;
        }

        /* Ambient green glow di tengah */
        .login-glow {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 500px 400px at 50% 50%, rgba(34,197,94,0.08) 0%, transparent 70%);
            z-index: 1;
            pointer-events: none;
            animation: glow-pulse 6s ease-in-out infinite alternate;
        }

        @keyframes glow-pulse {
            0%   { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* ── Centering wrapper ── */
        .login-page {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        /* ── Glass card ── */
        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem 2.2rem;
            box-shadow:
                0 8px 40px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        /* ── Brand header ── */
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-brand .mascot-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 0.85rem;
            border: 2px solid rgba(34, 197, 94, 0.45);
            box-shadow: 0 0 24px rgba(34, 197, 94, 0.25);
            animation: float 4s ease-in-out infinite;
            background: #1e293b;
        }

        .login-brand .mascot-wrap video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-6px); }
        }

        .login-brand h1 {
            font-size: 1.15rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            line-height: 1.3;
        }

        .login-brand p {
            font-size: 0.82rem;
            color: #64748b;
            margin: 0.3rem 0 0;
        }

        /* ── Form fields ── */
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f1f5f9;
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
            font-size: 0.92rem;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            border-color: rgba(34, 197, 94, 0.6);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
            background: rgba(15, 23, 42, 0.85);
            color: #f1f5f9;
            outline: none;
        }

        .form-control::placeholder { color: #475569; }

        .form-check-input {
            background-color: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.2);
        }

        .form-check-input:checked {
            background-color: #22c55e;
            border-color: #22c55e;
        }

        .form-check-label {
            color: #64748b;
            font-size: 0.84rem;
        }

        /* Password toggle button */
        .btn-outline-secondary {
            background: rgba(15, 23, 42, 0.7) !important;
            border-color: rgba(255,255,255,0.12) !important;
            color: #64748b !important;
            border-radius: 0 10px 10px 0 !important;
            transition: all 0.2s ease;
        }

        .btn-outline-secondary:hover {
            color: #94a3b8 !important;
            background: rgba(255,255,255,0.08) !important;
        }

        /* ── Login button ── */
        .btn-login {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            color: #fff;
            font-weight: 700;
            padding: 0.72rem;
            border-radius: 10px;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 18px rgba(34, 197, 94, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #16a34a, #15803d);
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(34, 197, 94, 0.4);
            color: #fff;
        }

        .btn-login:active { transform: translateY(0); }

        /* ── Alerts ── */
        .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 10px;
            font-size: 0.84rem;
            backdrop-filter: blur(8px);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
            border-radius: 10px;
            font-size: 0.84rem;
        }

        /* ── Footer links ── */
        .contact-admin {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.82rem;
            color: #475569;
        }

        .contact-admin a {
            color: #25d366;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .contact-admin a:hover { color: #4ade80; }
        .contact-admin a i { margin-right: 2px; }

        /* Divider */
        .login-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.07);
            margin: 1.5rem 0;
        }

        /* ── Bottom watermark ── */
        .login-watermark {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.3px;
        }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
                border-radius: 18px;
            }
            .login-brand .mascot-wrap { width: 60px; height: 60px; }
            .login-brand h1 { font-size: 1rem; }
        }
    </style>
</head>
<body>
    {{-- Background foto kebun --}}
    <div class="login-bg"></div>
    <div class="login-overlay"></div>
    <div class="login-glow"></div>

    {{-- Login form --}}
    <div class="login-page">
        <div class="login-wrapper">
            <div class="login-card">
                {{-- Brand --}}
                <div class="login-brand">
                    <div class="mascot-wrap">
                        <video autoplay loop muted playsinline>
                            <source src="{{ asset('img/maskot.mp4') }}" type="video/mp4">
                        </video>
                    </div>
                    <h1>Monitoring Kebun Jambu Kristal</h1>
                    <p>Silakan login untuk melanjutkan</p>
                </div>

                {{ $slot }}
            </div>

            <p class="login-watermark">
                &copy; {{ date('Y') }} Monitoring Kebun Jambu Kristal &mdash; IoT Dashboard
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePasswordVisibility(inputId, btnId) {
        const input = document.getElementById(inputId);
        const btn   = document.getElementById(btnId);
        if (!input || !btn) return;
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    </script>
</body>
</html>
