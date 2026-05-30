/* =========================================================
   Jambu Kristal Monitoring Dashboard — JavaScript
   Depends on: common.js (theme, chart helpers)
   ========================================================= */

// ── Configuration ────────────────────────────────────────
const CONFIG = {
    API_URL: '/api/sensor-data',
    REFRESH_INTERVAL: 10000, // 10 seconds
    CHART_MAX_POINTS: 50,    // max data points shown on charts
};

// ── State ────────────────────────────────────────────────
let allData = [];
let chartMoisture = null;
let chartTempHumidity = null;
let chartLight = null;
let refreshTimer = null;
let currentMode = window.INITIAL_MODE || 'dummy';
let currentTablePage = 1;
const TABLE_PAGE_SIZE = 20;
let selectedIds = new Set();
let simulatorInterval = null;
let simStep = 0;
let lastSimSoil = 55.0;

// ── DOM Elements ─────────────────────────────────────────
const DOM = {
    overlay: () => document.getElementById('loading-overlay'),
    errorToast: () => document.getElementById('error-toast'),
    errorMsg: () => document.getElementById('error-message'),
    successToast: () => document.getElementById('success-toast'),
    successMsg: () => document.getElementById('success-message'),
    valMoisture: () => document.getElementById('val-moisture'),
    valTemp: () => document.getElementById('val-temp'),
    valHumidity: () => document.getElementById('val-humidity'),
    valLight: () => document.getElementById('val-light'),
    statusBadge: () => document.getElementById('status-badge'),
    tempWarning: () => document.getElementById('temp-warning'),
    tempWarnText: () => document.getElementById('temp-warning-text'),
    lastUpdated: () => document.getElementById('last-updated'),
    dateStart: () => document.getElementById('date-start'),
    dateEnd: () => document.getElementById('date-end'),
    dataCount: () => document.getElementById('data-count'),
    connBadge: () => document.getElementById('connection-badge'),
    modeBadge: () => document.getElementById('mode-badge'),
    deviceBadge: () => document.getElementById('device-badge'),
    deviceLastSeen: () => document.getElementById('device-last-seen'),
    deviceStatusBadgeNav: () => document.getElementById('device-status-badge'),
    deviceStatusText: () => document.getElementById('device-status-text'),
    deviceIcon: () => document.getElementById('device-icon'),
    deviceCard: () => document.getElementById('device-status-card'),
    btnModeDummy: () => document.getElementById('btn-mode-dummy'),
    btnModeReal: () => document.getElementById('btn-mode-real'),
    clearModeLabel: () => document.getElementById('clear-mode-label'),
};


// ══════════════════════════════════════════════════════════
//  DATA MODE MANAGEMENT (Admin only — switch via server)
// ══════════════════════════════════════════════════════════

async function switchMode(mode) {
    if (mode === currentMode) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch('/switch-mode', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ mode }),
        });

        if (!res.ok) throw new Error('Gagal switch mode');

        currentMode = mode;

        // Reset selection and table page
        selectedIds.clear();
        currentTablePage = 1;

        // Update UI
        updateModeUI();

        // Re-fetch data for new mode
        fetchSensorData();

    } catch (err) {
        console.error('Switch mode error:', err);
        showError('Gagal mengganti mode data.');
    }
}

function updateModeUI() {
    const btnDummy = DOM.btnModeDummy();
    const btnReal = DOM.btnModeReal();
    const badge = DOM.modeBadge();
    const clearLabel = DOM.clearModeLabel();
    const simCard = document.getElementById('simulator-control-card');

    // Toggle active class on buttons
    if (btnDummy && btnReal) {
        btnDummy.classList.toggle('active', currentMode === 'dummy');
        btnReal.classList.toggle('active', currentMode === 'real');
    }

    // Update navbar badge
    if (badge) {
        if (currentMode === 'dummy') {
            badge.textContent = '🧪 Dummy';
            badge.className = 'badge mode-badge mode-badge-dummy';
        } else {
            badge.textContent = '📡 Real';
            badge.className = 'badge mode-badge mode-badge-real';
        }
    }

    // Update clear button label
    if (clearLabel) {
        clearLabel.textContent = currentMode === 'dummy' ? 'Dummy' : 'Real';
    }

    // Toggle simulator control card visibility
    if (simCard) {
        if (currentMode === 'dummy') {
            simCard.classList.remove('d-none');
        } else {
            simCard.classList.add('d-none');
            // Hentikan simulator jika sedang berjalan saat berpindah ke mode real
            stopSimulator();
        }
    }
}

