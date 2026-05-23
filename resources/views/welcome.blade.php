<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sistem IoT Monitoring & Prediksi Kebun Jambu Kristal — ARIMA</title>
    <meta name="description" content="Perancangan Sistem IoT untuk Monitoring dan Prediksi Kondisi Lingkungan pada Kebun Jambu Kristal Menggunakan Metode ARIMA.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ═══════════════════════════════════
           Desktop: NO scroll, pas viewport
           Mobile:  boleh scroll jika perlu
        ═══════════════════════════════════ */
        html {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #0b1120;
            color: #e2e8f0;
            /* Desktop — kunci scroll */
            height: 100%;
            overflow: hidden;
        }

        body {
            height: 100%;
            overflow: hidden;
        }

        /* ── HERO: split 50/50 desktop ── */
        .hero {
            width: 100%;
            height: 100vh;          /* desktop */
            height: 100dvh;         /* support dynamic viewport height */
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
            overflow: hidden;
        }

        /* Ambient glow */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 50% 60% at 15% 40%, rgba(34,197,94,0.10) 0%, transparent 70%),
                radial-gradient(ellipse 40% 50% at 85% 60%, rgba(56,189,248,0.07) 0%, transparent 70%);
            animation: pulse-bg 8s ease-in-out infinite alternate;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes pulse-bg {
            0%   { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* ── LEFT: konten ── */
        .hero-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(1.5rem, 4vw, 4rem) clamp(1.5rem, 3vw, 3rem) clamp(1.5rem, 4vw, 4rem) clamp(2rem, 4vw, 4rem);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        /* ── RIGHT: foto ── */
        .hero-right {
            position: relative;
            overflow: hidden;
        }

        .hero-right::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, #0b1120 0%, rgba(11,17,32,0.45) 25%, transparent 60%);
            z-index: 1;
        }

        .hero-right::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(11,17,32,0.55) 0%, transparent 25%, transparent 75%, rgba(11,17,32,0.6) 100%);
            z-index: 1;
        }

        .hero-bg-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            filter: brightness(0.68) saturate(1.15);
            transition: transform 10s ease;
        }

        .hero-right:hover .hero-bg-image { transform: scale(1.04); }

        .img-caption {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            background: rgba(11,17,32,0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(34,197,94,0.22);
            border-radius: 50px;
            padding: 0.4rem 1.1rem;
            font-size: 0.72rem;
            color: #94a3b8;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .img-caption i { color: #4ade80; font-size: 0.75rem; }

        /* ── Mascot ── */
        .hero-mascot {
            width: clamp(60px, 8vw, 100px);
            height: clamp(60px, 8vw, 100px);
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.2rem;
            border: 2px solid rgba(34,197,94,0.4);
            box-shadow: 0 0 28px rgba(34,197,94,0.22), 0 0 56px rgba(34,197,94,0.08);
            animation: float 4s ease-in-out infinite;
            background: #1e293b;
            display: block;
            flex-shrink: 0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }

        /* ── Badge ── */
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            border-radius: 50px;
            padding: 0.28rem 0.85rem;
            font-size: 0.7rem;
            color: #4ade80;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 1rem;
            width: fit-content;
        }

        .hero-badge .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #4ade80;
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.2; }
        }

        /* ── Heading ── */
        .hero h1 {
            font-size: clamp(1.6rem, 3.2vw, 3rem);
            font-weight: 900;
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 50%, #86efac 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.15;
            margin-bottom: 0.75rem;
        }

        .hero .subtitle {
            font-size: clamp(0.8rem, 1.3vw, 1rem);
            color: #94a3b8;
            line-height: 1.65;
            margin-bottom: 1.5rem;
            max-width: 480px;
        }
        .hero .subtitle strong { color: #e2e8f0; }

        /* ── Pills ── */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 0.35rem 0.85rem;
            font-size: 0.75rem;
            color: #cbd5e1;
            transition: all 0.25s ease;
        }
        .pill:hover { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.3); }
        .pill-green i  { color: #4ade80; }
        .pill-blue i   { color: #60a5fa; }
        .pill-orange i { color: #fb923c; }
        .pill-purple i { color: #c084fc; }

        /* ── CTA ── */
        .cta-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.75rem;
        }

        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: clamp(0.6rem, 1.2vw, 0.8rem) clamp(1.2rem, 2vw, 1.8rem);
            border-radius: 10px;
            font-weight: 700;
            font-size: clamp(0.82rem, 1.1vw, 0.95rem);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            border: none;
            cursor: pointer;
        }

        .btn-cta-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            box-shadow: 0 4px 18px rgba(34,197,94,0.3);
        }
        .btn-cta-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(34,197,94,0.45); color: #fff; }

        .btn-cta-secondary {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            color: #cbd5e1;
        }
        .btn-cta-secondary:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25); transform: translateY(-2px); color: #fff; }

        /* ── Stats ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: clamp(0.6rem, 1.5vw, 1.5rem);
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .stat-item {
            text-align: center;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 0.75rem 0.5rem;
            transition: background 0.2s ease;
        }

        .stat-item:hover {
            background: rgba(34,197,94,0.06);
            border-color: rgba(34,197,94,0.15);
        }

        .stat-value {
            font-size: clamp(1rem, 1.8vw, 1.5rem);
            font-weight: 800;
            color: #4ade80;
            display: block;
            margin-bottom: 0.2rem;
        }

        .stat-label {
            font-size: 0.62rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.3;
        }

        /* ── Location Section ── */
        .location-section {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 2;
            background: rgba(11, 17, 32, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(34, 197, 94, 0.25);
            border-radius: 16px;
            padding: 0.85rem;
            width: clamp(280px, 22vw, 340px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .location-section:hover {
            border-color: rgba(34, 197, 94, 0.45);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(34, 197, 94, 0.15);
        }

        .location-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.65rem;
        }

        .location-header i {
            color: #4ade80;
            font-size: 0.85rem;
        }

        .location-header span {
            font-size: 0.68rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .location-card {
            display: flex;
            gap: 0.75rem;
            background: transparent;
            border: none;
            padding: 0;
        }

        .location-map {
            width: 90px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .location-map iframe {
            width: 100%;
            height: 100%;
            border: 0;
            filter: brightness(0.85) contrast(1.1) saturate(0.8);
        }

        .location-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .location-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 0.15rem;
        }

        .location-address {
            font-size: 0.65rem;
            color: #94a3b8;
            line-height: 1.4;
        }

        .location-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.62rem;
            color: #4ade80;
            text-decoration: none;
            margin-top: 0.3rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .location-link:hover {
            color: #86efac;
        }

        .hero-footer {
            margin-top: auto;
            padding-top: 1rem;
            font-size: 0.7rem;
            color: #334155;
        }

        /* ═══════════════════════════════════════════════════════
           TABLET ≤ 1024px — foto di atas, konten di bawah, SCROLL
        ═══════════════════════════════════════════════════════ */
        @media (max-width: 1024px) {
            /* Izinkan scroll di tablet & mobile */
            html, body {
                height: auto;
                overflow: auto;
            }

            .hero {
                height: auto;
                min-height: 100svh;     /* safe viewport height — akurat di mobile */
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                overflow: visible;
            }

            /* Foto atas, konten bawah */
            .hero-right { order: 1; height: 40vw; min-height: 200px; max-height: 380px; }
            .hero-left  { order: 2; justify-content: flex-start; padding: 1.5rem 1.5rem 2rem; }

            .hero-right::before {
                background: linear-gradient(to bottom, rgba(11,17,32,0.3) 0%, transparent 40%, rgba(11,17,32,0.6) 100%);
            }

            .hero h1 { font-size: clamp(1.5rem, 5vw, 2.4rem); }
        }

        /* ═══════════════════════════════════════════════════════
           MOBILE ≤ 640px
        ═══════════════════════════════════════════════════════ */
        @media (max-width: 640px) {
            .hero-right { height: 45vw; min-height: 180px; max-height: 280px; }

            .hero-left {
                padding: 1.2rem 1.1rem 1.8rem;
                gap: 0;
            }

            .hero-mascot { width: 52px; height: 52px; margin-bottom: 0.65rem; }
            .hero-badge  { font-size: 0.62rem; padding: 0.22rem 0.7rem; margin-bottom: 0.65rem; }

            .hero h1 { font-size: clamp(1.35rem, 6.5vw, 1.75rem); margin-bottom: 0.5rem; }

            .hero .subtitle {
                font-size: 0.82rem;
                margin-bottom: 0.85rem;
                line-height: 1.5;
            }

            .feature-pills { gap: 0.35rem; margin-bottom: 0.85rem; }
            .pill { font-size: 0.68rem; padding: 0.25rem 0.65rem; }

            .cta-group { gap: 0.5rem; margin-bottom: 0.85rem; flex-wrap: nowrap; }
            .btn-cta { font-size: 0.8rem; padding: 0.62rem 1rem; white-space: nowrap; }

            /* Stats: 2x2 grid di mobile */
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.6rem;
                padding-top: 0.85rem;
            }

            .stat-item {
                padding: 0.7rem 0.5rem;
                border-radius: 10px;
            }

            .stat-value { font-size: 1.1rem; }
            .stat-label { font-size: 0.58rem; letter-spacing: 0.5px; }

            .img-caption { font-size: 0.62rem; padding: 0.3rem 0.8rem; bottom: 0.6rem; }
            .hero-footer { display: none; }

            .location-section {
                position: absolute;
                bottom: 1rem;
                right: 1rem;
                width: calc(100% - 2rem);
                max-width: 300px;
                margin-top: 0;
                padding-top: 0;
            }
            .location-map { width: 75px; height: 60px; }
            .location-name { font-size: 0.72rem; }
            .location-address { font-size: 0.6rem; }
        }

        /* ═══════════════════════════════════════════════════════
           MOBILE KECIL ≤ 400px
        ═══════════════════════════════════════════════════════ */
        @media (max-width: 400px) {
            .hero-right { height: 50vw; min-height: 160px; }

            .hero h1 { font-size: 1.3rem; }
            .hero .subtitle { font-size: 0.78rem; }

            /* Sembunyikan pills biar tidak penuh */
            .feature-pills { display: none; }

            .btn-cta { font-size: 0.77rem; padding: 0.55rem 0.9rem; }
            .stat-value { font-size: 0.9rem; }

            .location-section { display: none; }
        }

        /* ═══════════════════════════════════════════════════════
           LANDSCAPE MOBILE (tinggi pendek)
        ═══════════════════════════════════════════════════════ */
        @media (max-height: 500px) and (max-width: 900px) {
            html, body { height: auto; overflow: auto; }

            .hero {
                height: auto;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: auto;
            }

            .hero-right { order: 2; height: auto; max-height: none; }
            .hero-left  { order: 1; justify-content: flex-start; padding: 1rem 1.2rem; overflow-y: auto; max-height: 100svh; }

            .hero-mascot { width: 44px; height: 44px; margin-bottom: 0.5rem; }
            .hero h1 { font-size: 1.15rem; }
            .hero .subtitle, .feature-pills { display: none; }
            .stats-row { padding-top: 0.6rem; }
        }
    </style>
</head>
<body>
    <section class="hero">

        {{-- ── LEFT: Konten ── --}}
        <div class="hero-left">
            <video class="hero-mascot" autoplay loop muted playsinline>
                <source src="{{ asset('img/maskot_.mp4') }}" type="video/mp4">
            </video>

            <div class="hero-badge">
                <span class="dot"></span>
                IoT Live Monitoring
            </div>

            <h1>Monitoring & Prediksi<br>Kebun Jambu Kristal</h1>

            <p class="subtitle">
                Perancangan Sistem <strong>IoT</strong> untuk Monitoring dan Prediksi
                Kondisi Lingkungan pada Kebun Jambu Kristal Menggunakan
                Metode <strong>ARIMA</strong>
            </p>

            <div class="feature-pills">
                <span class="pill pill-green"><i class="bi bi-droplet-fill"></i> Kelembapan Tanah</span>
                <span class="pill pill-orange"><i class="bi bi-thermometer-half"></i> Suhu Udara</span>
                <span class="pill pill-blue"><i class="bi bi-moisture"></i> Kelembapan Udara</span>
                <span class="pill pill-purple"><i class="bi bi-sun-fill"></i> Intensitas Cahaya</span>
            </div>

            <div class="cta-group">
                <a href="{{ route('login') }}" class="btn-cta btn-cta-primary">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk ke Dashboard
                </a>
                <a href="https://wa.me/6283143759920" target="_blank" class="btn-cta btn-cta-secondary">
                    <i class="bi bi-whatsapp"></i> Hubungi Admin
                </a>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <span class="stat-value">4</span>
                    <span class="stat-label">Sensor IoT</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">24/7</span>
                    <span class="stat-label">Monitoring</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">ARIMA</span>
                    <span class="stat-label">ML Model</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><i class="bi bi-telegram" style="font-size:1rem;"></i></span>
                    <span class="stat-label">Notifikasi</span>
                </div>
            </div>

            <p class="hero-footer">
                &copy; {{ date('Y') }} Monitoring Kebun Jambu Kristal &mdash; Skripsi IoT + Machine Learning
            </p>
        </div>

        {{-- ── RIGHT: Foto kebun ── --}}
        <div class="hero-right">
            <img
                src="{{ asset('img/background-landingPage.png') }}"
                alt="Kebun Jambu Kristal"
                class="hero-bg-image"
                loading="eager"
            >
            {{-- ── Lokasi Penelitian (Melayang di kanan) ── --}}
            <div class="location-section">
                <div class="location-header">
                    <i class="bi bi-pin-map-fill"></i>
                    <span>Lokasi Penelitian</span>
                </div>
                <div class="location-card">
                    <div class="location-map">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2104!2d115.4073503!3d-2.1741447!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dfab30061acd2c5%3A0x181dcd3babd88c94!2sKebun%20Jambu%20Kristal!5e1!3m2!1sid!2sid!4v1747418183000"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                    </div>
                    <div class="location-info">
                        <div class="location-name">Kebun Jambu Kristal</div>
                        <div class="location-address">Kalimantan Selatan, Indonesia</div>
                        <a href="https://maps.app.goo.gl/TXwvWkUy4rXxhVdj7" target="_blank" class="location-link">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat di Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </section>
</body>
</html>
