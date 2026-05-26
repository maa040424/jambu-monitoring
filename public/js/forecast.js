/**
 * Forecast UI Logic
 * Depends on: common.js (theme, chart helpers)
 */

let currentForecastMode = window.INITIAL_MODE || 'dummy';
let currentForecastHours = 4; // default 4 jam ke depan
let chartForecastMoisture = null;
let chartForecastTemp = null;
let chartForecastHumidity = null;
let chartForecastLight = null;

/* ─── ARIMA Overlay helpers ─────────────────────────── */
const ARIMA_MIN_DISPLAY_MS = 3000; // minimum tampil 3 detik

function showArimaOverlay() {
    const overlay = document.getElementById('arima-overlay');
    if (!overlay) return;

    // Reset status text
    const statusEl = document.getElementById('arima-status-text');
    if (statusEl) {
        statusEl.textContent = 'Sistem sedang memproses data historis dengan model Machine Learning...';
        statusEl.style.color = '';
    }

    // Pastikan video autoplay ulang
    const video = document.getElementById('arima-loading-video');
    if (video) {
        video.currentTime = 0;
        video.play().catch(() => {});
    }

    overlay.classList.remove('hiding');
    overlay.style.display = 'flex';

    // Catat waktu mulai tampil
    overlay._shownAt = Date.now();
}

function hideArimaOverlay(callback) {
    const overlay = document.getElementById('arima-overlay');
    if (!overlay || overlay.style.display === 'none') {
        if (callback) callback();
        return;
    }

    const elapsed  = Date.now() - (overlay._shownAt || 0);
    const remaining = Math.max(0, ARIMA_MIN_DISPLAY_MS - elapsed);

    setTimeout(() => {
        overlay.classList.add('hiding');
        // Tunggu animasi fade-out selesai (450ms), baru sembunyikan
        setTimeout(() => {
            overlay.style.display = 'none';
            overlay.classList.remove('hiding');
            if (callback) callback();
        }, 460);
    }, remaining);
}

/* ─── Hour Selector ─────────────────────────────────── */
function selectForecastHours(hours) {
    if (hours === currentForecastHours) return;
    currentForecastHours = hours;

    // Update active button with animation
    const buttons = document.querySelectorAll('.hour-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active', 'ripple');
        if (parseInt(btn.dataset.hours) === hours) {
            btn.classList.add('active', 'ripple');
            // Remove ripple class after animation
            setTimeout(() => btn.classList.remove('ripple'), 400);
        }
    });

    // Update info text
    const infoEl = document.getElementById('forecast-steps-info');
    if (infoEl) {
        infoEl.textContent = `Prediksi ${hours} jam ke depan dari data terakhir`;
    }

    // Re-fetch forecast with new hours
    fetchForecast(true);
}

/* ─── Boot ─────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    applyTheme(getTheme());

    // Tampilkan overlay saat pertama kali buka halaman forecast
    showArimaOverlay();
    fetchForecast(false);
});

/* ─── Mode switch ───────────────────────────────────── */
function switchForecastMode(mode) {
    if (mode === currentForecastMode) return;
    currentForecastMode = mode;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    fetch('/switch-mode', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ mode }),
    });

    fetchForecast(true);
}

// Called by common.js when theme changes
function rebuildChartsForTheme() {
    if (chartForecastMoisture) {
        fetchForecast(false);
    }
}