async function clearModeData() {
    // Show the delete modal
    const modal = document.getElementById('delete-modal');
    if (modal) {
        // Reset modal state
        document.getElementById('delete-date-toggle').checked = false;
        document.getElementById('delete-date-range').style.display = 'none';
        document.getElementById('delete-from').value = '';
        document.getElementById('delete-to').value = '';

        const modeLabel = currentMode === 'dummy' ? 'Dummy (Pengujian)' : 'Real (Sensor)';
        document.getElementById('delete-modal-mode').textContent = modeLabel;

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

async function confirmDeleteData() {
    const useDateRange = document.getElementById('delete-date-toggle').checked;
    const fromDate = document.getElementById('delete-from').value;
    const toDate = document.getElementById('delete-to').value;
    const label = currentMode === 'dummy' ? 'Dummy (Pengujian)' : 'Real (Sensor)';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const body = { source: currentMode };
        if (useDateRange) {
            if (fromDate) body.from_date = fromDate;
            if (toDate) body.to_date = toDate;
        }

        const res = await fetch('/sensor-data/clear', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });

        if (!res.ok) {
            const errData = await res.json().catch(() => null);
            throw new Error(errData?.message || `HTTP ${res.status}: ${res.statusText}`);
        }

        const result = await res.json();

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('delete-modal'));
        if (modal) modal.hide();

        showSuccess(result.message || `Berhasil menghapus data ${label}.`);

        // Re-fetch data
        fetchSensorData();

    } catch (err) {
        console.error('Clear data error:', err);
        showError(err.message || 'Gagal menghapus data.');
    }
}


// ══════════════════════════════════════════════════════════
//  DATA FETCHING
// ══════════════════════════════════════════════════════════

async function fetchSensorData() {
    try {
        const res = await fetch(`${CONFIG.API_URL}?source=${currentMode}`);

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }

        const data = await res.json();
        allData = Array.isArray(data) ? data : [];

        // Update connection badge
        setConnectionStatus(true);

        // Apply filter if set
        const filtered = getFilteredData();

        updateStatCards(filtered);
        updateStatus(filtered);
        renderCharts(filtered);
        renderTable(filtered);
        updateMeta(filtered);
        hideLoading();

    } catch (err) {
        console.error('Fetch error:', err);
        setConnectionStatus(false);
        showError(err.message || 'Gagal memuat data dari server.');
        hideLoading();
    }
}

// ── Update Stat Cards with smooth animation ─────────────
function updateStatCards(data) {
    if (!data || data.length === 0) {
        animateValue(DOM.valMoisture(), '--');
        animateValue(DOM.valTemp(), '--');
        animateValue(DOM.valHumidity(), '--');
        animateValue(DOM.valLight(), '--');
        updateLightStatus(null);
        return;
    }

    const latest = data[0]; // latest() returns newest first

    animateValue(DOM.valMoisture(), parseFloat(latest.soil_moisture).toFixed(1));
    animateValue(DOM.valTemp(), parseFloat(latest.temperature).toFixed(1));
    animateValue(DOM.valHumidity(), parseFloat(latest.humidity).toFixed(1));
    animateValue(DOM.valLight(), parseFloat(latest.light_intensity).toFixed(0));
    updateLightStatus(parseFloat(latest.light_intensity));
}

/**
 * Update the premium Glassmorphic LDR Status Card
 */
