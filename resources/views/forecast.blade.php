@extends('layouts.app')

@section('content')

{{-- ═══════════════════════════════════════════════════
     ARIMA LOADING OVERLAY — tampil selama proses kalkulasi
     Minimum 3 detik, lanjut sampai hasil ada
════════════════════════════════════════════════════ --}}
<div id="arima-overlay" class="arima-overlay" style="display:none;">
    <div class="arima-overlay-inner">
        {{-- Lottie ARIMA animation --}}
        <div class="arima-lottie-wrap">
            <lottie-player
                src="{{ asset('img/ARIMA_animation.json') }}"
                background="transparent"
                speed="1"
                loop
                autoplay
                style="width: 100%; height: 100%;">
            </lottie-player>
        </div>

        {{-- Teks status --}}
        <div class="arima-text">
            <div class="arima-title">Menghitung Prediksi ARIMA</div>
            <div class="arima-subtitle" id="arima-status-text">
                Sistem sedang memproses data historis dengan model Machine Learning...
            </div>

            {{-- Progress dots --}}
            <div class="arima-dots">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </div>
</div>

<style>
/* ── ARIMA Overlay ── */
.arima-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(11, 17, 32, 0.92);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: overlay-in 0.35s ease forwards;
}

.arima-overlay.hiding {
    animation: overlay-out 0.45s ease forwards;
}

@keyframes overlay-in {
    from { opacity: 0; }
    to   { opacity: 1; }
}

@keyframes overlay-out {
    from { opacity: 1; transform: scale(1); }
    to   { opacity: 0; transform: scale(1.03); }
}

.arima-overlay-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
    text-align: center;
    padding: 2rem 1.5rem;
    max-width: 420px;
    width: 100%;
    animation: inner-in 0.5s cubic-bezier(0.34,1.56,0.64,1) forwards;
}

@keyframes inner-in {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Lottie wrap */
.arima-lottie-wrap {
    width: clamp(200px, 40vw, 300px);
    height: clamp(200px, 40vw, 300px);
    flex-shrink: 0;
    filter: drop-shadow(0 0 30px rgba(34, 197, 94, 0.2));
    animation: lottie-glow 2.5s ease-in-out infinite alternate;
}

@keyframes lottie-glow {
    0%   { filter: drop-shadow(0 0 20px rgba(34,197,94,0.15)); }
    100% { filter: drop-shadow(0 0 45px rgba(34,197,94,0.30)); }
}

/* Text */
.arima-text { color: #e2e8f0; }

.arima-title {
    font-size: clamp(1.1rem, 3vw, 1.4rem);
    font-weight: 800;
    background: linear-gradient(135deg, #4ade80, #22c55e, #86efac);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.6rem;
    letter-spacing: -0.3px;
}

.arima-subtitle {
    font-size: clamp(0.8rem, 2vw, 0.92rem);
    color: #64748b;
    line-height: 1.55;
    max-width: 320px;
    margin: 0 auto;
    transition: color 0.3s ease;
}

/* Animated dots */
.arima-dots {
    display: flex;
    gap: 0.55rem;
    justify-content: center;
    margin-top: 1.2rem;
}

.arima-dots .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    animation: bounce-dot 1.4s ease-in-out infinite;
}

.arima-dots .dot:nth-child(2) { animation-delay: 0.2s; background: #4ade80; }
.arima-dots .dot:nth-child(3) { animation-delay: 0.4s; background: #86efac; }

@keyframes bounce-dot {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40%           { transform: scale(1.15); opacity: 1; }
}

/* Mobile adjustments */
@media (max-width: 480px) {
    .arima-lottie-wrap { width: 180px; height: 180px; }
    .arima-overlay-inner { gap: 1.5rem; }
}
</style>

{{-- Flash Messages --}}
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0 fw-bold">Prediksi Sensor (ARIMA)</h2>
    <div class="d-flex align-items-center gap-2">
        <span class="badge {{ $mode === 'dummy' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $mode === 'dummy' ? '🧪 Dummy' : '📡 Real' }}</span>
        <button class="btn btn-sm btn-outline-danger d-none" id="btn-export-pdf" onclick="exportForecastPdf()">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </button>
        <button class="btn btn-sm btn-primary" id="btn-refresh-forecast" onclick="fetchForecast(true)">
            <i class="bi bi-arrow-repeat me-1 spinner-icon"></i> Refresh
        </button>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    <i class="bi bi-info-circle-fill me-2"></i> Prediksi di bawah ini menggunakan Model <strong>ARIMA (AutoRegressive Integrated Moving Average)</strong>. Sistem akan mencoba memperkirakan kondisi 24 langkah (sekitar 4 jam) ke depan berdasarkan urutan data historis terbaru.
</div>

<div class="row g-3 mb-4" id="forecast-charts-container">
    <div class="col-12 text-center py-5 text-muted" id="forecast-loading">
        <lottie-player
            src="{{ asset('img/signal-analysis.json') }}"
            background="transparent"
            speed="1"
            loop autoplay
            style="width: 120px; height: 120px; margin: 0 auto 1rem;">
        </lottie-player>
        <div class="fw-semibold">Sedang menghitung prediksi dengan model Machine Learning...</div>
        <small>Ini mungkin memakan waktu beberapa saat tergantung jumlah data.</small>
    </div>

    <div class="col-12 col-lg-6 d-none forecast-chart-wrapper">
        <div class="card chart-card">
            <div class="card-header"><i class="bi bi-droplet me-2"></i>Prediksi Kelembapan Tanah</div>
            <div class="card-body"><canvas id="chart-forecast-moisture"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 d-none forecast-chart-wrapper">
        <div class="card chart-card">
            <div class="card-header"><i class="bi bi-thermometer me-2"></i>Prediksi Suhu Udara</div>
            <div class="card-body"><canvas id="chart-forecast-temp"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 d-none forecast-chart-wrapper">
        <div class="card chart-card">
            <div class="card-header"><i class="bi bi-moisture me-2"></i>Prediksi Kelembapan Udara</div>
            <div class="card-body"><canvas id="chart-forecast-humidity"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 d-none forecast-chart-wrapper">
        <div class="card chart-card">
            <div class="card-header"><i class="bi bi-sun me-2"></i>Prediksi Intensitas Cahaya</div>
            <div class="card-body"><canvas id="chart-forecast-light"></canvas></div>
        </div>
    </div>
</div>

{{-- Forecast Table --}}
<div class="card data-table-card mb-5 d-none" id="forecast-table-container">
    <div class="card-header">
        <i class="bi bi-table me-2"></i>Tabel Hasil Prediksi ARIMA
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Waktu (Prediksi)</th>
                        <th>Kelembapan Tanah</th>
                        <th>Suhu Udara</th>
                        <th>Kelembapan Udara</th>
                        <th>Intensitas Cahaya</th>
                    </tr>
                </thead>
                <tbody id="forecast-table-body">
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<script src="{{ asset('js/forecast.js') }}?v={{ time() }}"></script>
@endsection

