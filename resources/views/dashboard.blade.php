@extends('layouts.app')

@section('content')

{{-- Loading Overlay --}}
<div id="loading-overlay" class="loading-overlay">
    <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-3 text-light">Memuat data sensor...</p>
</div>

{{-- Error Toast --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="error-toast" class="toast align-items-center text-bg-danger border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="error-message">Gagal memuat data dari server.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

{{-- Success Toast --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="success-toast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="success-message"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin())
{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="delete-modal" tabindex="-1" aria-labelledby="delete-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);">
            <div class="modal-header border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title" id="delete-modal-label">
                    <i class="bi bi-trash3 text-danger me-2"></i>Hapus Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" style="color: var(--text-secondary);">
                    Mode aktif: <strong id="delete-modal-mode">Dummy</strong>
                </p>

                {{-- Toggle: Hapus Semua vs Per Tanggal --}}
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="delete-date-toggle"
                           onchange="document.getElementById('delete-date-range').style.display = this.checked ? 'block' : 'none'">
                    <label class="form-check-label" for="delete-date-toggle" style="color: var(--text-secondary);">
                        Hapus per tanggal (opsional)
                    </label>
                </div>

                {{-- Date Range Fields (hidden by default) --}}
                <div id="delete-date-range" style="display: none;">
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="delete-from" class="form-label small" style="color: var(--text-secondary);">Dari Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="delete-from"
                                   style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text);">
                        </div>
                        <div class="col-6">
                            <label for="delete-to" class="form-label small" style="color: var(--text-secondary);">Sampai Tanggal</label>
                            <input type="date" class="form-control form-control-sm" id="delete-to"
                                   style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text);">
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block">Kosongkan untuk menghapus semua data mode ini.</small>
                </div>

                <div class="alert alert-warning mt-3 py-2 px-3" style="font-size: 0.85rem;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Data yang dihapus <strong>tidak dapat dikembalikan</strong>.
                </div>
            </div>
            <div class="modal-footer border-top" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteData()">
                    <i class="bi bi-trash3 me-1"></i>Hapus Data
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isAdmin())
{{-- Backup & Restore Modal --}}
<div class="modal fade" id="backup-modal" tabindex="-1" aria-labelledby="backup-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-primary);">
            <div class="modal-header border-bottom" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title" id="backup-modal-label">
                    <i class="bi bi-shield-lock-fill text-success me-2"></i>Backup & Restore Data Real
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                {{-- Flash messages --}}
                @if(session('backup_success'))
                <div class="alert alert-success py-2 px-3 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('backup_success') }}</span>
                </div>
                @endif
                @if(session('backup_error'))
                <div class="alert alert-danger py-2 px-3 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('backup_error') }}</span>
                </div>
                @endif

                {{-- Info ringkasan data real --}}
                <div class="p-3 rounded mb-4" style="background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.2);">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-database-fill-check text-success"></i>
                        <span class="fw-semibold" style="color: var(--text-primary);">Status Data Real Saat Ini</span>
                    </div>
                    <div class="small" style="color: var(--text-secondary);" id="backup-info-text">
                        <i class="bi bi-arrow-repeat spin-icon me-1"></i> Memuat info...
                    </div>
                </div>

                <div class="row g-4">
                    {{-- Kolom Download Backup --}}
                    <div class="col-12 col-md-6">
                        <div class="h-100 p-3 rounded" style="border: 1px solid var(--border-color); background: var(--bg-card-inner, rgba(255,255,255,0.03));">
                            <div class="mb-3">
                                <span class="fw-semibold d-flex align-items-center gap-2" style="color: var(--text-primary);">
                                    <i class="bi bi-cloud-download text-info fs-5"></i> Download Backup
                                </span>
                                <p class="small mt-1 mb-0" style="color: var(--text-secondary);">Unduh semua data real sebagai file <code>.sql</code>. File ini bisa dipakai untuk restore nanti.</p>
                            </div>
                            <form action="{{ route('backup.download') }}" method="GET">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small mb-1" style="color: var(--text-secondary);">Filter Periode (opsional)</label>
                                    <div class="d-flex gap-2">
                                        <input type="date" name="from_date" class="form-control form-control-sm"
                                               style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text);" placeholder="Dari">
                                        <input type="date" name="to_date" class="form-control form-control-sm"
                                               style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text);" placeholder="Sampai">
                                    </div>
                                    <small class="text-muted">Kosongkan untuk backup semua data.</small>
                                </div>
                                <button type="submit" class="btn btn-sm btn-info w-100 mt-1">
                                    <i class="bi bi-download me-1"></i>Download Backup (.sql)
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Kolom Restore Backup --}}
                    <div class="col-12 col-md-6">
                        <div class="h-100 p-3 rounded" style="border: 1px solid var(--border-color); background: var(--bg-card-inner, rgba(255,255,255,0.03));">
                            <div class="mb-3">
                                <span class="fw-semibold d-flex align-items-center gap-2" style="color: var(--text-primary);">
                                    <i class="bi bi-cloud-upload text-warning fs-5"></i> Restore Backup
                                </span>
                                <p class="small mt-1 mb-0" style="color: var(--text-secondary);">Upload file <code>.sql</code> backup untuk memulihkan data real yang hilang. Data duplikat (ID sama) akan dilewati otomatis.</p>
                            </div>
                            <form action="{{ route('backup.restore') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small mb-1" style="color: var(--text-secondary);">Pilih File Backup (.sql)</label>
                                    <input type="file" name="backup_file" class="form-control form-control-sm"
                                           accept=".sql,.txt"
                                           style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text);" required>
                                    <small class="text-muted">Hanya file yang dihasilkan dari fitur backup ini.</small>
                                </div>
                                <div class="alert alert-warning py-2 px-3 mt-2 mb-2" style="font-size: 0.8rem;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Data yang ID-nya sudah ada tidak akan ditimpa (aman).
                                </div>
                                <button type="submit" class="btn btn-sm btn-warning w-100">
                                    <i class="bi bi-upload me-1"></i>Restore Data
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Temperature Warning --}}
<div id="temp-warning" class="alert alert-danger alert-dismissible fade d-none mb-4 temp-warning-alert" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-thermometer-high fs-4 pulse-icon"></i>
        <div>
            <strong>⚠️ Peringatan Suhu Tinggi!</strong>
            <span id="temp-warning-text">Suhu saat ini melebihi 35°C. Segera lakukan tindakan.</span>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