function updateLightStatus(lightVal) {
    const card = document.getElementById('ldr-status-card');
    const icon = document.getElementById('ldr-icon');
    const statusText = document.getElementById('ldr-status-text');
    const subText = document.getElementById('ldr-sub-text');

    if (!card || !statusText) return;

    if (lightVal === null || isNaN(lightVal)) {
        statusText.textContent = 'Menunggu data...';
        subText.textContent = 'Memuat kondisi cahaya...';
        card.className = 'card status-card light-status-card h-100';
        if (icon) {
            icon.className = 'fs-2';
            icon.innerHTML = '☀️';
        }
        return;
    }

    const now = new Date();
    const hour = now.getHours();
    const isDaytime = hour >= 6 && hour < 18;

    if (lightVal >= 100) {
        if (isDaytime) {
            // SIANG & TERANG (Cahaya Cukup)
            card.className = 'card status-card light-status-card h-100 state-terang';
            statusText.textContent = 'Cahaya Cukup';
            subText.textContent = 'Ideal untuk fotosintesis Jambu Kristal';
            if (icon) {
                icon.className = 'fs-2 spin-slow';
                icon.innerHTML = '☀️';
            }
        } else {
            // MALAM & TERANG (Terdeteksi Pencahayaan Buatan)
            card.className = 'card status-card light-status-card h-100 state-terang-malam';
            statusText.textContent = 'Cahaya Cukup (Lampu Aktif)';
            subText.textContent = 'Terdeteksi pencahayaan buatan di kebun';
            if (icon) {
                icon.className = 'fs-2 pulse-glow-yellow';
                icon.innerHTML = '💡';
            }
        }
    } else {
        if (isDaytime) {
            // SIANG & GELAP (Mendung / Teduh)
            card.className = 'card status-card light-status-card h-100 state-gelap-siang';
            statusText.textContent = 'Cahaya Minim (Mendung/Teduh)';
            subText.textContent = 'Kondisi kebun mendung atau sensor terhalang';
            if (icon) {
                icon.className = 'fs-2 pulse-slow';
                icon.innerHTML = '☁️';
            }
        } else {
            // MALAM & GELAP (Malam Hari Normal)
            card.className = 'card status-card light-status-card h-100 state-gelap';
            statusText.textContent = 'Cahaya Minim / Malam Hari';
            subText.textContent = 'Sistem dalam mode monitoring malam';
            if (icon) {
                icon.className = 'fs-2 pulse-glow-slow';
                icon.innerHTML = '🌑';
            }
        }
    }
}

/**
 * Animate stat card value change with counting + pulse effect
 */
function animateValue(element, newValue) {
    if (!element) return;

    const oldValue = element.textContent;
    if (oldValue === newValue) return; // no change, skip animation

    const oldNum = parseFloat(oldValue);
    const newNum = parseFloat(newValue);

    // If both are numbers, do counting animation
    if (!isNaN(oldNum) && !isNaN(newNum)) {
        const duration = 600; // ms
        const startTime = performance.now();
        const decimals = newValue.includes('.') ? newValue.split('.')[1].length : 0;

        // Add pulse class to parent card
        const card = element.closest('.stat-card');
        if (card) {
            card.classList.add('stat-updating');
            setTimeout(() => card.classList.remove('stat-updating'), duration + 100);
        }

        function step(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = oldNum + (newNum - oldNum) * eased;

            element.textContent = current.toFixed(decimals);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = newValue;
            }
        }

        requestAnimationFrame(step);
    } else {
        // Non-numeric, just swap with fade
        element.style.opacity = '0';
        element.style.transform = 'translateY(5px)';
        setTimeout(() => {
            element.textContent = newValue;
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 150);
    }
}

// ── Update Status & Warning ──────────────────────────────
function updateStatus(data) {
    const badge = DOM.statusBadge();
    const warning = DOM.tempWarning();
    const warnTxt = DOM.tempWarnText();

    if (!data || data.length === 0) {
        badge.textContent = 'Menunggu data...';
        badge.className = 'badge status-badge fs-6';
        warning.classList.add('d-none');
        warning.classList.remove('show');
        return;
    }

    const latest = data[0];
    const moisture = parseFloat(latest.soil_moisture);
    const temp = parseFloat(latest.temperature);

    // Determine condition (threshold konsisten: <20 Kritis, <30 Perlu Penyiraman)
    let status, cssClass;

    if (moisture < 20) {
        status = '🔴 Kritis';
        cssClass = 'badge-critical';
    } else if (moisture < 30) {
        status = '🟠 Perlu Penyiraman';
        cssClass = 'badge-warning';
    } else {
        status = '🟢 Normal';
        cssClass = 'badge-normal';
    }

    badge.textContent = status;
    badge.className = `badge status-badge fs-6 ${cssClass}`;

    // Temperature warning
    if (temp > 35) {
        warnTxt.textContent = `Suhu saat ini ${temp.toFixed(1)}°C — melebihi batas aman 35°C!`;
        warning.classList.remove('d-none');
        warning.classList.add('show');
    } else {
        warning.classList.add('d-none');
        warning.classList.remove('show');
    }
}


// ══════════════════════════════════════════════════════════
//  CHART RENDERING
// ══════════════════════════════════════════════════════════