/* ─── Main fetch ────────────────────────────────────── */
async function fetchForecast(forceRefresh = false) {
    const loading        = document.getElementById('forecast-loading');
    const chartWrappers  = document.querySelectorAll('.forecast-chart-wrapper');
    const tableContainer = document.getElementById('forecast-table-container');
    const refreshBtnIcon = document.querySelector('#btn-refresh-forecast i');

    // Tampilkan overlay animasi
    showArimaOverlay();

    if (forceRefresh) {
        if (refreshBtnIcon) refreshBtnIcon.classList.add('spin-icon');
        loading.classList.remove('d-none');
        loading.innerHTML = '<div class="spinner-border text-primary border-0 mb-3" style="width:3rem;height:3rem;border-width:0.25rem!important;" role="status"></div>'
            + '<div class="fw-semibold">Sedang menghitung prediksi dengan model Machine Learning...</div>'
            + '<small>Ini mungkin memakan waktu beberapa saat tergantung jumlah data.</small>';
        chartWrappers.forEach(el => {
            el.classList.add('d-none');
            el.classList.remove('chart-visible');
        });
        tableContainer.classList.add('d-none');
    }

    try {
        const res = await fetch(`/api/forecast?source=${currentForecastMode}&hours=${currentForecastHours}`);

        let data;
        try { data = await res.json(); } catch (e) { data = null; }

        if (!res.ok) {
            throw new Error((data && data.message) ? data.message : `HTTP ${res.status}`);
        }

        if (data && data.status === 'success') {
            // Sembunyikan overlay (minimum 3 detik), lalu tampilkan chart SATU PER SATU
            hideArimaOverlay(() => {
                loading.classList.add('d-none');

                // Sembunyikan semua wrapper dulu, munculkan staggered
                chartWrappers.forEach(el => {
                    el.classList.add('d-none');
                    el.classList.remove('chart-visible');
                });
                tableContainer.classList.add('d-none');

                renderForecastChartsStaggered(data, () => {
                    // Setelah semua chart selesai, tampilkan tabel + PDF button
                    tableContainer.classList.remove('d-none');
                    tableContainer.style.animation = 'chart-slide-in 0.5s ease forwards';
                    renderForecastTable(data.forecast);

                    const pdfBtn = document.getElementById('btn-export-pdf');
                    if (pdfBtn) pdfBtn.classList.remove('d-none');
                });
            });
        } else {
            throw new Error((data && data.message) ? data.message : 'Error occurred');
        }

    } catch (err) {
        console.error('Forecast error:', err);

        // Update teks status overlay dulu
        const statusEl = document.getElementById('arima-status-text');
        if (statusEl) {
            statusEl.style.color = '#f87171';
            statusEl.textContent = 'Gagal memuat prediksi. ' + err.message;
        }

        // Tunggu minimum 3 detik, tampilkan error di loading area
        hideArimaOverlay(() => {
            loading.classList.remove('d-none');
            loading.innerHTML = `<div class="text-danger"><i class="bi bi-exclamation-triangle-fill fs-3 mb-2 d-block"></i>Gagal memuat prediksi ARIMA.</div>`
                + `<div class="alert alert-warning d-inline-block mt-2 text-start small border border-warning-subtle shadow-sm">${err.message}</div>`;
        });

    } finally {
        if (refreshBtnIcon) refreshBtnIcon.classList.remove('spin-icon');
    }
}

/* ─── CSS animasi stagger (inject sekali) ────────────── */
(function injectStaggerCSS() {
    if (document.getElementById('stagger-css')) return;
    const style = document.createElement('style');
    style.id = 'stagger-css';
    style.textContent = `
        @keyframes chart-slide-in {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes shimmer {
            0%   { background-position: -600px 0; }
            100% { background-position: 600px 0; }
        }
        .chart-skeleton {
            border-radius: 12px;
            background: linear-gradient(
                90deg,
                rgba(255,255,255,0.04) 25%,
                rgba(255,255,255,0.10) 50%,
                rgba(255,255,255,0.04) 75%
            );
            background-size: 600px 100%;
            animation: shimmer 1.4s infinite linear;
            height: 220px;
            width: 100%;
        }
        .forecast-chart-wrapper {
            opacity: 0;
        }
        .forecast-chart-wrapper.chart-visible {
            animation: chart-slide-in 0.55s cubic-bezier(0.34,1.4,0.64,1) forwards;
        }
    `;
    document.head.appendChild(style);
})();