{{-- Data Mode Toggle + Date Filter --}}
<div class="card filter-card mb-4">
    <div class="card-body py-3">
        @if(auth()->user()->isAdmin())
        {{-- Mode Toggle Row (Admin Only) --}}
        <div class="row align-items-center g-2 mb-3">
            <div class="col-auto">
                <label class="form-label small fw-semibold text-uppercase text-muted mb-0">
                    <i class="bi bi-database me-1"></i>Mode Data
                </label>
            </div>
            <div class="col-auto">
                <div class="btn-group mode-toggle" role="group" aria-label="Data Mode">
                    <button type="button" class="btn btn-sm mode-btn mode-btn-dummy {{ $mode === 'dummy' ? 'active' : '' }}"
                            id="btn-mode-dummy" onclick="switchMode('dummy')">
                        <i class="bi bi-flask me-1"></i> 🧪 Dummy (Pengujian)
                    </button>
                    <button type="button" class="btn btn-sm mode-btn mode-btn-real {{ $mode === 'real' ? 'active' : '' }}"
                            id="btn-mode-real" onclick="switchMode('real')">
                        <i class="bi bi-broadcast me-1"></i> 📡 Real (Sensor)
                    </button>
                </div>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-success" id="btn-backup-data"
                        data-bs-toggle="modal" data-bs-target="#backup-modal"
                        title="Backup & Restore data real">
                    <i class="bi bi-shield-lock me-1"></i>Backup Data
                </button>
                <button class="btn btn-sm btn-outline-danger" id="btn-clear-data" onclick="clearModeData()">
                    <i class="bi bi-trash3 me-1"></i>Hapus Data <span id="clear-mode-label">{{ ucfirst($mode) }}</span>
                </button>
            </div>
        </div>
        @endif

        {{-- Date Filter Row --}}
        <div class="row align-items-end g-3">
            <div class="col-auto">
                <label class="form-label small fw-semibold text-uppercase text-muted mb-1">
                    <i class="bi bi-funnel me-1"></i>Filter Data
                </label>
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2">
                <label for="date-start" class="form-label small mb-1">Dari Tanggal</label>
                <input type="date" class="form-control form-control-sm" id="date-start">
            </div>
            <div class="col-sm-6 col-md-3 col-lg-2">
                <label for="date-end" class="form-label small mb-1">Sampai Tanggal</label>
                <input type="date" class="form-control form-control-sm" id="date-end">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-sm btn-success" id="btn-filter" onclick="applyDateFilter()">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-reset" onclick="resetDateFilter()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                @if(auth()->user()->isAdmin())
                <button class="btn btn-sm btn-outline-info" id="btn-export" onclick="exportCSV()">
                    <i class="bi bi-download me-1"></i>Export CSV
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    {{-- Soil Moisture --}}
    <div class="col-6 col-lg-3">
        <div class="card stat-card stat-moisture">
            <div class="card-body">
                <div class="stat-icon"><i class="bi bi-droplet-fill"></i></div>
                <div class="stat-label">Kelembapan Tanah</div>
                <div class="stat-value" id="val-moisture">--</div>
                <div class="stat-unit">%</div>
            </div>
        </div>
    </div>
    {{-- Temperature --}}
    <div class="col-6 col-lg-3">
        <div class="card stat-card stat-temp">
            <div class="card-body">
                <div class="stat-icon"><i class="bi bi-thermometer-half"></i></div>
                <div class="stat-label">Suhu Udara</div>
                <div class="stat-value" id="val-temp">--</div>
                <div class="stat-unit">°C</div>
            </div>
        </div>
    </div>
    {{-- Humidity --}}
    <div class="col-6 col-lg-3">
        <div class="card stat-card stat-humidity">
            <div class="card-body">
                <div class="stat-icon"><i class="bi bi-moisture"></i></div>
                <div class="stat-label">Kelembapan Udara</div>
                <div class="stat-value" id="val-humidity">--</div>
                <div class="stat-unit">%</div>
            </div>
        </div>
    </div>
    {{-- Light Intensity --}}
    <div class="col-6 col-lg-3">
        <div class="card stat-card stat-light">
            <div class="card-body">
                <div class="stat-icon"><i class="bi bi-brightness-high-fill"></i></div>
                <div class="stat-label">Intensitas Cahaya</div>
                <div class="stat-value" id="val-light">--</div>
                <div class="stat-unit">lux</div>
            </div>
        </div>
    </div>