// Called by common.js when theme changes
function rebuildChartsForTheme() {
    if (allData.length > 0) {
        const filtered = getFilteredData();
        renderCharts(filtered);
    }
}

function renderCharts(data) {
    // Read theme colours from CSS variables
    const tc = getChartThemeColors();

    // Reverse so chronological order (oldest → newest) for line charts
    const sorted = [...(data || [])].reverse().slice(-CONFIG.CHART_MAX_POINTS);

    const labels = sorted.map(d => {
        const dt = new Date(d.created_at);
        return dt.toLocaleString('id-ID', {
            day: '2-digit', month: 'short',
            hour: '2-digit', minute: '2-digit'
        });
    });

    const moistureVals = sorted.map(d => parseFloat(d.soil_moisture));
    const tempVals = sorted.map(d => parseFloat(d.temperature));
    const humidityVals = sorted.map(d => parseFloat(d.humidity));
    const lightVals = sorted.map(d => parseFloat(d.light_intensity));

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                labels: { color: tc.legend, font: { family: 'Inter', size: 12 } }
            },
            tooltip: {
                backgroundColor: tc.tooltipBg,
                titleColor: tc.tooltipTitle,
                bodyColor: tc.tooltipBody,
                borderColor: tc.tooltipBorder,
                borderWidth: 1,
                cornerRadius: 8,
                padding: 10,
            }
        },
        scales: {
            x: {
                ticks: { color: tc.tick, font: { size: 10 }, maxRotation: 45 },
                grid: { color: tc.grid }
            },
            y: {
                ticks: { color: tc.tick, font: { size: 11 } },
                grid: { color: tc.grid }
            }
        }
    };

    // ── Moisture Chart
    if (chartMoisture) chartMoisture.destroy();
    chartMoisture = new Chart(document.getElementById('chart-moisture'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Kelembapan Tanah (%)',
                data: moistureVals,
                borderColor: '#26a69a',
                backgroundColor: 'rgba(38, 166, 154, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#26a69a',
                tension: 0.4,
                fill: true,
            }]
        },
        options: commonOptions
    });

    // ── Temperature & Humidity Chart
    if (chartTempHumidity) chartTempHumidity.destroy();
    chartTempHumidity = new Chart(document.getElementById('chart-temp-humidity'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Suhu (°C)',
                    data: tempVals,
                    borderColor: '#ef5350',
                    backgroundColor: 'rgba(239, 83, 80, 0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#ef5350',
                    tension: 0.4,
                    fill: true,
                },
                {
                    label: 'Kelembapan Udara (%)',
                    data: humidityVals,
                    borderColor: '#42a5f5',
                    backgroundColor: 'rgba(66, 165, 245, 0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#42a5f5',
                    tension: 0.4,
                    fill: true,
                }
            ]
        },
        options: commonOptions
    });

    // ── Light Chart
    if (chartLight) chartLight.destroy();
    chartLight = new Chart(document.getElementById('chart-light'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Status Cahaya',
                data: lightVals,
                borderColor: '#ffa726',
                backgroundColor: 'rgba(255, 167, 38, 0.15)',
                borderWidth: 2.5,
                pointRadius: 3,
                pointBackgroundColor: '#ffa726',
                stepped: true,
                fill: true,
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
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
                }
            }
        }
    });
}


// ══════════════════════════════════════════════════════════
//  DATE FILTER
// ══════════════════════════════════════════════════════════

function getFilteredData() {
    const startVal = DOM.dateStart().value;
    const endVal = DOM.dateEnd().value;

    if (!startVal && !endVal) return allData;

    return allData.filter(d => {
        const dt = new Date(d.created_at).toISOString().slice(0, 10);
        if (startVal && dt < startVal) return false;
        if (endVal && dt > endVal) return false;
        return true;
    });
}

function applyDateFilter() {
    currentTablePage = 1;
    const filtered = getFilteredData();
    updateStatCards(filtered);
    updateStatus(filtered);
    renderCharts(filtered);
    renderTable(filtered);
    updateMeta(filtered);
}

function resetDateFilter() {
    DOM.dateStart().value = '';
    DOM.dateEnd().value = '';
    applyDateFilter();
}


// ══════════════════════════════════════════════════════════
//  META & UI HELPERS
// ══════════════════════════════════════════════════════════

