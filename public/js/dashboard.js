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

// ── Update Stat Cards ────────────────────────────────────
function updateStatCards(data) {
    if (!data || data.length === 0) {
        DOM.valMoisture().textContent = '--';
        DOM.valTemp().textContent = '--';
        DOM.valHumidity().textContent = '--';
        DOM.valLight().textContent = '--';
        return;
    }

    const latest = data[0]; // latest() returns newest first

    DOM.valMoisture().textContent = parseFloat(latest.soil_moisture).toFixed(1);
    DOM.valTemp().textContent = parseFloat(latest.temperature).toFixed(1);
    DOM.valHumidity().textContent = parseFloat(latest.humidity).toFixed(1);
    DOM.valLight().textContent = parseFloat(latest.light_intensity).toFixed(0);
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
                label: 'Intensitas Cahaya (lux)',
                data: lightVals,
                borderColor: '#ffa726',
                backgroundColor: 'rgba(255, 167, 38, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#ffa726',
                tension: 0.4,
                fill: true,
            }]
        },
        options: commonOptions
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

        return `<tr class="${checked ? 'table-row-selected' : ''}">
            ${checkboxTd}
            <td>${rowNum}</td>
            <td class="text-nowrap">${timeStr}</td>
            <td>${parseFloat(d.soil_moisture).toFixed(1)}%</td>
            <td>${parseFloat(d.temperature).toFixed(1)}°C</td>
            <td>${parseFloat(d.humidity).toFixed(1)}%</td>
            <td>${parseFloat(d.light_intensity).toFixed(0)} lux</td>
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

    // Auto-refresh
    refreshTimer = setInterval(fetchSensorData, CONFIG.REFRESH_INTERVAL);
});
