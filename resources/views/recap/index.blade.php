@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">

    {{-- Alert Flash Error / Info --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
            <strong>Parameter Filter Tidak Valid:</strong>
        </div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0 fw-bold" style="color: var(--text-primary);">
                    <i class="bi bi-file-earmark-bar-graph me-2" style="color: var(--green-400);"></i>Laporan Rekap Kondisi Lingkungan
                </h1>
                <span class="badge {{ $mode === 'real' ? 'bg-success' : 'bg-warning text-dark' }} px-2 py-1">
                    {{ $mode === 'real' ? '📡 Data Real IoT' : '🧪 Data Dummy' }}
                </span>
            </div>
            <p class="mb-0 small" style="color: var(--text-muted);">
                Ringkasan analitis data historis sensor untuk evaluasi dan dukungan keputusan budidaya kebun jambu kristal.
            </p>
        </div>

        {{-- Export Action Buttons --}}
        <div class="d-flex flex-wrap gap-2">
            @php
                $exportParams = array_filter([
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'mode'       => $mode,
                ]);
            @endphp
            <a href="{{ route('recap.export.pdf', $exportParams) }}"
               class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-2 {{ !$recap['has_data'] ? 'disabled' : '' }}"
               title="Unduh Laporan Format PDF">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>Ekspor PDF Rekap</span>
            </a>
            <a href="{{ route('recap.export.csv', $exportParams) }}"
               class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-2 {{ !$recap['has_data'] ? 'disabled' : '' }}"
               title="Unduh Agregasi Format CSV (Bukan Raw Data)">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                <span>Ekspor CSV Rekap</span>
            </a>
        </div>
    </div>

    {{-- Card Filter Periode --}}
    <div class="card filter-card mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('recap.index') }}" method="GET" id="recap-filter-form" class="row g-3 align-items-end">
                <input type="hidden" name="mode" value="{{ $mode }}">

                <div class="col-12 col-sm-6 col-md-3">
                    <label for="start_date" class="form-label small fw-semibold" style="color: var(--text-secondary);">Tanggal Mulai</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="background: var(--bg-secondary); border-color: var(--form-border); color: var(--text-muted);">
                            <i class="bi bi-calendar-event"></i>
                        </span>
                        <input type="date"
                               class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                               id="start_date"
                               name="start_date"
                               value="{{ $startDate ?? '' }}"
                               max="{{ date('Y-m-d') }}"
                               style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text);">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label for="end_date" class="form-label small fw-semibold" style="color: var(--text-secondary);">Tanggal Akhir</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="background: var(--bg-secondary); border-color: var(--form-border); color: var(--text-muted);">
                            <i class="bi bi-calendar-check"></i>
                        </span>
                        <input type="date"
                               class="form-control form-control-sm @error('end_date') is-invalid @enderror"
                               id="end_date"
                               name="end_date"
                               value="{{ $endDate ?? '' }}"
                               max="{{ date('Y-m-d') }}"
                               style="background: var(--form-bg); border-color: var(--form-border); color: var(--form-text);">
                    </div>
                </div>

                <div class="col-12 col-md-6 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                        <i class="bi bi-filter"></i>
                        <span>Tampilkan Laporan</span>
                    </button>
                    <a href="{{ route('recap.index', ['mode' => $mode]) }}" class="btn btn-sm btn-outline-secondary" style="color: var(--text-secondary); border-color: var(--border-color);">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>

                    {{-- Quick Presets --}}
                    <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Preset Periode">
                        <button type="button" class="btn btn-sm" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary);" onclick="setPresetDate(0)">Hari Ini</button>
                        <button type="button" class="btn btn-sm" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary);" onclick="setPresetDate(7)">7 Hari</button>
                        <button type="button" class="btn btn-sm" style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-secondary);" onclick="setPresetDate(30)">30 Hari</button>
                    </div>
                </div>
            </form>

            {{-- Info Bar Periode Aktif --}}
            <div class="border-top mt-3 pt-3 d-flex flex-wrap justify-content-between align-items-center gap-2 small" style="border-color: var(--border-color) !important; color: var(--text-muted);">
                <div>
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    <strong>Periode Terpilih:</strong>
                    @if($recap['has_data'])
                        <span class="fw-medium" style="color: var(--text-primary);">{{ $recap['period']['start_date'] }}</span> s/d
                        <span class="fw-medium" style="color: var(--text-primary);">{{ $recap['period']['end_date'] }}</span>
                        ({{ $recap['period']['total_records'] }} pembacaan sensor)
                    @else
                        <span>Tidak ada filter aktif (menampilkan keseluruhan data)</span>
                    @endif
                </div>
                @if($recap['has_data'])
                <div class="text-md-end">
                    <span>Rentang Data: <strong style="color: var(--text-primary);">{{ $recap['period']['first_record'] }}</strong> &mdash; <strong style="color: var(--text-primary);">{{ $recap['period']['last_record'] }}</strong></span>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(!$recap['has_data'])
    {{-- Empty State: Tidak ada data --}}
    <div class="card chart-card text-center py-5 my-4">
        <div class="card-body">
            <div class="display-4 text-warning mb-3">
                <i class="bi bi-clipboard-x"></i>
            </div>
            <h4 class="fw-bold mb-2" style="color: var(--text-primary);">Tidak Ada Data Pada Periode Ini</h4>
            <p class="mx-auto mb-4" style="max-width: 500px; color: var(--text-muted);">
                Tidak ditemukan rekaman data sensor pada rentang tanggal yang dipilih untuk mode <strong>{{ $mode }}</strong>.
                Silakan ubah rentang tanggal atau klik tombol reset untuk melihat data yang tersedia.
            </p>
            <a href="{{ route('recap.index', ['mode' => $mode]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i> Muat Semua Periode
            </a>
        </div>
    </div>
    @else

    {{-- BAGIAN 1: 4 METRIK STATISTIK SENSOR --}}
    <div class="row g-3 mb-4">
        {{-- Kelembapan Tanah --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card stat-moisture h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-label mb-0">Kelembapan Tanah</div>
                        <div class="stat-icon mb-0" style="font-size: 1.4rem;"><i class="bi bi-droplet-fill"></i></div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="stat-value" style="color: var(--accent-moisture);">{{ $recap['sensor_stats']['soil_moisture']['avg'] ?? '0' }}</span>
                        <span class="stat-unit">% Rata-rata</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top" style="border-color: var(--border-color) !important; font-size: 0.78rem; color: var(--text-muted);">
                        <span>Min: <strong>{{ $recap['sensor_stats']['soil_moisture']['min'] }}%</strong></span>
                        <span>Max: <strong>{{ $recap['sensor_stats']['soil_moisture']['max'] }}%</strong></span>
                        <span>Data: <strong>{{ $recap['sensor_stats']['soil_moisture']['valid_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Suhu Udara --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card stat-temp h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-label mb-0">Suhu Udara</div>
                        <div class="stat-icon mb-0" style="font-size: 1.4rem;"><i class="bi bi-thermometer-half"></i></div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="stat-value" style="color: var(--accent-temp);">{{ $recap['sensor_stats']['temperature']['avg'] ?? '0' }}</span>
                        <span class="stat-unit">°C Rata-rata</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top" style="border-color: var(--border-color) !important; font-size: 0.78rem; color: var(--text-muted);">
                        <span>Min: <strong>{{ $recap['sensor_stats']['temperature']['min'] }}°C</strong></span>
                        <span>Max: <strong>{{ $recap['sensor_stats']['temperature']['max'] }}°C</strong></span>
                        <span>Data: <strong>{{ $recap['sensor_stats']['temperature']['valid_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kelembapan Udara --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card stat-humidity h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-label mb-0">Kelembapan Udara</div>
                        <div class="stat-icon mb-0" style="font-size: 1.4rem;"><i class="bi bi-moisture"></i></div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="stat-value" style="color: var(--accent-humidity);">{{ $recap['sensor_stats']['humidity']['avg'] ?? '0' }}</span>
                        <span class="stat-unit">% Rata-rata</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top" style="border-color: var(--border-color) !important; font-size: 0.78rem; color: var(--text-muted);">
                        <span>Min: <strong>{{ $recap['sensor_stats']['humidity']['min'] }}%</strong></span>
                        <span>Max: <strong>{{ $recap['sensor_stats']['humidity']['max'] }}%</strong></span>
                        <span>Data: <strong>{{ $recap['sensor_stats']['humidity']['valid_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kondisi Cahaya (LM393) --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card stat-light h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-label mb-0">Kondisi Cahaya (LM393)</div>
                        <div class="stat-icon mb-0" style="font-size: 1.4rem;"><i class="bi bi-sun-fill"></i></div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="stat-value" style="color: var(--accent-light);">{{ $recap['sensor_stats']['light']['terang_pct'] }}%</span>
                        <span class="stat-unit">Kondisi Terang</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top" style="border-color: var(--border-color) !important; font-size: 0.78rem; color: var(--text-muted);">
                        <span>Terang: <strong>{{ $recap['sensor_stats']['light']['terang_count'] }}</strong></span>
                        <span>Gelap: <strong>{{ $recap['sensor_stats']['light']['gelap_count'] }}</strong></span>
                        <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #facc15;">Diskrit</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: DETAIL STATUS TANAH & PERINGATAN SUHU --}}
    <div class="row g-3 mb-4">
        {{-- Card Rekap Kelembapan Tanah --}}
        <div class="col-12 col-lg-7">
            <div class="card chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <span class="fw-bold" style="color: var(--text-primary);">
                        <i class="bi bi-pie-chart-fill text-success me-2"></i>
                        Rekap Status Kelembapan Tanah
                    </span>
                    @if($recap['soil_recap']['last_reading_time'])
                    <span class="badge" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                        Terakhir: {{ $recap['soil_recap']['last_reading_time'] }} ({{ $recap['soil_recap']['last_soil_moisture'] }}%)
                    </span>
                    @endif
                </div>
                <div class="card-body p-3 p-md-4">
                    {{-- Progress Bar Proporsi Status --}}
                    <div class="progress mb-3" style="height: 14px; background: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <div class="progress-bar bg-success"
                             role="progressbar"
                             style="width: {{ $recap['soil_recap']['normal_pct'] }}%"
                             title="Normal: {{ $recap['soil_recap']['normal_pct'] }}%"></div>
                        <div class="progress-bar bg-warning text-dark"
                             role="progressbar"
                             style="width: {{ $recap['soil_recap']['perlu_penyiraman_pct'] }}%"
                             title="Perlu Penyiraman: {{ $recap['soil_recap']['perlu_penyiraman_pct'] }}%"></div>
                        <div class="progress-bar bg-danger"
                             role="progressbar"
                             style="width: {{ $recap['soil_recap']['kritis_pct'] }}%"
                             title="Kritis: {{ $recap['soil_recap']['kritis_pct'] }}%"></div>
                    </div>

                    {{-- Tabel Rincian Kategori --}}
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" style="color: var(--text-primary);">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.78rem;">
                                    <th>Kategori Status</th>
                                    <th>Ambang Batas</th>
                                    <th class="text-end">Jumlah Pembacaan</th>
                                    <th class="text-end">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td>
                                        <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);">
                                            <i class="bi bi-check-circle me-1"></i>Normal
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted);">&ge; 30.0%</td>
                                    <td class="text-end fw-semibold">{{ $recap['soil_recap']['normal_count'] }} kali</td>
                                    <td class="text-end fw-bold" style="color: #4ade80;">{{ $recap['soil_recap']['normal_pct'] }}%</td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td>
                                        <span class="badge" style="background: rgba(249, 115, 22, 0.15); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.3);">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Perlu Penyiraman
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted);">20.0% s/d &lt; 30.0%</td>
                                    <td class="text-end fw-semibold">{{ $recap['soil_recap']['perlu_penyiraman_count'] }} kali</td>
                                    <td class="text-end fw-bold" style="color: #fb923c;">{{ $recap['soil_recap']['perlu_penyiraman_pct'] }}%</td>
                                </tr>
                                <tr style="border-bottom: none;">
                                    <td>
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
                                            <i class="bi bi-x-octagon me-1"></i>Kritis
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted);">&lt; 20.0%</td>
                                    <td class="text-end fw-semibold">{{ $recap['soil_recap']['kritis_count'] }} kali</td>
                                    <td class="text-end fw-bold" style="color: #f87171;">{{ $recap['soil_recap']['kritis_pct'] }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 p-2 rounded small" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-muted);">
                        <i class="bi bi-shield-check me-1 text-primary"></i>
                        <strong>Aturan Prioritas Sistem:</strong> Ambang status dievaluasi secara eksklusif (Kritis &lt; 20%, Perlu Penyiraman 20% &ndash; 29.9%, Normal &ge; 30%).
                        <em>Catatan: Jumlah pembacaan menunjukkan frekuensi data berkala per interval transmisi, bukan durasi jam kontinu.</em>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Rekap Peringatan Suhu --}}
        <div class="col-12 col-lg-5">
            <div class="card chart-card h-100">
                <div class="card-header py-3">
                    <span class="fw-bold" style="color: var(--text-primary);">
                        <i class="bi bi-thermometer-high text-danger me-2"></i>
                        Rekap Peringatan Suhu Udara
                    </span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="p-3 mb-3 rounded" style="background: var(--temp-warn-bg); border: 1px solid var(--temp-warn-border); color: var(--temp-warn-text);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="small d-block" style="opacity: 0.9;">Pembacaan Suhu Tinggi (&gt; 35.0°C)</span>
                                <h3 class="fw-bold mb-0">
                                    {{ $recap['temp_recap']['high_temp_count'] }}
                                    <span class="fs-6 fw-normal" style="opacity: 0.85;">kali ({{ $recap['temp_recap']['high_temp_pct'] }}%)</span>
                                </h3>
                            </div>
                            <div class="display-6">
                                <i class="bi {{ $recap['temp_recap']['high_temp_count'] > 0 ? 'bi-exclamation-triangle-fill' : 'bi-shield-fill-check' }}"></i>
                            </div>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-0 d-flex justify-content-between" style="background: transparent; border-color: var(--border-color); color: var(--text-primary);">
                            <span style="color: var(--text-muted);">Ambang Peringatan Sistem:</span>
                            <strong>&gt; 35.0 °C</strong>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between" style="background: transparent; border-color: var(--border-color); color: var(--text-primary);">
                            <span style="color: var(--text-muted);">Suhu Maksimum Periode:</span>
                            <strong style="color: var(--accent-temp);">{{ $recap['temp_recap']['max_temp'] ?? '-' }} °C</strong>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between" style="background: transparent; border-color: var(--border-color); color: var(--text-primary);">
                            <span style="color: var(--text-muted);">Waktu Suhu Maksimum:</span>
                            <span>{{ $recap['temp_recap']['max_temp_time'] ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between" style="background: transparent; border-color: var(--border-color); color: var(--text-primary);">
                            <span style="color: var(--text-muted);">Total Pembacaan Suhu Valid:</span>
                            <span>{{ $recap['temp_recap']['total_valid'] }} record</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 3: GRAFIK TREN KONDISI LINGKUNGAN HARIAN --}}
    <div class="card chart-card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <span class="fw-bold" style="color: var(--text-primary);">
                    <i class="bi bi-graph-up text-primary me-2"></i>
                    Tren Rata-Rata Harian Kondisi Lingkungan
                </span>
                <span class="d-block text-muted small" style="text-transform: none; letter-spacing: normal;">Agregasi harian parameter lingkungan selama periode laporan</span>
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Toggle Chart Parameter">
                <button type="button" class="btn btn-outline-success active" id="btn-chart-soil" onclick="switchChartParameter('soil')">Kelembapan Tanah</button>
                <button type="button" class="btn btn-outline-danger" id="btn-chart-temp" onclick="switchChartParameter('temp')">Suhu Udara</button>
                <button type="button" class="btn btn-outline-info" id="btn-chart-hum" onclick="switchChartParameter('hum')">Kelembapan Udara</button>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="recapTrendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- BAGIAN 4: KESIMPULAN & REKOMENDASI KEPUTUSAN BUDIDAYA --}}
    <div class="card chart-card mb-4" style="border-left: 4px solid var(--green-500) !important;">
        <div class="card-header py-3">
            <span class="fw-bold" style="color: var(--green-400);">
                <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                Ringkasan dan Interpretasi Keputusan Budidaya Jambu Kristal
            </span>
            <span class="d-block text-muted small" style="text-transform: none; letter-spacing: normal;">Penerjemahan fakta data agregat menjadi acuan keputusan agronomis berbasis aturan sistem</span>
        </div>
        <div class="card-body p-3 p-md-4">
            {{-- Ringkasan Eksekutif --}}
            <div class="p-3 rounded mb-3" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                <h6 class="fw-bold mb-1" style="color: var(--green-400);">
                    <i class="bi bi-card-text me-1"></i> Ringkasan Umum Periode
                </h6>
                <p class="mb-0 small" style="color: var(--text-secondary);">
                    {{ $recap['recommendations']['summary'] }}
                </p>
            </div>

            <div class="row g-3">
                {{-- Fakta Hasil Pengukuran --}}
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold small text-uppercase mb-2" style="color: var(--accent-humidity);">
                        <i class="bi bi-clipboard2-data me-1"></i> Fakta Pengukuran Sensor
                    </h6>
                    <ul class="list-group list-group-flush small">
                        @foreach($recap['recommendations']['facts'] as $fact)
                        <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2" style="background: transparent; border-color: var(--border-color); color: var(--text-secondary);">
                            <i class="bi bi-check-circle-fill mt-1 text-primary"></i>
                            <span>{{ $fact }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Saran Tindakan Budidaya --}}
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold small text-uppercase mb-2" style="color: var(--green-400);">
                        <i class="bi bi-tree me-1"></i> Rekomendasi Tindakan Agronomi
                    </h6>
                    <ul class="list-group list-group-flush small">
                        @foreach($recap['recommendations']['actions'] as $action)
                        <li class="list-group-item px-0 py-2 d-flex align-items-start gap-2" style="background: transparent; border-color: var(--border-color); color: var(--text-secondary);">
                            <i class="bi bi-arrow-right-circle-fill mt-1 text-success"></i>
                            <span>{{ $action }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Batasan Teknis / Disclaimer --}}
            <div class="border-top mt-3 pt-3 small" style="border-color: var(--border-color) !important; color: var(--text-muted);">
                <i class="bi bi-info-circle me-1"></i>
                {{ $recap['recommendations']['disclaimer'] }}
            </div>
        </div>
    </div>

    @endif

</div>
@endsection

@section('scripts')
<script>
/**
 * Helper untuk tombol preset tanggal (Hari Ini, 7 Hari, 30 Hari).
 */
function setPresetDate(daysAgo) {
    const endInput = document.getElementById('end_date');
    const startInput = document.getElementById('start_date');

    const now = new Date();
    const endDateStr = now.toISOString().split('T')[0];
    endInput.value = endDateStr;

    if (daysAgo === 0) {
        startInput.value = endDateStr;
    } else {
        const past = new Date();
        past.setDate(past.getDate() - daysAgo);
        startInput.value = past.toISOString().split('T')[0];
    }

    document.getElementById('recap-filter-form').submit();
}

@if($recap['has_data'] && !empty($recap['daily_trends']))
/**
 * Data Tren Harian dari Server
 */
const dailyTrendsData = @json($recap['daily_trends']);

const chartLabels = dailyTrendsData.map(d => d.label);
const soilMoistureData = dailyTrendsData.map(d => d.soil_avg);
const temperatureData = dailyTrendsData.map(d => d.temp_avg);
const humidityData = dailyTrendsData.map(d => d.hum_avg);

let trendChart = null;

const chartConfigs = {
    soil: {
        label: 'Kelembapan Tanah Rata-Rata (%)',
        data: soilMoistureData,
        borderColor: '#26a69a',
        backgroundColor: 'rgba(38, 166, 154, 0.15)',
        unit: '%',
    },
    temp: {
        label: 'Suhu Udara Rata-Rata (°C)',
        data: temperatureData,
        borderColor: '#ef5350',
        backgroundColor: 'rgba(239, 83, 80, 0.15)',
        unit: '°C',
    },
    hum: {
        label: 'Kelembapan Udara Rata-Rata (%)',
        data: humidityData,
        borderColor: '#42a5f5',
        backgroundColor: 'rgba(66, 165, 245, 0.15)',
        unit: '%',
    }
};

let currentChartParam = 'soil';

function getThemeColors() {
    if (typeof getChartThemeColors === 'function') {
        return getChartThemeColors();
    }
    return {
        grid: 'rgba(255, 255, 255, 0.08)',
        tick: '#94a3b8',
        legend: '#f8fafc',
    };
}

function initRecapTrendChart() {
    const ctx = document.getElementById('recapTrendChart');
    if (!ctx) return;

    if (trendChart) {
        trendChart.destroy();
    }

    const activeConfig = chartConfigs[currentChartParam];
    const themeColors = getThemeColors();

    trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: activeConfig.label,
                data: activeConfig.data,
                borderColor: activeConfig.borderColor,
                backgroundColor: activeConfig.backgroundColor,
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: themeColors.legend,
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y} ${activeConfig.unit}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: themeColors.grid,
                    },
                    ticks: {
                        color: themeColors.tick,
                    }
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: themeColors.tick,
                    }
                }
            }
        }
    });
}

function switchChartParameter(param) {
    if (!trendChart || !chartConfigs[param]) return;
    currentChartParam = param;

    const conf = chartConfigs[param];
    trendChart.data.datasets[0].label = conf.label;
    trendChart.data.datasets[0].data = conf.data;
    trendChart.data.datasets[0].borderColor = conf.borderColor;
    trendChart.data.datasets[0].backgroundColor = conf.backgroundColor;
    trendChart.options.plugins.tooltip.callbacks.label = function(context) {
        return `${context.dataset.label}: ${context.parsed.y} ${conf.unit}`;
    };
    trendChart.update();

    // Update active button state
    document.getElementById('btn-chart-soil').classList.toggle('active', param === 'soil');
    document.getElementById('btn-chart-temp').classList.toggle('active', param === 'temp');
    document.getElementById('btn-chart-hum').classList.toggle('active', param === 'hum');
}

/**
 * Rebuild chart saat user mengganti tema dark/light
 */
window.rebuildChartsForTheme = function() {
    initRecapTrendChart();
};

document.addEventListener('DOMContentLoaded', function() {
    initRecapTrendChart();
});
@endif
</script>
@endsection