function updateMeta(data) {
    DOM.dataCount().textContent = (data || []).length;

    if (data && data.length > 0) {
        const latest = new Date(data[0].created_at);
        const timeStr = latest.toLocaleString('id-ID', {
            day: '2-digit', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
        DOM.lastUpdated().innerHTML = `<i class="bi bi-clock me-1"></i>Terakhir diperbarui: ${timeStr}`;
    }
}

function hideLoading() {
    const overlay = DOM.overlay();
    if (overlay) overlay.classList.add('hidden');
}

function showError(message) {
    DOM.errorMsg().textContent = message;
    const toast = new bootstrap.Toast(DOM.errorToast(), { delay: 5000 });
    toast.show();
}

function showSuccess(message) {
    DOM.successMsg().textContent = message;
    const toast = new bootstrap.Toast(DOM.successToast(), { delay: 4000 });
    toast.show();
}

function setConnectionStatus(connected) {
    const badge = DOM.connBadge();
    if (connected) {
        badge.className = 'badge bg-success-subtle text-success-emphasis';
        badge.innerHTML = '<i class="bi bi-circle-fill me-1 pulse-dot"></i> Live';
    } else {
        badge.className = 'badge bg-danger-subtle text-danger-emphasis';
        badge.innerHTML = '<i class="bi bi-circle-fill me-1"></i> Offline';
    }
}


// ══════════════════════════════════════════════════════════
//  DEVICE STATUS (ESP32 Online/Offline)
// ══════════════════════════════════════════════════════════

async function fetchDeviceStatus() {
    try {
        const res = await fetch('/api/device-status', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        updateDeviceStatusUI(data);
    } catch (err) {
        console.error('Device status error:', err);
        updateDeviceStatusUI({ status: 'unknown', last_seen: null, minutes_ago: null });
    }
}

function updateDeviceStatusUI(data) {
    const badge = DOM.deviceBadge();
    const lastSeen = DOM.deviceLastSeen();
    const navBadge = DOM.deviceStatusBadgeNav();
    const navText = DOM.deviceStatusText();
    const icon = DOM.deviceIcon();
    const card = DOM.deviceCard();

    if (!badge) return;

    if (data.status === 'online') {
        // Online state
        badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Online';
        badge.className = 'badge device-badge device-online fs-6';
        if (icon) { icon.innerHTML = '📡'; icon.className = 'fs-2 pulse-slow'; }
        if (card) card.className = 'card status-card device-status-card h-100 device-state-online';

        if (navBadge) navBadge.className = 'nav-badge nav-badge-device device-nav-online';
        if (navText) navText.innerHTML = '<i class="bi bi-circle-fill pulse-dot me-1"></i>ESP32 Online';

        const agoText = formatMinutesAgo(data.minutes_ago);
        if (lastSeen) lastSeen.innerHTML = `<i class="bi bi-clock-history me-1"></i>Data terakhir: ${agoText}`;

    } else if (data.status === 'offline') {
        // Offline state
        badge.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Offline';
        badge.className = 'badge device-badge device-offline fs-6';
        if (icon) { icon.innerHTML = '⚠️'; icon.className = 'fs-2 pulse-slow'; }
        if (card) card.className = 'card status-card device-status-card h-100 device-state-offline';

        if (navBadge) navBadge.className = 'nav-badge nav-badge-device device-nav-offline';
        if (navText) navText.innerHTML = '<i class="bi bi-circle-fill me-1"></i>ESP32 Offline';

        const agoText = formatMinutesAgo(data.minutes_ago);
        if (lastSeen) lastSeen.innerHTML = `<i class="bi bi-clock-history me-1"></i>Terakhir: ${agoText}`;

    } else {
        // Unknown state
        badge.innerHTML = '<i class="bi bi-question-circle me-1"></i>Belum Ada Data';
        badge.className = 'badge device-badge device-unknown fs-6';
        if (icon) { icon.innerHTML = '⚪'; icon.className = 'fs-2'; }
        if (card) card.className = 'card status-card device-status-card h-100';

        if (navBadge) navBadge.className = 'nav-badge nav-badge-device device-nav-unknown';
        if (navText) navText.textContent = 'ESP32 N/A';

        if (lastSeen) lastSeen.innerHTML = '<i class="bi bi-clock-history me-1"></i>Belum ada data dari sensor';
    }
}

function formatMinutesAgo(minutes) {
    if (minutes === null || minutes === undefined) return '--';
    if (minutes < 1) return 'Baru saja';
    if (minutes < 60) return `${minutes} menit lalu`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours < 24) return `${hours} jam ${mins > 0 ? mins + ' menit' : ''} lalu`;
    const days = Math.floor(hours / 24);
    return `${days} hari lalu`;
}


// ══════════════════════════════════════════════════════════
//  DATA TABLE
// ══════════════════════════════════════════════════════════

function renderTable(data) {
    const tbody = document.getElementById('data-table-body');
    const info = document.getElementById('table-info');
    const pagination = document.getElementById('table-pagination');

    if (!tbody) return;

    const items = data || [];
    const totalPages = Math.max(1, Math.ceil(items.length / TABLE_PAGE_SIZE));

    // Clamp current page
    if (currentTablePage > totalPages) currentTablePage = totalPages;
    if (currentTablePage < 1) currentTablePage = 1;

    const start = (currentTablePage - 1) * TABLE_PAGE_SIZE;
    const end = Math.min(start + TABLE_PAGE_SIZE, items.length);
    const pageItems = items.slice(start, end);

    const colSpan = window.USER_IS_ADMIN ? 8 : 7;
    if (pageItems.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">Tidak ada data.</td></tr>`;
        info.textContent = 'Tidak ada data';
        pagination.innerHTML = '';
        updateDeleteButton();
        return;
    }

    // Render rows
    tbody.innerHTML = pageItems.map((d, i) => {
        const rowNum = start + i + 1;
        const dt = new Date(d.created_at);
        const timeStr = dt.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        const checked = selectedIds.has(d.id) ? 'checked' : '';

        const statusClass = d.status === 'Kritis' ? 'text-danger'
            : d.status === 'Perlu Penyiraman' ? 'text-warning'
            : 'text-success';

        const checkboxTd = window.USER_IS_ADMIN
            ? `<td><input class="form-check-input row-checkbox" type="checkbox" value="${d.id}" ${checked} onchange="onRowCheckboxChange(this)"></td>`
            : '';

        const lightVal = parseFloat(d.light_intensity);
        const lightBadge = lightVal >= 100 
            ? `<span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-sun-fill me-1 text-warning"></i> Terang</span>` 
            : `<span class="badge bg-indigo-subtle text-indigo-emphasis" style="background-color: rgba(99, 102, 241, 0.15); color: #818cf8;"><i class="bi bi-moon-stars-fill me-1" style="color: #818cf8;"></i> Gelap</span>`;

        return `<tr class="${checked ? 'table-row-selected' : ''}">
            ${checkboxTd}
            <td>${rowNum}</td>
            <td class="text-nowrap">${timeStr}</td>
            <td>${parseFloat(d.soil_moisture).toFixed(1)}%</td>
            <td>${parseFloat(d.temperature).toFixed(1)}°C</td>
            <td>${parseFloat(d.humidity).toFixed(1)}%</td>
            <td>${lightBadge}</td>
            <td><span class="${statusClass} fw-semibold">${d.status}</span></td>
        </tr>`;
    }).join('');

    // Info text
    info.textContent = `Menampilkan ${start + 1}–${end} dari ${items.length} data`;

    // Pagination
    let paginationHTML = '';
    paginationHTML += `<li class="page-item ${currentTablePage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="event.preventDefault(); goToPage(${currentTablePage - 1})">&laquo;</a></li>`;

    // Show max 5 page numbers
    let pageStart = Math.max(1, currentTablePage - 2);
    let pageEnd = Math.min(totalPages, pageStart + 4);
    if (pageEnd - pageStart < 4) pageStart = Math.max(1, pageEnd - 4);

    for (let p = pageStart; p <= pageEnd; p++) {
        paginationHTML += `<li class="page-item ${p === currentTablePage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); goToPage(${p})">${p}</a></li>`;
    }

    paginationHTML += `<li class="page-item ${currentTablePage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="event.preventDefault(); goToPage(${currentTablePage + 1})">&raquo;</a></li>`;

    pagination.innerHTML = paginationHTML;

    // Update select-all checkbox state (admin only)
    if (window.USER_IS_ADMIN) {
        const selectAll = document.getElementById('select-all');
        if (selectAll) {
            const pageCheckboxes = pageItems.map(d => d.id);
            const allPageSelected = pageCheckboxes.every(id => selectedIds.has(id));
            selectAll.checked = allPageSelected && pageCheckboxes.length > 0;
            selectAll.indeterminate = !allPageSelected && pageCheckboxes.some(id => selectedIds.has(id));
        }
    }

    updateDeleteButton();
}

function goToPage(page) {
    currentTablePage = page;
    const filtered = getFilteredData();
    renderTable(filtered);
}

function toggleSelectAll(checkbox) {
    const filtered = getFilteredData();
    const start = (currentTablePage - 1) * TABLE_PAGE_SIZE;
    const end = Math.min(start + TABLE_PAGE_SIZE, filtered.length);
    const pageItems = filtered.slice(start, end);

    pageItems.forEach(d => {
        if (checkbox.checked) {
            selectedIds.add(d.id);
        } else {
            selectedIds.delete(d.id);
        }
    });

    renderTable(filtered);
}

function onRowCheckboxChange(checkbox) {
    const id = parseInt(checkbox.value);
    if (checkbox.checked) {
        selectedIds.add(id);
    } else {
        selectedIds.delete(id);
    }

    // Update row visual
    const row = checkbox.closest('tr');
    row.classList.toggle('table-row-selected', checkbox.checked);

    // Update select-all checkbox
    const filtered = getFilteredData();
    const start = (currentTablePage - 1) * TABLE_PAGE_SIZE;
    const end = Math.min(start + TABLE_PAGE_SIZE, filtered.length);
    const pageItems = filtered.slice(start, end);
    const selectAll = document.getElementById('select-all');
    const allSelected = pageItems.every(d => selectedIds.has(d.id));
    selectAll.checked = allSelected && pageItems.length > 0;
    selectAll.indeterminate = !allSelected && pageItems.some(d => selectedIds.has(d.id));

    updateDeleteButton();
}

function updateDeleteButton() {
    if (!window.USER_IS_ADMIN) return;
    const btn = document.getElementById('btn-delete-selected');
    const badge = document.getElementById('selected-count');
    if (!btn || !badge) return;
    const count = selectedIds.size;

    badge.textContent = count;
    if (count > 0) {
        btn.classList.remove('d-none');
    } else {
        btn.classList.add('d-none');
    }
}

async function deleteSelectedRecords() {
    const count = selectedIds.size;
    if (count === 0) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const res = await fetch('/sensor-data/delete-selected', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ids: Array.from(selectedIds) }),
        });

        if (!res.ok) {
            const errData = await res.json().catch(() => null);
            throw new Error(errData?.message || `HTTP ${res.status}: ${res.statusText}`);
        }

        const result = await res.json();
        selectedIds.clear();
        showSuccess(result.message || `Berhasil menghapus ${count} record.`);

        // Re-fetch data
        fetchSensorData();

    } catch (err) {
        console.error('Delete selected error:', err);
        showError(err.message || 'Gagal menghapus data.');
    }
}


