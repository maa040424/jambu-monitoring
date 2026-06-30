@extends('layouts.app')

@section('content')

{{-- ═══════════════════════════════════════════════════
     ARIMA LOADING OVERLAY — tampil selama proses kalkulasi
     Minimum 3 detik, lanjut sampai hasil ada
════════════════════════════════════════════════════ --}}
<div id="arima-overlay" class="arima-overlay" style="display:none;">
    <div class="arima-overlay-inner">
        {{-- Lottie ARIMA animation ── --}}
        <div class="arima-video-wrap d-flex align-items-center justify-content-center">
            <lottie-player
                src="{{ asset('img/ARIMA_animation.json') }}"
                background="transparent"
                speed="1.2"
                loop autoplay
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

/* Video wrap */
.arima-video-wrap {
    width: clamp(180px, 38vw, 260px);
    height: clamp(180px, 38vw, 260px);
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(34, 197, 94, 0.35);
    box-shadow:
        0 0 40px rgba(34, 197, 94, 0.2),
        0 0 80px rgba(34, 197, 94, 0.08),
        inset 0 0 20px rgba(34, 197, 94, 0.05);
    animation: glow-pulse 2.5s ease-in-out infinite alternate;
    background: #0b1120;
    flex-shrink: 0;
}

@keyframes glow-pulse {
    0%   { box-shadow: 0 0 30px rgba(34,197,94,0.15), 0 0 60px rgba(34,197,94,0.06); }
    100% { box-shadow: 0 0 55px rgba(34,197,94,0.30), 0 0 100px rgba(34,197,94,0.12); }
}

#arima-loading-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
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
    .arima-video-wrap { width: 150px; height: 150px; }
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
        @if(auth()->user()->isSuperAdmin())
        <span class="badge {{ $mode === 'dummy' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $mode === 'dummy' ? '🧪 Dummy' : '📡 Real' }}</span>
        @endif
        <button class="btn btn-sm btn-outline-danger d-none" id="btn-export-pdf" onclick="exportForecastPdf()">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </button>
        <button class="btn btn-sm btn-primary" id="btn-refresh-forecast" onclick="fetchForecast(true)">
            <i class="bi bi-arrow-repeat me-1 spinner-icon"></i> Refresh
        </button>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    <i class="bi bi-info-circle-fill me-2"></i> Prediksi di bawah ini menggunakan Model <strong>ARIMA (AutoRegressive Integrated Moving Average)</strong>. Sistem akan memperkirakan kondisi beberapa jam <strong>ke depan</strong> berdasarkan data historis terbaru.
</div>

{{-- Hour Selector --}}
<div class="card mb-4" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px;">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <label class="form-label small fw-semibold text-uppercase text-muted mb-0">
                <i class="bi bi-clock-history me-1"></i>Prediksi Ke Depan
            </label>
            <div class="forecast-hour-selector" id="forecast-hour-selector">
                <button type="button" class="hour-btn" data-hours="1" onclick="selectForecastHours(1)">1 Jam</button>
                <button type="button" class="hour-btn" data-hours="2" onclick="selectForecastHours(2)">2 Jam</button>
                <button type="button" class="hour-btn" data-hours="3" onclick="selectForecastHours(3)">3 Jam</button>
                <button type="button" class="hour-btn active" data-hours="4" onclick="selectForecastHours(4)">4 Jam</button>
                <button type="button" class="hour-btn" data-hours="5" onclick="selectForecastHours(5)">5 Jam</button>
                <button type="button" class="hour-btn" data-hours="6" onclick="selectForecastHours(6)">6 Jam</button>
            </div>
            <span class="text-muted small" id="forecast-steps-info">Prediksi 4 jam ke depan dari data terakhir</span>
        </div>
    </div>
</div>

<style>
.forecast-hour-selector {
    display: flex;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.hour-btn {
    padding: 0.4rem 0.9rem;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.hour-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 185, 129, 0.1));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.hour-btn:hover {
    border-color: var(--green-500);
    color: var(--green-400);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
}

.hour-btn:hover::before {
    opacity: 1;
}

.hour-btn.active {
    background: linear-gradient(135deg, #10b981, #059669);
    border-color: #059669;
    color: #fff;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
    transform: translateY(-2px) scale(1.05);
}

.hour-btn.active::before {
    opacity: 0;
}

/* Ripple effect on click */
.hour-btn.ripple {
    animation: btn-ripple 0.4s ease-out;
}

@keyframes btn-ripple {
    0% { transform: translateY(-2px) scale(1.05); }
    50% { transform: translateY(-2px) scale(0.95); }
    100% { transform: translateY(-2px) scale(1.05); }
}
</style>

<div class="row g-3 mb-4" id="forecast-charts-container">
    <div class="col-12 text-center py-5 text-muted" id="forecast-loading">
        <div class="d-flex justify-content-center mb-3">
            <lottie-player
                src="{{ asset('img/ARIMA_animation.json') }}"
                background="transparent"
                speed="1.2"
                loop autoplay
                style="width: 120px; height: 120px;">
            </lottie-player>
        </div>
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
                <thead>
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
<script src="{{ asset('js/forecast.js') }}?v={{ time() }}"></script>
@endsection

