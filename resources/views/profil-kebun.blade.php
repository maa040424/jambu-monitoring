@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-3 mb-2">
            <i class="bi bi-tree-fill fs-3" style="color: var(--accent-green);"></i>
            <div>
                <h4 class="mb-0 fw-bold" style="color: var(--text-primary);">Profil Kebun Jambu Kristal</h4>
                <small style="color: var(--text-secondary);">Informasi lokasi penelitian, tanaman, dan spesifikasi sensor IoT</small>
            </div>
        </div>
    </div>
</div>

{{-- Info Kebun + Map --}}
<div class="row g-3 mb-4">
    {{-- Info Kebun --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <div class="card-header d-flex align-items-center gap-2" style="border-bottom: 1px solid var(--border-color);">
                <i class="bi bi-geo-alt-fill" style="color: var(--accent-green);"></i>
                <span style="color: var(--text-primary);">Informasi Lokasi Penelitian</span>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0" style="color: var(--text-secondary);">
                    <tbody>
                        <tr>
                            <td class="fw-semibold" style="width: 160px; color: var(--text-primary);">
                                <i class="bi bi-building me-2" style="color: var(--accent-green);"></i>Nama Kebun
                            </td>
                            <td>Kebun Jambu Kristal</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold" style="color: var(--text-primary);">
                                <i class="bi bi-pin-map me-2" style="color: var(--accent-green);"></i>Lokasi
                            </td>
                            <td>Kalimantan Selatan, Indonesia</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold" style="color: var(--text-primary);">
                                <i class="bi bi-geo me-2" style="color: var(--accent-green);"></i>Koordinat
                            </td>
                            <td>
                                <code style="background: rgba(34,197,94,0.1); color: var(--accent-green); padding: 2px 8px; border-radius: 4px;">
                                    -2.1741447, 115.4073503
                                </code>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold" style="color: var(--text-primary);">
                                <i class="bi bi-flower1 me-2" style="color: var(--accent-green);"></i>Komoditas
                            </td>
                            <td>Jambu Kristal (<em>Psidium guajava</em> var. Crystal)</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold" style="color: var(--text-primary);">
                                <i class="bi bi-tree me-2" style="color: var(--accent-green);"></i>Jumlah Pohon
                            </td>
                            <td><strong>118</strong> pohon</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold" style="color: var(--text-primary);">
                                <i class="bi bi-link-45deg me-2" style="color: var(--accent-green);"></i>Google Maps
                            </td>
                            <td>
                                <a href="https://maps.app.goo.gl/TXwvWkUy4rXxhVdj7" target="_blank" class="text-decoration-none" style="color: var(--accent-green);">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka di Google Maps
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Map Embed --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <div class="card-header d-flex align-items-center gap-2" style="border-bottom: 1px solid var(--border-color);">
                <i class="bi bi-map-fill" style="color: var(--accent-green);"></i>
                <span style="color: var(--text-primary);">Peta Lokasi</span>
            </div>
            <div class="card-body p-0" style="min-height: 300px;">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3946!2d115.4073503!3d-2.1741447!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dfab30061acd2c5%3A0x181dcd3babd88c94!2sKebun%20Jambu%20Kristal!5e1!3m2!1sid!2sid!4v1747418183000"
                    width="100%"
                    height="100%"
                    style="border:0; min-height: 300px; border-radius: 0 0 8px 8px;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</div>

{{-- Kondisi Ideal Tanaman --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <div class="card-header d-flex align-items-center gap-2" style="border-bottom: 1px solid var(--border-color);">
                <i class="bi bi-clipboard2-pulse" style="color: var(--accent-green);"></i>
                <span style="color: var(--text-primary);">Kondisi Ideal Tanaman Jambu Kristal</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Suhu --}}
                    <div class="col-6 col-md-3">
                        <div class="text-center p-3 rounded" style="background: rgba(251,146,60,0.08); border: 1px solid rgba(251,146,60,0.2);">
                            <i class="bi bi-thermometer-half fs-3" style="color: #fb923c;"></i>
                            <div class="fw-bold mt-2" style="color: var(--text-primary);">Suhu Udara</div>
                            <div class="fs-5 fw-bold" style="color: #fb923c;">25°C – 30°C</div>
                            <small style="color: var(--text-secondary);">Optimal untuk pertumbuhan buah</small>
                        </div>
                    </div>
                    {{-- Kelembapan Udara --}}
                    <div class="col-6 col-md-3">
                        <div class="text-center p-3 rounded" style="background: rgba(96,165,250,0.08); border: 1px solid rgba(96,165,250,0.2);">
                            <i class="bi bi-moisture fs-3" style="color: #60a5fa;"></i>
                            <div class="fw-bold mt-2" style="color: var(--text-primary);">Kelembapan Udara</div>
                            <div class="fs-5 fw-bold" style="color: #60a5fa;">60% – 80%</div>
                            <small style="color: var(--text-secondary);">Mencegah jamur & penyakit</small>
                        </div>
                    </div>
                    {{-- Kelembapan Tanah --}}
                    <div class="col-6 col-md-3">
                        <div class="text-center p-3 rounded" style="background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2);">
                            <i class="bi bi-droplet-fill fs-3" style="color: #22c55e;"></i>
                            <div class="fw-bold mt-2" style="color: var(--text-primary);">Kelembapan Tanah</div>
                            <div class="fs-5 fw-bold" style="color: #22c55e;">50% – 80%</div>
                            <small style="color: var(--text-secondary);">Kebutuhan air tanaman</small>
                        </div>
                    </div>
                    {{-- Cahaya --}}
                    <div class="col-6 col-md-3">
                        <div class="text-center p-3 rounded" style="background: rgba(250,204,21,0.08); border: 1px solid rgba(250,204,21,0.2);">
                            <i class="bi bi-sun-fill fs-3" style="color: #facc15;"></i>
                            <div class="fw-bold mt-2" style="color: var(--text-primary);">Intensitas Cahaya</div>
                            <div class="fs-5 fw-bold" style="color: #facc15;">Full Sun</div>
                            <small style="color: var(--text-secondary);">8–12 jam sinar matahari/hari</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Spesifikasi Sensor --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <div class="card-header d-flex align-items-center gap-2" style="border-bottom: 1px solid var(--border-color);">
                <i class="bi bi-cpu" style="color: var(--accent-green);"></i>
                <span style="color: var(--text-primary);">Spesifikasi Sensor & Perangkat IoT</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="color: var(--text-secondary);">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="color: var(--text-primary);">Komponen</th>
                                <th style="color: var(--text-primary);">Tipe/Model</th>
                                <th style="color: var(--text-primary);">Parameter</th>
                                <th style="color: var(--text-primary);">Spesifikasi</th>
                                <th style="color: var(--text-primary);">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Mikrokontroler --}}
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td class="fw-semibold" style="color: var(--text-primary);">
                                    <i class="bi bi-motherboard me-2" style="color: #60a5fa;"></i>Mikrokontroler
                                </td>
                                <td><span class="badge" style="background: rgba(96,165,250,0.15); color: #60a5fa;">ESP32</span></td>
                                <td>Prosesor & WiFi</td>
                                <td>
                                    <small>
                                        • Dual-core Xtensa LX6 @240MHz<br>
                                        • WiFi 802.11 b/g/n<br>
                                        • Flash: 4MB<br>
                                        • GPIO: 36 pin
                                    </small>
                                </td>
                                <td><small>Mengirim data ke server via HTTP/WiFi setiap 30 menit</small></td>
                            </tr>
                            {{-- DHT22 --}}
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td class="fw-semibold" style="color: var(--text-primary);">
                                    <i class="bi bi-thermometer-half me-2" style="color: #fb923c;"></i>Sensor Suhu & Kelembapan
                                </td>
                                <td><span class="badge" style="background: rgba(251,146,60,0.15); color: #fb923c;">DHT22 (AM2302)</span></td>
                                <td>Suhu & Kelembapan Udara</td>
                                <td>
                                    <small>
                                        • Range Suhu: -40°C ~ 80°C<br>
                                        • Akurasi Suhu: ±0.5°C<br>
                                        • Range Kelembapan: 0–100% RH<br>
                                        • Akurasi Kelembapan: ±2–5% RH<br>
                                        • Resolusi: 0.1°C / 0.1% RH
                                    </small>
                                </td>
                                <td><small>Sensor digital, output single-bus, sampling rate 0.5Hz (1x per 2 detik)</small></td>
                            </tr>
                            {{-- Soil Moisture --}}
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td class="fw-semibold" style="color: var(--text-primary);">
                                    <i class="bi bi-droplet-fill me-2" style="color: #22c55e;"></i>Sensor Kelembapan Tanah
                                </td>
                                <td><span class="badge" style="background: rgba(34,197,94,0.15); color: #22c55e;">Capacitive Soil Moisture v1.2</span></td>
                                <td>Kelembapan Tanah</td>
                                <td>
                                    <small>
                                        • Tegangan Operasi: 3.3V – 5V<br>
                                        • Output: Analog (0–4095 pada ESP32)<br>
                                        • Tipe: Kapasitif (non-korosi)<br>
                                        • Interface: Analog signal
                                    </small>
                                </td>
                                <td><small>Lebih tahan lama dibanding resistive karena tidak ada kontak logam langsung dengan tanah</small></td>
                            </tr>
                            {{-- LDR --}}
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td class="fw-semibold" style="color: var(--text-primary);">
                                    <i class="bi bi-sun-fill me-2" style="color: #facc15;"></i>Sensor Cahaya
                                </td>
                                <td><span class="badge" style="background: rgba(250,204,21,0.15); color: #facc15;">LDR Module</span></td>
                                <td>Intensitas Cahaya</td>
                                <td>
                                    <small>
                                        • Tipe: Light Dependent Resistor<br>
                                        • Output: Analog + Digital (DO)<br>
                                        • Tegangan: 3.3V – 5V<br>
                                        • Sensitivitas: adjustable via potensiometer
                                    </small>
                                </td>
                                <td><small>Mengukur intensitas cahaya matahari yang diterima area kebun (dikonversi ke Lux)</small></td>
                            </tr>
                            {{-- OLED --}}
                            <tr>
                                <td class="fw-semibold" style="color: var(--text-primary);">
                                    <i class="bi bi-display me-2" style="color: #c084fc;"></i>Display
                                </td>
                                <td><span class="badge" style="background: rgba(192,132,252,0.15); color: #c084fc;">OLED SSD1306 0.96"</span></td>
                                <td>Tampilan Lokal</td>
                                <td>
                                    <small>
                                        • Resolusi: 128×64 pixel<br>
                                        • Interface: I2C<br>
                                        • Tegangan: 3.3V – 5V<br>
                                        • Warna: Monokrom (putih/biru)
                                    </small>
                                </td>
                                <td><small>Menampilkan pembacaan sensor secara lokal di perangkat</small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Arsitektur Sistem --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <div class="card-header d-flex align-items-center gap-2" style="border-bottom: 1px solid var(--border-color);">
                <i class="bi bi-diagram-3" style="color: var(--accent-green);"></i>
                <span style="color: var(--text-primary);">Arsitektur Sistem IoT</span>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    {{-- Step 1 --}}
                    <div class="col-6 col-md-2">
                        <div class="p-3 rounded h-100" style="background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.15);">
                            <i class="bi bi-broadcast-pin fs-2" style="color: var(--accent-green);"></i>
                            <div class="fw-bold mt-2 small" style="color: var(--text-primary);">Sensor Node</div>
                            <small style="color: var(--text-secondary);">DHT22 + Capacitive Soil + LDR</small>
                        </div>
                    </div>
                    {{-- Arrow --}}
                    <div class="col-auto d-none d-md-flex align-items-center">
                        <i class="bi bi-arrow-right fs-4" style="color: var(--text-secondary);"></i>
                    </div>
                    {{-- Step 2 --}}
                    <div class="col-6 col-md-2">
                        <div class="p-3 rounded h-100" style="background: rgba(96,165,250,0.06); border: 1px solid rgba(96,165,250,0.15);">
                            <i class="bi bi-cpu fs-2" style="color: #60a5fa;"></i>
                            <div class="fw-bold mt-2 small" style="color: var(--text-primary);">ESP32</div>
                            <small style="color: var(--text-secondary);">Baca sensor, kirim via WiFi</small>
                        </div>
                    </div>
                    {{-- Arrow --}}
                    <div class="col-auto d-none d-md-flex align-items-center">
                        <i class="bi bi-arrow-right fs-4" style="color: var(--text-secondary);"></i>
                    </div>
                    {{-- Step 3 --}}
                    <div class="col-6 col-md-2">
                        <div class="p-3 rounded h-100" style="background: rgba(192,132,252,0.06); border: 1px solid rgba(192,132,252,0.15);">
                            <i class="bi bi-cloud-upload fs-2" style="color: #c084fc;"></i>
                            <div class="fw-bold mt-2 small" style="color: var(--text-primary);">VPS Server</div>
                            <small style="color: var(--text-secondary);">Laravel API + MySQL</small>
                        </div>
                    </div>
                    {{-- Arrow --}}
                    <div class="col-auto d-none d-md-flex align-items-center">
                        <i class="bi bi-arrow-right fs-4" style="color: var(--text-secondary);"></i>
                    </div>
                    {{-- Step 4 --}}
                    <div class="col-6 col-md-2">
                        <div class="p-3 rounded h-100" style="background: rgba(251,146,60,0.06); border: 1px solid rgba(251,146,60,0.15);">
                            <i class="bi bi-graph-up-arrow fs-2" style="color: #fb923c;"></i>
                            <div class="fw-bold mt-2 small" style="color: var(--text-primary);">ARIMA (Flask)</div>
                            <small style="color: var(--text-secondary);">Prediksi kondisi lingkungan</small>
                        </div>
                    </div>
                    {{-- Arrow --}}
                    <div class="col-auto d-none d-md-flex align-items-center">
                        <i class="bi bi-arrow-right fs-4" style="color: var(--text-secondary);"></i>
                    </div>
                    {{-- Step 5 --}}
                    <div class="col-12 col-md-2">
                        <div class="p-3 rounded h-100" style="background: rgba(250,204,21,0.06); border: 1px solid rgba(250,204,21,0.15);">
                            <i class="bi bi-display fs-2" style="color: #facc15;"></i>
                            <div class="fw-bold mt-2 small" style="color: var(--text-primary);">Dashboard Web</div>
                            <small style="color: var(--text-secondary);">Monitoring real-time + notifikasi Telegram</small>
                        </div>
                    </div>
                </div>

                {{-- Flow Summary --}}
                <div class="mt-4 p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                    <div class="small" style="color: var(--text-secondary);">
                        <i class="bi bi-info-circle me-1" style="color: var(--accent-green);"></i>
                        <strong style="color: var(--text-primary);">Alur Data:</strong>
                        Sensor membaca kondisi lingkungan setiap <strong>30 menit</strong> →
                        ESP32 mengirim data via HTTP POST ke API Server (Laravel) →
                        Data disimpan di MySQL →
                        Flask/ARIMA melakukan prediksi →
                        Dashboard menampilkan data real-time & grafik prediksi →
                        Notifikasi otomatis via Telegram jika ada anomali.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Metode ARIMA --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <div class="card-header d-flex align-items-center gap-2" style="border-bottom: 1px solid var(--border-color);">
                <i class="bi bi-calculator" style="color: var(--accent-green);"></i>
                <span style="color: var(--text-primary);">Metode Prediksi — ARIMA(2,1,2)</span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold" style="color: var(--text-primary);">Apa itu ARIMA?</h6>
                        <p class="small" style="color: var(--text-secondary); line-height: 1.7;">
                            <strong>ARIMA</strong> (AutoRegressive Integrated Moving Average) adalah metode statistik untuk
                            meramalkan data <em>time series</em>. Model ini menggabungkan tiga komponen:
                        </p>
                        <ul class="small" style="color: var(--text-secondary); line-height: 1.8;">
                            <li><strong style="color: #60a5fa;">AR(2)</strong> — AutoRegressive: menggunakan <strong>2 nilai sebelumnya</strong> sebagai prediktor</li>
                            <li><strong style="color: #22c55e;">I(1)</strong> — Integrated: data di-<em>differencing</em> <strong>1 kali</strong> agar stasioner</li>
                            <li><strong style="color: #fb923c;">MA(2)</strong> — Moving Average: menggunakan <strong>2 error sebelumnya</strong> untuk koreksi</li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold" style="color: var(--text-primary);">Pengaturan Pengumpulan Data</h6>
                        <table class="table table-borderless table-sm mb-0" style="color: var(--text-secondary);">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold" style="color: var(--text-primary); width: 160px;">Interval Sampling</td>
                                    <td>Setiap <strong>30 menit</strong></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold" style="color: var(--text-primary);">Durasi Pengumpulan</td>
                                    <td><strong>2 minggu</strong> (14 hari)</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold" style="color: var(--text-primary);">Target Dataset</td>
                                    <td><strong>672</strong> data points</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold" style="color: var(--text-primary);">Data per Hari</td>
                                    <td><strong>48</strong> data points</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold" style="color: var(--text-primary);">Minimum Data</td>
                                    <td><strong>100</strong> data points (syarat panelis)</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold" style="color: var(--text-primary);">Split Ratio</td>
                                    <td>70% Training / 30% Testing</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