// ══════════════════════════════════════════════════════════
//  INITIALISATION
// ══════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    // Apply saved theme
    applyTheme(getTheme());

    // Sync mode UI
    updateModeUI();

    // Initial fetch
    fetchSensorData();
    fetchDeviceStatus();

    // Auto-refresh
    refreshTimer = setInterval(() => {
        fetchSensorData();
        fetchDeviceStatus();
    }, CONFIG.REFRESH_INTERVAL);
});

// ══════════════════════════════════════════════════════════
//  DUMMY SIMULATOR & GENERATOR
// ══════════════════════════════════════════════════════════

async function generateHistoricalDummy() {
    const daysInput = document.getElementById('generate-days');
    const btn = document.getElementById('btn-generate-dummy');
    if (!daysInput || !btn) return;
    
    const days = parseInt(daysInput.value);
    if (isNaN(days) || days < 1 || days > 90) {
        showError('Jumlah hari harus antara 1 sampai 90.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Generating...';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch('/sensor-data/generate-dummy', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ days }),
        });

        if (!res.ok) {
            const errData = await res.json().catch(() => null);
            throw new Error(errData?.message || `HTTP ${res.status}: ${res.statusText}`);
        }

        const result = await res.json();
        showSuccess(result.message || 'Berhasil menghasilkan data dummy.');
        
        // Re-fetch data to update charts & tables
        fetchSensorData();
    } catch (err) {
        console.error('Generate dummy error:', err);
        showError(err.message || 'Gagal menghasilkan data dummy.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-magic me-1"></i>Generate Data';
    }
}