</div>

{{-- Condition Status --}}
<div class="card status-card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-clipboard2-pulse fs-4 text-muted"></i>
            <div>
                <div class="small text-muted fw-semibold text-uppercase">Status Kondisi Tanaman</div>
                <div class="mt-1">
                    <span class="badge status-badge fs-6" id="status-badge">Menunggu data...</span>
                </div>
            </div>
        </div>
        <div class="text-muted small" id="last-updated">
            <i class="bi bi-clock me-1"></i>Terakhir diperbarui: --
        </div>
    </div>
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">
    {{-- Soil Moisture Chart --}}
    <div class="col-12 col-lg-6">
        <div class="card chart-card">
            <div class="card-header">
                <i class="bi bi-droplet me-2"></i>Kelembapan Tanah
            </div>
            <div class="card-body">
                <canvas id="chart-moisture"></canvas>
            </div>
        </div>
    </div>
    {{-- Temperature & Humidity Chart --}}
    <div class="col-12 col-lg-6">
        <div class="card chart-card">
            <div class="card-header">
                <i class="bi bi-thermometer me-2"></i>Suhu & Kelembapan Udara
            </div>
            <div class="card-body">
                <canvas id="chart-temp-humidity"></canvas>
            </div>
        </div>
    </div>
    {{-- Light Intensity Chart --}}
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <i class="bi bi-sun me-2"></i>Intensitas Cahaya
            </div>
            <div class="card-body">
                <canvas id="chart-light"></canvas>
            </div>
        </div>
    </div>
