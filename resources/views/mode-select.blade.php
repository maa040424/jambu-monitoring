<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pilih Mode Data — Monitoring Kebun Jambu Kristal</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5.3 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0b1120;
            color: #f9fafb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Hapus overflow: hidden — agar bisa scroll di mobile */
            overflow-y: auto;
            padding: 1rem 0;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(34, 197, 94, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(56, 189, 248, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 80%, rgba(244, 63, 94, 0.05) 0%, transparent 50%);
            z-index: 0;
            animation: bg-drift 15s ease-in-out infinite alternate;
            pointer-events: none;
        }

        @keyframes bg-drift {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(-20px, 10px); }
        }

        .mode-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 720px;
            padding: 1.5rem;
        }

        .mode-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .mode-header .brand-emoji {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 0.75rem;
            filter: drop-shadow(0 0 20px rgba(76, 175, 80, 0.5));
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .mode-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 0.5rem;
        }

        .mode-header p {
            color: #94a3b8;
            font-size: 0.9rem;
            margin: 0;
        }

        .mode-header .user-info {
            margin-top: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 0.35rem 1rem;
            font-size: 0.8rem;
            color: #cbd5e1;
        }

        .mode-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .mode-card {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .mode-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 18px;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .mode-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .mode-card-dummy::before {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.05));
        }
        .mode-card-dummy:hover {
            border-color: #f59e0b;
            box-shadow: 0 20px 40px rgba(245, 158, 11, 0.15);
        }
        .mode-card-dummy:hover::before { opacity: 1; }

        .mode-card-real::before {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
        }
        .mode-card-real:hover {
            border-color: #10b981;
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.15);
        }
        .mode-card-real:hover::before { opacity: 1; }

        .mode-card .card-emoji {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .mode-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            position: relative;
            z-index: 1;
        }

        .mode-card-dummy h3 { color: #fbbf24; }
        .mode-card-real h3 { color: #34d399; }

        .mode-card p {
            font-size: 0.82rem;
            color: #94a3b8;
            margin: 0 0 1.25rem;
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }

        .mode-card .mode-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .mode-card-dummy .mode-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
        }
        .mode-card-dummy:hover .mode-btn {
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.4);
            transform: scale(1.05);
        }

        .mode-card-real .mode-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
        }
        .mode-card-real:hover .mode-btn {
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
            transform: scale(1.05);
        }

        .mode-features {
            margin-top: 0.75rem;
            text-align: left;
            position: relative;
            z-index: 1;
        }

        .mode-features li {
            font-size: 0.78rem;
            color: #64748b;
            padding: 0.2rem 0;
            list-style: none;
        }

        .mode-features li::before {
            content: '✓ ';
            color: #4ade80;
            font-weight: 700;
        }

        .mode-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .mode-footer .logout-link {
            color: #64748b;
            font-size: 0.82rem;
            text-decoration: none;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .mode-footer .logout-link:hover {
            color: #ef4444;
        }

        /* Current mode indicator */
        .current-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
        }

        /* ── Mobile: stack vertikal, scroll bebas ── */
        @media (max-width: 640px) {
            body {
                align-items: flex-start;
                padding: 1rem 0 2rem;
            }

            .mode-wrapper { padding: 1rem; }

            .mode-header { margin-bottom: 1.5rem; }
            .mode-header .brand-emoji { font-size: 2.5rem; margin-bottom: 0.5rem; }
            .mode-header h1 { font-size: 1.15rem; }
            .mode-header p { font-size: 0.82rem; }
            .mode-header .user-info { font-size: 0.75rem; padding: 0.28rem 0.8rem; }

            .mode-cards { grid-template-columns: 1fr; gap: 1rem; }

            .mode-card { padding: 1.4rem 1.2rem; border-radius: 16px; }
            .mode-card .card-emoji { font-size: 2.2rem; margin-bottom: 0.65rem; }
            .mode-card h3 { font-size: 1.05rem; }
            .mode-card p { font-size: 0.8rem; margin-bottom: 1rem; }
            .mode-card .mode-btn { padding: 0.5rem 1.2rem; font-size: 0.82rem; }
            .mode-features li { font-size: 0.75rem; }

            .mode-footer { margin-top: 1.5rem; }
        }

        /* Sangat kecil */
        @media (max-width: 360px) {
            .mode-wrapper { padding: 0.8rem; }
            .mode-card { padding: 1.1rem 1rem; }
            .mode-header .brand-emoji { font-size: 2rem; }
            .mode-header h1 { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="mode-wrapper">
        <div class="mode-header">
            <span class="brand-emoji">🌿</span>
            <h1>Monitoring Kebun Jambu Kristal</h1>
            <p>Pilih sumber data yang ingin ditampilkan</p>
            <div class="user-info">
                <i class="bi {{ auth()->user()->isAdmin() ? 'bi-shield-lock' : 'bi-person' }}"></i>
                <span>{{ auth()->user()->name }}</span>
                <span class="badge {{ auth()->user()->isAdmin() ? 'bg-danger' : 'bg-info' }} bg-opacity-75" style="font-size: 0.65rem;">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
            </div>
        </div>

        <div class="mode-cards">
            {{-- Dummy Mode --}}
            <form method="POST" action="{{ route('mode.set') }}" class="mode-card mode-card-dummy" id="form-dummy">
                @csrf
                <input type="hidden" name="mode" value="dummy">
                @if(session('data_mode') === 'dummy')
                    <span class="current-badge"><i class="bi bi-check-circle-fill me-1"></i>Aktif</span>
                @endif
                <span class="card-emoji">🧪</span>
                <h3>Mode Dummy</h3>
                <p>Data simulasi untuk pengujian dan pengembangan sistem</p>
                <ul class="mode-features">
                    <li>Data 30 hari otomatis</li>
                    <li>Pola realistis siang/malam</li>
                    <li>Aman untuk eksperimen</li>
                </ul>
                <button type="submit" class="mode-btn">
                    <i class="bi bi-flask"></i> Pilih Dummy
                </button>
            </form>

            {{-- Real Mode --}}
            <form method="POST" action="{{ route('mode.set') }}" class="mode-card mode-card-real" id="form-real">
                @csrf
                <input type="hidden" name="mode" value="real">
                @if(session('data_mode') === 'real')
                    <span class="current-badge"><i class="bi bi-check-circle-fill me-1"></i>Aktif</span>
                @endif
                <span class="card-emoji">📡</span>
                <h3>Mode Real</h3>
                <p>Data langsung dari perangkat sensor IoT di kebun</p>
                <ul class="mode-features">
                    <li>Data real-time sensor</li>
                    <li>Monitoring langsung</li>
                    <li>Notifikasi Telegram</li>
                </ul>
                <button type="submit" class="mode-btn">
                    <i class="bi bi-broadcast"></i> Pilih Real
                </button>
            </form>
        </div>

        <div class="mode-footer">
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="logout-link" style="background: none; border: none; cursor: pointer;">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <script>
        // Make entire card clickable (submit form on card click)
        document.querySelectorAll('.mode-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.tagName !== 'BUTTON') {
                    this.querySelector('button[type="submit"]').click();
                }
            });
        });
    </script>
</body>
</html>
