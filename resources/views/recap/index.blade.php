@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">

    {{-- Alert Flash Error / Info --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
            <strong>Parameter Filter Tidak Valid:</strong>
        </div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0 fw-bold text-gradient-primary">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Rekap Kondisi Lingkungan
                </h1>
                <span class="badge {{ $mode === 'real' ? 'bg-success' : 'bg-warning text-dark' }} px-2 py-1">
                    {{ $mode === 'real' ? '📡 Data Real IoT' : '🧪 Data Dummy' }}
                </span>
            </div>
            <p class="text-muted mb-0 small">
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
    <div class="card card-custom border-0 shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('recap.index') }}" method="GET" id="recap-filter-form" class="row g-3 align-items-end">
                <input type="hidden" name="mode" value="{{ $mode }}">

                <div class="col-12 col-sm-6 col-md-3">
                    <label for="start_date" class="form-label small fw-semibold text-muted">Tanggal Mulai</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-secondary-subtle">
                            <i class="bi bi-calendar-event"></i>
                        </span>
                        <input type="date"
                               class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                               id="start_date"
                               name="start_date"
                               value="{{ $startDate ?? '' }}"
                               max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label for="end_date" class="form-label small fw-semibold text-muted">Tanggal Akhir</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-secondary-subtle">
                            <i class="bi bi-calendar-check"></i>
                        </span>
                        <input type="date"
                               class="form-control form-control-sm @error('end_date') is-invalid @enderror"
                               id="end_date"
                               name="end_date"
                               value="{{ $endDate ?? '' }}"
                               max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="col-12 col-md-6 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-filter"></i>
                        <span>Tampilkan Laporan</span>
                    </button>
                    <a href="{{ route('recap.index', ['mode' => $mode]) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>

                    {{-- Quick Presets --}}
                    <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Preset Periode">
                        <button type="button" class="btn btn-outline-secondary" onclick="setPresetDate(0)">Hari Ini</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setPresetDate(7)">7 Hari</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setPresetDate(30)">30 Hari</button>
                    </div>
                </div>
            </form>

            {{-- Info Bar Periode Aktif --}}
            <div class="border-top border-secondary-subtle mt-3 pt-3 d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
                <div>
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    <strong>Periode Terpilih:</strong>
                    @if($recap['has_data'])
                        <span class="text-body fw-medium">{{ $recap['period']['start_date'] }}</span> s/d
                        <span class="text-body fw-medium">{{ $recap['period']['end_date'] }}</span>
                        ({{ $recap['period']['total_records'] }} pembacaan sensor)
                    @else
                        <span class="text-secondary">Tidak ada filter aktif (menampilkan keseluruhan data)</span>
                    @endif
                </div>
                @if($recap['has_data'])
                <div class="text-md-end">
                    <span>Rentang Data: <strong>{{ $recap['period']['first_record'] }}</strong> &mdash; <strong>{{ $recap['period']['last_record'] }}</strong></span>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(!$recap['has_data'])
    {{-- Empty State: Tidak ada data --}}
    <div class="card card-custom border-0 shadow-sm text-center py-5 my-4">
        <div class="card-body">
            <div class="display-4 text-warning mb-3">
                <i class="bi bi-clipboard-x"></i>
            </div>
            <h4 class="fw-bold mb-2">Tidak Ada Data Pada Periode Ini</h4>
            <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                Tidak ditemukan rekaman data sensor pada rentang tanggal yang dipilih untuk mode <strong>{{ $mode }}</strong>.
                Silakan ubah rentang tanggal atau klik tombol reset untuk melihat data yang tersedia.
            </p>
            <a href="{{ route('recap.index', ['mode' => $mode]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i> Muat Semua Periode
            </a>
        </div>
    </div>
    @else

    {{-- BAGIAN 1: METRIK STATISTIK SENSOR --}}
    <div class="row g-3 mb-4">
        {{-- Kelembapan Tanah --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-custom h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Kelembapan Tanah</span>
                        <div class="badge bg-success-subtle text-success p-2 rounded-circle">
                            <i class="bi bi-moisture fs-6"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-1 mb-2">
                        <h2 class="h3 fw-bold mb-0 text-success">{{ $recap['sensor_stats']['soil_moisture']['avg'] ?? '0' }}</h2>
                        <span class="text-muted small">% Rata-rata</span>
                    </div>
                    <div class="border-top border-secondary-subtle pt-2 small text-muted d-flex justify-content-between">
                        <span>Min: <strong>{{ $recap['sensor_stats']['soil_moisture']['min'] }}%</strong></span>
                        <span>Max: <strong>{{ $recap['sensor_stats']['soil_moisture']['max'] }}%</strong></span>
                        <span>Data: <strong>{{ $recap['sensor_stats']['soil_moisture']['valid_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Suhu Udara --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-custom h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Suhu Udara</span>
                        <div class="badge bg-danger-subtle text-danger p-2 rounded-circle">
                            <i class="bi bi-thermometer-half fs-6"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-1 mb-2">
                        <h2 class="h3 fw-bold mb-0 text-danger">{{ $recap['sensor_stats']['temperature']['avg'] ?? '0' }}</h2>
                        <span class="text-muted small">°C Rata-rata</span>
                    </div>
                    <div class="border-top border-secondary-subtle pt-2 small text-muted d-flex justify-content-between">
                        <span>Min: <strong>{{ $recap['sensor_stats']['temperature']['min'] }}°C</strong></span>
                        <span>Max: <strong>{{ $recap['sensor_stats']['temperature']['max'] }}°C</strong></span>
                        <span>Data: <strong>{{ $recap['sensor_stats']['temperature']['valid_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kelembapan Udara --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-custom h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Kelembapan Udara</span>
                        <div class="badge bg-info-subtle text-info p-2 rounded-circle">
                            <i class="bi bi-droplet-half fs-6"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-1 mb-2">
                        <h2 class="h3 fw-bold mb-0 text-info">{{ $recap['sensor_stats']['humidity']['avg'] ?? '0' }}</h2>
                        <span class="text-muted small">% Rata-rata</span>
                    </div>
                    <div class="border-top border-secondary-subtle pt-2 small text-muted d-flex justify-content-between">
                        <span>Min: <strong>{{ $recap['sensor_stats']['humidity']['min'] }}%</strong></span>
                        <span>Max: <strong>{{ $recap['sensor_stats']['humidity']['max'] }}%</strong></span>
                        <span>Data: <strong>{{ $recap['sensor_stats']['humidity']['valid_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kondisi Cahaya (LM393) --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-custom h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Kondisi Cahaya (LM393)</span>
                        <div class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                            <i class="bi bi-sun-fill fs-6"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-1 mb-2">
                        <h2 class="h3 fw-bold mb-0 text-warning">{{ $recap['sensor_stats']['light']['terang_pct'] }}%</h2>
                        <span class="text-muted small">Kondisi Terang</span>
                    </div>
                    <div class="border-top border-secondary-subtle pt-2 small text-muted d-flex justify-content-between">
                        <span>Terang: <strong>{{ $recap['sensor_stats']['light']['terang_count'] }}</strong></span>
                        <span>Gelap: <strong>{{ $recap['sensor_stats']['light']['gelap_count'] }}</strong></span>
                        <span class="badge bg-secondary-subtle text-secondary py-0">Diskrit</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: DETAIL STATUS TANAH & PERINGATAN SUHU --}}
    <div class="row g-3 mb-4">
        {{-- Card Rekap Kelembapan Tanah --}}
        <div class="col-12 col-lg-7">
            <div class="card card-custom h-100 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold h6 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart-fill text-success"></i>
                        Rekap Status Kelembapan Tanah
                    </h5>
                    @if($recap['soil_recap']['last_reading_time'])
                    <span class="badge bg-secondary-subtle text-body small">
                        Terakhir: {{ $recap['soil_recap']['last_reading_time'] }} ({{ $recap['soil_recap']['last_soil_moisture'] }}%)
                    </span>
                    @endif
                </div>
                <div class="card-body p-3 p-md-4">
                    {{-- Progress Bar Proporsi Status --}}
                    <div class="progress mb-3" style="height: 14px;">
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
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <thead>
                                <tr class="text-muted border-bottom border-secondary-subtle small">
                                    <th>Kategori Status</th>
                                    <th>Ambang Batas</th>
                                    <th class="text-end">Jumlah Pembacaan</th>
                                    <th class="text-end">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="badge bg-success-subtle text-success me-1">
                                            <i class="bi bi-check-circle me-1"></i>Normal
                                        </span>
                                    </td>
                                    <td class="text-muted small">&ge; 30.0%</td>
                                    <td class="text-end fw-semibold">{{ $recap['soil_recap']['normal_count'] }} kali</td>
                                    <td class="text-end fw-bold text-success">{{ $recap['soil_recap']['normal_pct'] }}%</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning me-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Perlu Penyiraman
                                        </span>
                                    </td>
                                    <td class="text-muted small">20.0% s/d &lt; 30.0%</td>
                                    <td class="text-end fw-semibold">{{ $recap['soil_recap']['perlu_penyiraman_count'] }} kali</td>
                                    <td class="text-end fw-bold text-warning">{{ $recap['soil_recap']['perlu_penyiraman_pct'] }}%</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-danger-subtle text-danger me-1">
                                            <i class="bi bi-x-octagon me-1"></i>Kritis
                                        </span>
                                    </td>
                                    <td class="text-muted small">&lt; 20.0%</td>
                                    <td class="text-end fw-semibold">{{ $recap['soil_recap']['kritis_count'] }} kali</td>
                                    <td class="text-end fw-bold text-danger">{{ $recap['soil_recap']['kritis_pct'] }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 p-2 rounded bg-light-subtle small text-muted border border-secondary-subtle">
                        <i class="bi bi-shield-check me-1 text-primary"></i>
                        <strong>Aturan Prioritas Sistem:</strong> Ambang status dievaluasi secara eksklusif (Kritis &lt; 20%, Perlu Penyiraman 20% &ndash; 29.9%, Normal &ge; 30%).
                        <em>Catatan: Jumlah pembacaan menunjukkan frekuensi data berkala per interval transmisi, bukan durasi jam kontinu.</em>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Rekap Peringatan Suhu --}}
        <div class="col-12 col-lg-5">
            <div class="card card-custom h-100 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3 pb-0">
                    <h5 class="card-title fw-bold h6 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-thermometer-high text-danger"></i>
                        Rekap Peringatan Suhu Udara
                    </h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="p-3 mb-3 rounded border {{ $recap['temp_recap']['high_temp_count'] > 0 ? 'bg-danger-subtle border-danger-subtle' : 'bg-success-subtle border-success-subtle' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="small text-muted d-block">Pembacaan Suhu Tinggi (&gt; 35.0°C)</span>
                                <h3 class="fw-bold mb-0 {{ $recap['temp_recap']['high_temp_count'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $recap['temp_recap']['high_temp_count'] }}
                                    <span class="fs-6 fw-normal text-muted">kali ({{ $recap['temp_recap']['high_temp_pct'] }}%)</span>
                                </h3>
                            </div>
                            <div class="display-6 {{ $recap['temp_recap']['high_temp_count'] > 0 ? 'text-danger' : 'text-success' }}">
                                <i class="bi {{ $recap['temp_recap']['high_temp_count'] > 0 ? 'bi-exclamation-triangle-fill' : 'bi-shield-fill-check' }}"></i>
                            </div>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                            <span class="text-muted">Ambang Peringatan Sistem:</span>
                            <strong>&gt; 35.0 °C</strong>
                        </li>
                        <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                            <span class="text-muted">Suhu Maksimum Periode:</span>
                            <strong class="text-danger">{{ $recap['temp_recap']['max_temp'] ?? '-' }} °C</strong>
                        </li>
                        <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                            <span class="text-muted">Waktu Suhu Maksimum:</span>
                            <span>{{ $recap['temp_recap']['max_temp_time'] ?? '-' }}</span>
                        </li>
                        <li class="list-group-item bg-transparent px-0 d-flex justify-content-between">
                            <span class="text-muted">Total Pembacaan Suhu Valid:</span>
                            <span>{{ $recap['temp_recap']['total_valid'] }} record</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 3: GRAFIK TREN KONDISI LINGKUNGAN HARIAN --}}
    <div class="card card-custom border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title fw-bold h6 mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up text-primary"></i>
                    Tren Rata-Rata Harian Kondisi Lingkungan
                </h5>
                <span class="text-muted small">Agregasi harian parameter lingkungan selama periode laporan</span>
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
    <div class="card card-custom border-0 shadow-sm mb-4 border-start border-4 border-primary">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="card-title fw-bold h6 mb-1 text-primary d-flex align-items-center gap-2">
                <i class="bi bi-lightbulb-fill text-warning"></i>
                Ringkasan dan Interpretasi Keputusan Budidaya Jambu Kristal
            </h5>
            <span class="text-muted small">Penerjemahan fakta data agregat menjadi acuan keputusan agronomis berbasis aturan sistem</span>
        </div>
        <div class="card-body p-3 p-md-4">
            {{-- Ringkasan Eksekutif --}}
            <div class="p-3 rounded bg-light-subtle mb-3 border border-secondary-subtle">
                <h6 class="fw-bold mb-1 text-body">
                    <i class="bi bi-card-text me-1 text-primary"></i> Ringkasan Umum Periode
                </h6>
                <p class="mb-0 text-muted small">
                    {{ $recap['recommendations']['summary'] }}
                </p>
            </div>

            <div class="row g-3">
                {{-- Fakta Hasil Pengukuran --}}
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold small text-muted text-uppercase mb-2">
                        <i class="bi bi-clipboard2-data me-1 text-info"></i> Fakta Pengukuran Sensor
                    </h6>
                    <ul class="list-group list-group-flush small">
                        @foreach($recap['recommendations']['facts'] as $fact)
                        <li class="list-group-item bg-transparent px-0 py-2 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-primary mt-1"></i>
                            <span>{{ $fact }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Saran Tindakan Budidaya --}}
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold small text-muted text-uppercase mb-2">
                        <i class="bi bi-tree me-1 text-success"></i> Rekomendasi Tindakan Agronomi
                    </h6>
                    <ul class="list-group list-group-flush small">
                        @foreach($recap['recommendations']['actions'] as $action)
                        <li class="list-group-item bg-transparent px-0 py-2 d-flex align-items-start gap-2">
                            <i class="bi bi-arrow-right-circle-fill text-success mt-1"></i>
                            <span>{{ $action }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Batasan Teknis / Disclaimer --}}
            <div class="border-top border-secondary-subtle mt-3 pt-3 small text-muted">
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

function initRecapTrendChart() {
    const ctx = document.getElementById('recapTrendChart');
    if (!ctx) return;

    const activeConfig = chartConfigs[currentChartParam];

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
                        color: 'rgba(128, 128, 128, 0.15)',
                    }
                },
                x: {
                    grid: {
                        display: false,
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

document.addEventListener('DOMContentLoaded', function() {
    initRecapTrendChart();
});
@endif
</script>
@endsection