</div>


{{-- Data Table --}}
<div class="card data-table-card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-table me-1"></i>
            <span>Data Sensor</span>
        </div>
        @if(auth()->user()->isAdmin())
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-danger d-none" id="btn-delete-selected" onclick="deleteSelectedRecords()">
                <i class="bi bi-trash3 me-1"></i>Hapus Terpilih
                <span class="badge bg-danger ms-1" id="selected-count">0</span>
            </button>
        </div>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="data-table">
                <thead>
                    <tr>
                        @if(auth()->user()->isAdmin())
                        <th style="width: 40px;">
                            <input class="form-check-input" type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                        </th>
                        @endif
                        <th>No</th>
                        <th>Waktu</th>
                        <th>Kelembapan Tanah</th>
                        <th>Suhu</th>
                        <th>Kelembapan Udara</th>
                        <th>Cahaya</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="data-table-body">
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 8 : 7 }}" class="text-center text-muted py-4">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2" style="border-top: 1px solid var(--border-color);">
            <div class="text-muted small" id="table-info">Menampilkan 0 dari 0 data</div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="table-pagination"></ul>
            </nav>
        </div>
    </div>
</div>

{{-- Auto-refresh Info --}}
<div class="text-center text-muted small mb-3">
    <i class="bi bi-arrow-repeat me-1 spin-icon"></i>
    Auto-refresh setiap <strong>10 detik</strong>
    <span class="ms-2">|</span>
    <span class="ms-2">Total data: <strong id="data-count">0</strong> record</span>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/dashboard.js') }}?v={{ time() }}"></script>
<script>
@if(auth()->user()->isAdmin())
function exportCSV() {
    const from = document.getElementById('date-start').value;
    const to   = document.getElementById('date-end').value;
    const params = new URLSearchParams();
    if (from) params.append('from_date', from);
    if (to)   params.append('to_date', to);
    params.append('mode', currentMode);
    const url = '/export-sensor-data' + (params.toString() ? '?' + params.toString() : '');
    window.location.href = url;
}

// Load backup info saat modal backup dibuka
const backupModal = document.getElementById('backup-modal');
if (backupModal) {
    backupModal.addEventListener('show.bs.modal', function () {
        const infoEl = document.getElementById('backup-info-text');
        if (!infoEl) return;
        infoEl.innerHTML = '<i class="bi bi-arrow-repeat spin-icon me-1"></i> Memuat info...';
        fetch('/backup/info', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => {
            if (d.count === 0) {
                infoEl.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Belum ada data real tersimpan.</span>';
            } else {
                const oldest = d.oldest ? new Date(d.oldest).toLocaleString('id-ID') : '—';
                const newest = d.newest ? new Date(d.newest).toLocaleString('id-ID') : '—';
                infoEl.innerHTML =
                    `<i class="bi bi-database-fill me-1 text-success"></i>` +
                    `<strong>${d.count}</strong> record real &bull; ` +
                    `Terlama: <strong>${oldest}</strong> &bull; ` +
                    `Terbaru: <strong>${newest}</strong>`;
            }
        })
        .catch(() => {
            infoEl.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Gagal memuat info.</span>';
        });
    });

    // Auto-open backup modal jika ada flash session backup
    @if(session('backup_success') || session('backup_error'))
    setTimeout(() => {
        const bsModal = new bootstrap.Modal(backupModal);
        bsModal.show();
    }, 300);
    @endif
}
@endif
</script>
@endsection