function startSimulator() {
    if (simulatorInterval) return;

    const statusBadge = document.getElementById('simulator-status-badge');
    const btnStart = document.getElementById('btn-start-sim');
    const btnStop = document.getElementById('btn-stop-sim');

    if (statusBadge) {
        statusBadge.textContent = 'Simulator: BERJALAN (5s)';
        statusBadge.className = 'badge bg-success';
    }
    if (btnStart) btnStart.classList.add('d-none');
    if (btnStop) btnStop.classList.remove('d-none');

    showSuccess('Simulator real-time telah dimulai (tiap 5 detik).');

    simStep = 0;
    
    // Ambil nilai kelembapan tanah terakhir dari UI sebagai titik awal jika ada
    const currentSoilText = document.getElementById('val-moisture')?.textContent;
    const parsedSoil = parseFloat(currentSoilText);
    if (!isNaN(parsedSoil)) {
        lastSimSoil = parsedSoil;
    } else {
        lastSimSoil = 55.0;
    }

    simulatorInterval = setInterval(async () => {
        simStep++;
        const condition = document.getElementById('sim-condition')?.value || 'auto';
        const now = new Date();
        const hour = now.getHours() + now.getMinutes() / 60.0;

        let moisture, temp, humidity, light;

        // ── Temperature: 22-38°C, peaks at ~14:00 ──
        const tempBase = 28.0 + 7.0 * Math.sin(Math.PI * (hour - 6.0) / 12.0);
        temp = tempBase + (Math.random() * 3.0 - 1.5);
        temp = Math.max(20.0, Math.min(40.0, temp));

        // ── Humidity: inverse of temp, 50-90% ──
        const humBase = 75.0 - 15.0 * Math.sin(Math.PI * (hour - 6.0) / 12.0);
        humidity = humBase + (Math.random() * 6.0 - 3.0);
        humidity = Math.max(45.0, Math.min(95.0, humidity));

        // ── Light: Biner 2.0 (GELAP) atau 500.0 (TERANG) sesuai waktu kebun (siang/malam) ──
        if (hour >= 6.0 && hour <= 18.0) {
            light = 500.0;
        } else {
            light = 2.0;
        }

        // Determine moisture based on selected condition
        if (condition === 'normal') {
            moisture = 50.0 + (Math.random() * 10.0 - 5.0); // 45% - 55%
        } else if (condition === 'warning') {
            moisture = 24.0 + (Math.random() * 4.0 - 2.0); // 22% - 26%
        } else if (condition === 'critical') {
            moisture = 14.0 + (Math.random() * 4.0 - 2.0); // 12% - 16%
        } else {
            // Auto/fluctuate
            if (simStep % 15 === 0) {
                // watering simulation
                lastSimSoil = Math.min(80.0, lastSimSoil + 30.0);
            } else {
                lastSimSoil -= (Math.random() * 2.0 + 0.5);
            }
            lastSimSoil = Math.max(5.0, Math.min(90.0, lastSimSoil));
            moisture = lastSimSoil;
        }

        // Round
        moisture = Math.round(moisture * 10) / 10;
        temp = Math.round(temp * 10) / 10;
        humidity = Math.round(humidity * 10) / 10;
        light = Math.round(light);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/sensor-data/simulate', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    soil_moisture: moisture,
                    temperature: temp,
                    humidity: humidity,
                    light_intensity: light,
                    source: 'dummy'
                }),
            });

            if (res.ok) {
                // Instantly fetch updated values to update UI/charts
                fetchSensorData();
            }
        } catch (err) {
            console.error('Simulator tick error:', err);
        }
    }, 5000);
}

function stopSimulator() {
    if (!simulatorInterval) return;

    clearInterval(simulatorInterval);
    simulatorInterval = null;

    const statusBadge = document.getElementById('simulator-status-badge');
    const btnStart = document.getElementById('btn-start-sim');
    const btnStop = document.getElementById('btn-stop-sim');

    if (statusBadge) {
        statusBadge.textContent = 'Simulator: MATI';
        statusBadge.className = 'badge bg-secondary-subtle text-secondary-emphasis';
    }
    if (btnStart) btnStart.classList.remove('d-none');
    if (btnStop) btnStop.classList.add('d-none');

    showSuccess('Simulator real-time telah dihentikan.');
}