/* ─── Render Charts STAGGERED ────────────────────────── */
function renderForecastChartsStaggered(data, onAllDone) {
    const tc       = getChartThemeColors();
    const actual   = data.actual   || [];
    const forecast = data.forecast || [];

    const allLabels = [];
    const actualMoisture = [], actualTemp = [], actualHumidity = [], actualLight = [];

    actual.forEach(d => {
        const dt = new Date(d.time);
        allLabels.push(dt.toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }));
        actualMoisture.push(d.soil_moisture);
        actualTemp.push(d.temperature);
        actualHumidity.push(d.humidity);
        actualLight.push(d.light_intensity);
    });

    const forecastMoisture = Array(actual.length - 1).fill(null);
    const forecastTemp     = Array(actual.length - 1).fill(null);
    const forecastHumidity = Array(actual.length - 1).fill(null);
    const forecastLight    = Array(actual.length - 1).fill(null);

    if (actual.length > 0) {
        const lastActual = actual[actual.length - 1];
        forecastMoisture.push(lastActual.soil_moisture);
        forecastTemp.push(lastActual.temperature);
        forecastHumidity.push(lastActual.humidity);
        forecastLight.push(lastActual.light_intensity);
    }

    forecast.forEach(d => {
        const dt = new Date(d.time);
        allLabels.push(dt.toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }));
        actualMoisture.push(null);
        actualTemp.push(null);
        actualHumidity.push(null);
        actualLight.push(null);

        forecastMoisture.push(d.soil_moisture);
        forecastTemp.push(d.temperature);
        forecastHumidity.push(d.humidity);
        forecastLight.push(d.light_intensity);
    });


    // Definisi 4 chart yang akan dimunculkan berurutan
    const chartQueue = [
        {
            wrapperId : 'wrapper-moisture',
            canvasId  : 'chart-forecast-moisture',
            oldChart  : chartForecastMoisture,
            label     : 'Kelembapan Tanah',
            actual    : actualMoisture,
            predicted : forecastMoisture,
            color     : '#26a69a',
            setter    : (c) => { chartForecastMoisture = c; }
        },
        {
            wrapperId : 'wrapper-temp',
            canvasId  : 'chart-forecast-temp',
            oldChart  : chartForecastTemp,
            label     : 'Suhu Udara',
            actual    : actualTemp,
            predicted : forecastTemp,
            color     : '#ef5350',
            setter    : (c) => { chartForecastTemp = c; }
        },
        {
            wrapperId : 'wrapper-humidity',
            canvasId  : 'chart-forecast-humidity',
            oldChart  : chartForecastHumidity,
            label     : 'Kelembapan Udara',
            actual    : actualHumidity,
            predicted : forecastHumidity,
            color     : '#42a5f5',
            setter    : (c) => { chartForecastHumidity = c; }
        },
        {
            wrapperId : 'wrapper-light',
            canvasId  : 'chart-forecast-light',
            oldChart  : chartForecastLight,
            label     : 'Intensitas Cahaya',
            actual    : actualLight,
            predicted : forecastLight,
            color     : '#ffa726',
            setter    : (c) => { chartForecastLight = c; }
        },
    ];

    const STAGGER_DELAY    = 480;  // ms antar tiap card muncul
    const SKELETON_HOLD    = 650;  // ms skeleton tampil sebelum chart dirender

    /**
     * Fungsi pembantu: build Chart.js instance
     */
    function buildChart(cfg) {
        if (cfg.oldChart) cfg.oldChart.destroy();

        // Annotation: vertical line at forecast start
        const forecastStartIndex = actual.length - 1;
        const isLight = cfg.canvasId === 'chart-forecast-light';

        const chart = new Chart(document.getElementById(cfg.canvasId), {
            type: 'line',
            data: {
                labels: allLabels,
                datasets: [
                    {
                        label: 'Aktual (Historis)',
                        data: cfg.actual,
                        borderColor: cfg.color,
                        backgroundColor: cfg.color + '18',
                        borderWidth: 2.5,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        tension: isLight ? 0 : 0.4,
                        stepped: isLight,
                        fill: true,
                    },
                    {
                        label: `Prediksi ${currentForecastHours} Jam Ke Depan`,
                        data: cfg.predicted,
                        borderColor: cfg.color,
                        backgroundColor: cfg.color + '0a',
                        borderWidth: 2.5,
                        borderDash: [6, 4],
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        pointStyle: 'triangle',
                        tension: isLight ? 0 : 0.4,
                        stepped: isLight,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { color: tc.legend, usePointStyle: true, padding: 16 } },
                    tooltip: {
                        backgroundColor: tc.tooltipBg, titleColor: tc.tooltipTitle,
                        bodyColor: tc.tooltipBody, borderColor: tc.tooltipBorder, borderWidth: 1,
                        callbacks: {
                            title: function(items) {
                                const idx = items[0].dataIndex;
                                if (idx >= forecastStartIndex) {
                                    return '🔮 ' + items[0].label + ' (Prediksi)';
                                }
                                return '📊 ' + items[0].label + ' (Aktual)';
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: tc.tick, maxRotation: 45, font: { size: 10 } }, grid: { color: tc.grid } },
                    y: isLight ? {
                        min: 0,
                        max: 500,
                        ticks: {
                            color: tc.tick,
                            font: { size: 10, weight: 'bold' },
                            stepSize: 500,
                            callback: function(value) {
                                if (value === 0) return '🌙 GELAP';
                                if (value === 500) return '☀️ TERANG';
                                return '';
                            }
                        },
                        grid: { color: tc.grid }
                    } : {
                        ticks: { color: tc.tick },
                        grid: { color: tc.grid }
                    }
                }
            }
        });
        cfg.setter(chart);
    }

    /**
     * Reveal satu card: tampilkan wrapper → skeleton shimmer → render chart
     */
    function revealCard(index) {
        if (index >= chartQueue.length) {
            // Semua chart sudah muncul — panggil callback (tabel + PDF btn)
            if (onAllDone) onAllDone();
            return;
        }

        const cfg     = chartQueue[index];
        const wrapper = document.querySelector(`.forecast-chart-wrapper[data-id="${cfg.wrapperId}"]`)
                     || document.querySelectorAll('.forecast-chart-wrapper')[index];

        if (!wrapper) {
            revealCard(index + 1);
            return;
        }

        // 1. Tampilkan card wrapper dengan skeleton
        const cardBody = wrapper.querySelector('.card-body');
        const canvas   = wrapper.querySelector('canvas');

        if (cardBody && canvas) {
            // Sembunyikan canvas sementara, tampilkan skeleton
            canvas.style.display = 'none';
            const skeleton = document.createElement('div');
            skeleton.className = 'chart-skeleton';
            cardBody.appendChild(skeleton);
        }

        // 2. Hapus d-none → trigger animasi slide-in
        wrapper.classList.remove('d-none');
        // Force reflow untuk animasi CSS
        void wrapper.offsetWidth;
        wrapper.classList.add('chart-visible');

        // 3. Setelah skeleton tampil beberapa saat → render chart asli
        setTimeout(() => {
            if (cardBody && canvas) {
                const skeleton = cardBody.querySelector('.chart-skeleton');
                if (skeleton) skeleton.remove();
                canvas.style.display = '';
            }

            buildChart(cfg);

            // 4. Reveal card berikutnya setelah delay stagger
            setTimeout(() => revealCard(index + 1), STAGGER_DELAY);
        }, SKELETON_HOLD);
    }

    // Mulai dari card pertama
    revealCard(0);
}


/* ─── Render Table ──────────────────────────────────── */
function renderForecastTable(forecast) {
    const tbody = document.getElementById('forecast-table-body');
    if (!tbody || !forecast) return;

    tbody.innerHTML = forecast.map((d, i) => {
        const dt = new Date(d.time);
        const timeStr = dt.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });

        // Stagger animation delay per row
        const delay = i * 30;
        const lightVal = parseFloat(d.light_intensity);
        const lightBadge = lightVal >= 100 
            ? `<span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-sun-fill me-1 text-warning"></i> Terang</span>` 
            : `<span class="badge bg-indigo-subtle text-indigo-emphasis" style="background-color: rgba(99, 102, 241, 0.15); color: #818cf8;"><i class="bi bi-moon-stars-fill me-1" style="color: #818cf8;"></i> Gelap</span>`;

        return `<tr style="animation: table-row-in 0.3s ease ${delay}ms both;">
            <td class="text-nowrap"><span class="badge bg-success-subtle text-success-emphasis me-1">+${i + 1}</span>${timeStr}</td>
            <td>${parseFloat(d.soil_moisture).toFixed(1)}%</td>
            <td>${parseFloat(d.temperature).toFixed(1)}°C</td>
            <td>${parseFloat(d.humidity).toFixed(1)}%</td>
            <td>${lightBadge}</td>
        </tr>`;
    }).join('');

    // Inject table row animation if not exists
    if (!document.getElementById('table-row-css')) {
        const style = document.createElement('style');
        style.id = 'table-row-css';
        style.textContent = `
            @keyframes table-row-in {
                from { opacity: 0; transform: translateX(-10px); }
                to   { opacity: 1; transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);
    }
}

/* ─── Export PDF ────────────────────────────────────── */
function exportForecastPdf() {
    window.location.href = `/export-forecast-pdf?source=${currentForecastMode}&hours=${currentForecastHours}`;
}
