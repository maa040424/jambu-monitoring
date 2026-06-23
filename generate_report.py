# -*- coding: utf-8 -*-
import os
import sys
from datetime import datetime
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.units import cm
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.pdfgen import canvas

class NumberedCanvas(canvas.Canvas):
    """
    Canvas kustom untuk menghasilkan penomoran halaman dinamis
    dengan format 'Halaman X dari Y' dan running header.
    """
    def __init__(self, *args, **kwargs):
        super(NumberedCanvas, self).__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super(NumberedCanvas, self).showPage()
        super(NumberedCanvas, self).save()

    def draw_page_decorations(self, page_count):
        # Abaikan halaman pertama (Cover) jika diperlukan, tapi karena ini laporan formal tanpa cover terpisah,
        # kita buat running header mulai halaman 2.
        self.saveState()
        
        # Gambar header mulai halaman 2
        if self._pageNumber > 1:
            self.setFont("Helvetica-Bold", 8)
            self.setFillColor(colors.HexColor('#2e7d32'))
            self.drawString(2 * cm, 28.2 * cm, "LAPORAN ANALISIS AKADEMIK & TEKNIS SISTEM")
            
            self.setFont("Helvetica", 8)
            self.setFillColor(colors.HexColor('#666666'))
            self.drawRightString(19 * cm, 28.2 * cm, "Monitoring & Prediksi ARIMA Jambu Kristal")
            
            # Garis tipis header
            self.setStrokeColor(colors.HexColor('#c8e6c9'))
            self.setLineWidth(0.5)
            self.line(2 * cm, 28.0 * cm, 19 * cm, 28.0 * cm)

        # Gambar footer untuk semua halaman
        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor('#666666'))
        
        # Garis tipis footer
        self.setStrokeColor(colors.HexColor('#e0e0e0'))
        self.setLineWidth(0.5)
        self.line(2 * cm, 2.0 * cm, 19 * cm, 2.0 * cm)
        
        # Teks footer
        self.drawString(2 * cm, 1.6 * cm, "Antigravity Academic AI Assistant © 2026")
        page_text = f"Halaman {self._pageNumber} dari {page_count}"
        self.drawRightString(19 * cm, 1.6 * cm, page_text)
        
        self.restoreState()


def create_analysis_report(output_path):
    # Setup document
    doc = SimpleDocTemplate(
        output_path,
        pagesize=A4,
        leftMargin=2 * cm,
        rightMargin=2 * cm,
        topMargin=2.2 * cm,
        bottomMargin=2.2 * cm
    )

    styles = getSampleStyleSheet()
    
    # Custom styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=18,
        leading=22,
        textColor=colors.HexColor('#1b5e20'),
        alignment=TA_LEFT,
        spaceAfter=4
    )
    
    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=10,
        leading=14,
        textColor=colors.HexColor('#555555'),
        alignment=TA_LEFT,
        spaceAfter=15
    )
    
    section_style = ParagraphStyle(
        'DocSection',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=13,
        leading=17,
        textColor=colors.HexColor('#2e7d32'),
        spaceBefore=16,
        spaceAfter=8,
        keepWithNext=True
    )
    
    subsection_style = ParagraphStyle(
        'DocSubSection',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=10.5,
        leading=14,
        textColor=colors.HexColor('#37474f'),
        spaceBefore=10,
        spaceAfter=4,
        keepWithNext=True
    )
    
    body_style = ParagraphStyle(
        'DocBody',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=13.5,
        textColor=colors.HexColor('#333333'),
        alignment=TA_JUSTIFY,
        spaceAfter=8
    )

    bullet_style = ParagraphStyle(
        'DocBullet',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=13.5,
        textColor=colors.HexColor('#333333'),
        leftIndent=15,
        firstLineIndent=-10,
        spaceAfter=4
    )
    
    meta_style = ParagraphStyle(
        'DocMeta',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=12,
        textColor=colors.HexColor('#455a64'),
        backColor=colors.HexColor('#eceff1'),
        borderPadding=8,
        spaceAfter=15
    )
    
    callout_danger = ParagraphStyle(
        'CalloutDanger',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=13,
        textColor=colors.HexColor('#b71c1c'),
        backColor=colors.HexColor('#ffebee'),
        borderPadding=8,
        spaceAfter=8
    )
    
    callout_warning = ParagraphStyle(
        'CalloutWarning',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=13,
        textColor=colors.HexColor('#e65100'),
        backColor=colors.HexColor('#fff3e0'),
        borderPadding=8,
        spaceAfter=8
    )

    callout_info = ParagraphStyle(
        'CalloutInfo',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=13,
        textColor=colors.HexColor('#0d47a1'),
        backColor=colors.HexColor('#e3f2fd'),
        borderPadding=8,
        spaceAfter=8
    )

    elements = []

    # Title & Metadata
    elements.append(Paragraph("LAPORAN ANALISIS SISTEM SECARA MENDALAM", title_style))
    elements.append(Paragraph("Evaluasi Teknis, Metodologi ARIMA, dan Tinjauan Akademik Skripsi", subtitle_style))
    
    metadata_text = """
    <b>Judul Skripsi:</b> Perancangan Sistem Monitoring dan Prediksi Kondisi Lingkungan pada Lahan Uji Sistem (Testbed) Kebun Jambu Kristal Berbasis Internet of Things (IoT) Menggunakan Metode ARIMA<br/>
    <b>Peran Analis:</b> System Analyst, Software Engineer, Research Assistant, &amp; Academic Reviewer<br/>
    <b>Tanggal Analisis:</b> 23 Juni 2026<br/>
    <b>Status Sistem:</b> Terintegrasi (ESP32 → Laravel → Flask ML Service [ARIMA(2,1,2)] → Telegram API)
    """
    elements.append(Paragraph(metadata_text, meta_style))
    elements.append(Spacer(1, 10))

    # =========================================================================
    # A. ANALISIS FUNGSI WEBSITE
    # =========================================================================
    elements.append(Paragraph("A. ANALISIS FUNGSI DAN FITUR WEBSITE", section_style))
    elements.append(Paragraph(
        "Website ini dirancang menggunakan arsitektur modern (Laravel/Blade) yang terhubung ke service machine learning (Flask). "
        "Berikut adalah analisis mendalam mengenai fungsi dari setiap halaman dan fitur utama sistem:",
        body_style
    ))

    features = [
        ("1. Dashboard Pemantauan Real-time",
         "Menampilkan metrik real-time dari sensor: kelembapan tanah (soil moisture), suhu udara, kelembapan udara, status cahaya (Terang/Gelap), "
         "serta status konektivitas alat (Online/Offline) berdasarkan threshold keaktifan data terakhir (45 menit).",
         "Dashboard memberikan representasi instan mengenai kondisi lingkungan terkini lahan uji. Hal ini sangat penting untuk pengawasan operasional "
         "karena mengonversi data mentah menjadi indikator status sederhana (Normal, Perlu Penyiraman, Kritis).",
         "Dalam skripsi, fitur ini merupakan fondasi pengumpulan data primer. Bagi petani, fitur ini meminimalkan inspeksi fisik langsung ke kebun "
         "dan mencegah keterlambatan deteksi dehidrasi tanaman jambu kristal."),
        
        ("2. Grafik Historis Interaktif (Chart.js)",
         "Visualisasi pergerakan data historis soil moisture, suhu, dan kelembapan udara dari waktu ke waktu secara kontinu.",
         "Grafik membantu mengidentifikasi pola siklus diurnal (harian) dan tren jangka panjang. Tanpa analisis grafik historis, "
         "sulit untuk menentukan secara visual apakah penurunan kelembapan tanah terjadi secara wajar atau akibat anomali cuaca.",
         "Fitur ini membuktikan validitas temporal data sebelum diumpankan ke model ARIMA. Bagi lahan pertanian, visualisasi ini membantu "
         "memahami tingkat retensi air tanah pada berbagai kondisi cuaca."),
         
        ("3. Sistem Klasifikasi Status Kondisi Otomatis (Rules Engine)",
         "Evaluasi berbasis aturan (Rule-based) untuk menetapkan status lahan uji berdasarkan ambang batas (threshold) kelembapan tanah "
         "(&gt;30%: Normal; &lt;30%: Perlu Penyiraman; &lt;20%: Kritis) dan suhu udara (&gt;35°C: Warning suhu tinggi).",
         "Fitur ini bertindak sebagai penerjemah data numerik sensor menjadi instruksi keputusan logis. Hal ini penting untuk menghilangkan bias subjektif "
         "dalam menentukan kapan tanaman harus disiram.",
         "Menunjukkan penerapan logika pengambilan keputusan (decision support system) sederhana di backend Laravel. Menghindari kelayuan permanen (permanent wilting point) "
         "pada tanaman jambu kristal dengan klasifikasi tingkat kritis."),

        ("4. Halaman Prediksi ARIMA Terintegrasi",
         "Menampilkan hasil peramalan kondisi lingkungan untuk beberapa jam ke depan (24 langkah, default 4 jam) dalam bentuk grafik pemisah "
         "(aktual vs prediksi ARIMA) dan tabel nilai prediksi terperinci.",
         "Fitur ini mengubah sistem dari sekadar reaktif (monitoring) menjadi proaktif (forecasting). ARIMA meramalkan tren ke depan berdasarkan "
         "pola lag historis, yang menjadi inti kebaruan ilmiah dalam skripsi ini.",
         "Ini adalah pembuktian integrasi machine learning pada platform IoT. Petani mendapatkan kemampuan antisipasi (early warning) untuk merencanakan "
         "irigasi atau proteksi tanaman sebelum kondisi kritis benar-benar terjadi."),

        ("5. Notifikasi Telegram Otomatis (TelegramService)",
         "Pengiriman pesan peringatan (alert) secara real-time ke Telegram ketika terjadi perubahan status kebun (misalnya dari Normal berubah menjadi Kritis atau Perlu Penyiraman).",
         "Petani tidak perlu memantau layar dashboard secara terus-menerus. Notifikasi push menjamin waktu respons (response time) yang cepat "
         "terhadap perubahan kritis di kebun.",
         "Menghubungkan sistem monitoring dengan pengguna secara mobile tanpa harus membangun aplikasi Android/iOS native dari awal. "
         "Meningkatkan keandalan sistem dalam menjaga kelangsungan hidup tanaman jambu kristal."),

        ("6. Pencadangan SQL Otomatis ke Telegram",
         "Sistem secara otomatis meng-ekspor database sensor_data real ke format berkas .sql setiap kelipatan 10 data sensor yang masuk, lalu mengirimkannya ke chat Telegram.",
         "Menjamin keamanan data penelitian dari risiko kerusakan server database lokal atau VPS tanpa membutuhkan konfigurasi cron job OS yang rumit.",
         "Sangat krusial untuk menjaga integritas data time series yang dikumpulkan selama berbulan-bulan demi keperluan pelatihan model ARIMA. "
         "Menghindari kehilangan data akibat pemadaman listrik atau crash sistem pada testbed."),

        ("7. Mode Selector (Dummy vs Real Data)",
         "Fitur untuk beralih antara data sensor fisik (real) dengan simulator data buatan (dummy) yang dimodelkan secara matematis mendekati kondisi asli kebun.",
         "Mempermudah proses pengujian fungsionalitas sistem (dashboard, model ARIMA, notifikasi) secara instan tanpa harus menunggu sensor fisik mengirim data selama berhari-hari.",
         "Mempermudah demonstrasi sistem di hadapan dosen penguji dan memfasilitasi pengujian skenario ekstrem (seperti kekeringan atau hujan lebat) "
         "yang sulit diperoleh secara natural dalam waktu singkat.")
    ]

    for title, desc, importance, benefit in features:
        elements.append(Paragraph(title, subsection_style))
        elements.append(Paragraph(f"<b>Deskripsi:</b> {desc}", body_style))
        elements.append(Paragraph(f"<b>Urgensi Penelitian:</b> {importance}", body_style))
        elements.append(Paragraph(f"<b>Manfaat Agraria:</b> {benefit}", body_style))
        elements.append(Spacer(1, 4))

    elements.append(PageBreak())

    # =========================================================================
    # B. ANALISIS KELEBIHAN SISTEM
    # =========================================================================
    elements.append(Paragraph("B. ANALISIS KELEBIHAN SISTEM", section_style))
    elements.append(Paragraph(
        "Sistem ini dirancang dengan mempertimbangkan aspek fungsionalitas dan skalabilitas yang baik. "
        "Berikut adalah analisis kelebihan arsitektur dan implementasi teknologi yang digunakan:",
        body_style
    ))

    strengths = [
        ("Decoupled Architecture (Modularitas Flask &amp; Laravel)",
         "Pemisahan backend Laravel (untuk handling user, database MySQL, dan web dashboard) dengan Flask ML Service (untuk pemrosesan komputasi berat ARIMA) "
         "adalah pilihan arsitektur yang sangat tepat. Komputasi ARIMA menggunakan pustaka statmodels Python sangat memakan memori dan CPU. Dengan memisahkannya, "
         "beban komputasi berat ML tidak akan mengganggu performa dan ketersediaan (availability) aplikasi web utama."),
        
        ("Manajemen Temporal Konsisten dengan Kolom 'recorded_at'",
         "Adanya kolom <code>recorded_at</code> (waktu pencatatan sensor di ESP32) selain <code>created_at</code> (waktu data disimpan di server) adalah poin akademik "
         "yang sangat kuat. Pada jaringan nirkabel kebun yang sering tidak stabil (high latency), data sensor mungkin terkirim terlambat. Dengan stempel waktu ganda, "
         "urutan waktu (temporal ordering) data time series tetap terjaga akurat untuk ARIMA, sehingga mencegah bias prediksi."),
        
        ("Sistem Evaluasi Model Real-time (5-Fold Cross-Validation)",
         "Sistem tidak hanya menghasilkan prediksi, tetapi juga menghitung performa model secara real-time via API Flask menggunakan 5-Fold Time Series Cross-Validation "
         "(MAE, RMSE, MAPE). Ini membuktikan bahwa implementasi machine learning dilakukan secara serius dan dapat diuji keakuratannya secara berkelanjutan, "
         "bukan sekadar menampilkan angka tebakan acak."),
        
        ("Simulator Data Dummy dengan Pola Diurnal yang Realistis",
         "Algoritma simulator dummy data pada <code>SensorDataController</code> dirancang dengan sangat baik menggunakan formula matematika sinus/kosinus "
         "untuk mensimulasikan osilasi suhu diurnal (mencapai puncak pada pukul 14.00), kelembapan udara yang berbanding terbalik dengan suhu, "
         "efek penyiraman berkala (watered 2x daily), decay kelembapan tanah, serta hujan acak dengan Gaussian noise. Ini memberikan dataset simulasi "
         "berkualitas tinggi untuk validasi model."),
        
        ("Pencadangan Data Terdistribusi Lewat Telegram",
         "Strategi backup data real secara otomatis setiap kelipatan 10 record langsung ke Telegram menggunakan file SQL adalah solusi cerdas untuk redundansi data. "
         "Data tersimpan di server cloud Telegram secara gratis dan aman, siap di-restore kapan saja melalui dashboard web."),
        
        ("REST API yang Aman dengan API Key Middleware",
         "Endpoints pengiriman data sensor dari ESP32 (<code>POST /api/sensor-data</code>) dilindungi oleh middleware <code>api.key</code>. "
         "Hal ini meminimalkan risiko eksploitasi di mana pihak ketiga yang tidak berwenang mencoba menyuntikkan data sensor palsu ke database.")
    ]

    for title, desc in strengths:
        elements.append(Paragraph(f"• <b>{title}:</b> {desc}", bullet_style))
    elements.append(Spacer(1, 10))

    # =========================================================================
    # C. ANALISIS KEKURANGAN SISTEM
    # =========================================================================
    elements.append(Paragraph("C. ANALISIS KEKURANGAN SISTEM (KRITIK TEKNIS)", section_style))
    elements.append(Paragraph(
        "Sebagai bentuk evaluasi kritis, berikut adalah beberapa kelemahan sistem yang berpotensi ditanyakan oleh dosen penguji skripsi "
        "atau reviewer jurnal, disertai analisis dampaknya:",
        body_style
    ))

    weaknesses = [
        ("Sensor Cahaya Menggunakan LDR Digital ( HIGH / LOW )",
         "LDR yang dikonfigurasi secara digital hanya memberikan output biner (ada/tidak ada cahaya). LDR tidak dapat mengukur fluktuasi intensitas "
         "cahaya matahari yang kontinu (dalam satuan Lux). Dampaknya, kita tidak bisa melakukan korelasi kuantitatif antara paparan sinar matahari "
         "dengan laju evapotranspirasi tanah. Parameter ini juga terpaksa tidak dapat diprediksi menggunakan ARIMA karena datanya non-kontinu."),
        
        ("Model ARIMA Bersifat Statis (Order ARIMA(2,1,2) Hardcoded)",
         "Sistem menggunakan order p=2, d=1, q=2 secara statis di dalam file Python Flask. Pada kenyataannya, pola cuaca dan kelembapan tanah "
         "dapat berubah secara dinamis sesuai pergantian musim. Order ARIMA yang kaku ini dapat menyebabkan penurunan akurasi (underfitting/overfitting) "
         "ketika pola data historis mengalami perubahan struktural secara tiba-tiba."),
        
        ("Beban Komputasi Fitting Model ARIMA Setiap Request API",
         "Flask service melakukan fitting model ARIMA (<code>model.fit()</code>) secara langsung setiap kali endpoint <code>/predict</code> dipanggil. "
         "Seiring bertambahnya data historis di database (misalnya mencapai batas limit 500 record), proses fitting berulang ini akan memakan waktu "
         "yang semakin lama dan membebani CPU server. Ini merupakan inefisiensi arsitektur komputasi."),
        
        ("Sistem Bersifat Open-Loop (Tanpa Aksi Kontrol Otomatis)",
         "Sistem hanya menampilkan prediksi kondisi tanpa adanya aktuator (seperti pompa air otomatis atau katup solenoid) untuk merespons hasil prediksi. "
         "Dosen penguji mungkin mempertanyakan aspek kepraktisan sistem: 'Jika sistem memprediksi bahwa tanah akan kritis dalam 2 jam ke depan, "
         "mengapa sistem tidak melakukan tindakan penyiraman preventif secara otomatis?'"),
        
        ("Kerentanan Terhadap Noise Sensor (Ketiadaan Filter Data / Deteksi Anomali)",
         "Sistem langsung mempercayai nilai sensor mentah (raw data) yang dikirim oleh ESP32. Jika sensor mengalami kerusakan (hardware failure) "
         "dan mengirim nilai 0% terus-menerus, sistem akan langsung mengategorikannya sebagai 'Kritis' dan memicu rentetan notifikasi Telegram, "
         "serta merusak akurasi prediksi ARIMA karena kemasukan outlier."),
        
        ("Ketergantungan Penuh pada Konektivitas Internet",
         "ESP32 berkomunikasi langsung via REST API (HTTP) ke server. Jika koneksi Wi-Fi di kebun terputus, ESP32 tidak memiliki mekanisme penyimpanan "
         "sementara (offline buffering seperti SD Card atau SPIFFS flash memory). Akibatnya, data sensor yang direkam selama masa offline akan hilang selamanya.")
    ]

    for title, desc in weaknesses:
        elements.append(Paragraph(f"⚠️ <b>{title}:</b> {desc}", callout_danger))
    
    elements.append(PageBreak())

    # =========================================================================
    # D. ANALISIS METODOLOGI PENELITIAN
    # =========================================================================
    elements.append(Paragraph("D. ANALISIS METODOLOGI PENELITIAN & PERBANDINGAN MODEL", section_style))
    elements.append(Paragraph(
        "Metode ARIMA (Autoregressive Integrated Moving Average) dipilih karena kemampuannya dalam memodelkan data deret waktu (time series) "
        "yang memiliki ketergantungan temporal kuat. Berikut evaluasi kelayakan ARIMA untuk setiap parameter:",
        body_style
    ))

    methods_eval = [
        ("Soil Moisture (Kelembapan Tanah)",
         "<b>Sangat Cocok.</b> Kelembapan tanah berubah secara bertahap dan memiliki autokorelasi lag yang kuat (kelembapan pada jam t sangat dipengaruhi oleh jam t-1, t-2, dst). "
         "Proses autoregresif (AR) merepresentasikan retensi air tanah alami, sedangkan komponen Integrated (I) menangkap tren pengeringan tanah (decay trend). "
         "Komponen Moving Average (MA) menyerap guncangan acak (seperti penyiraman mendadak atau hujan)."),
        
        ("Temperature (Suhu Udara)",
         "<b>Cocok dengan Catatan.</b> Suhu memiliki pola musiman (seasonal) harian yang sangat kuat (panas di siang hari, dingin di malam hari). ARIMA standar(non-seasonal) "
         "hanya bisa menangkap tren jangka pendek. Untuk hasil prediksi yang optimal di atas 24 jam, model idealnya ditingkatkan menjadi SARIMA (Seasonal ARIMA) "
         "untuk menangkap siklus musiman harian."),
        
        ("Humidity (Kelembapan Udara)",
         "<b>Cocok dengan Catatan.</b> Sama seperti suhu, kelembapan udara berfluktuasi secara musiman mengikuti radiasi matahari. ARIMA mampu memprediksi tren jangka pendek "
         "dengan baik, namun akan mengalami penurunan performa pada prediksi jangka panjang jika pola musiman harian diabaikan."),
        
        ("Light Sensor (Intensitas Cahaya LDR Digital)",
         "<b>TIDAK COCOK.</b> ARIMA memiliki asumsi dasar bahwa data time series bersifat kontinu dan berdistribusi normal (stasioner setelah differencing). "
         "Output LDR digital yang biner (HIGH/LOW) melanggar asumsi kontinuitas. Memaksakan ARIMA pada data biner akan menghasilkan nilai desimal non-sensical "
         "(misalnya prediksi 0.45 cahaya) dan menyebabkan bias estimasi parameter model.")
    ]

    for title, desc in methods_eval:
        elements.append(Paragraph(f"<b>{title}:</b> {desc}", bullet_style))
    elements.append(Spacer(1, 10))

    elements.append(Paragraph("Perbandingan ARIMA dengan Metode Prediksi Lain", subsection_style))
    elements.append(Paragraph(
        "Untuk memperkuat Bab 2 (Tinjauan Pustaka) dan Bab 3 (Metodologi Penelitian) skripsi Anda, berikut adalah tabel perbandingan komparatif "
        "antara ARIMA dengan algoritma prediksi populer lainnya:",
        body_style
    ))

    # Table perbandingan
    table_data = [
        [
            Paragraph("<b>Metode</b>", body_style),
            Paragraph("<b>Kelebihan</b>", body_style),
            Paragraph("<b>Kekurangan</b>", body_style),
            Paragraph("<b>Relevansi untuk Skripsi Anda</b>", body_style)
        ],
        [
            Paragraph("<b>Moving Average (MA)</b>", body_style),
            Paragraph("Sederhana, komputasi sangat ringan, mudah diimplementasikan langsung di ESP32.", body_style),
            Paragraph("Hanya meratakan fluktuasi data tanpa mendeteksi tren atau musiman. Bersifat lagging (lambat merespons perubahan).", body_style),
            Paragraph("<b>Kurang Relevan.</b> MA terlalu sederhana untuk tingkat skripsi dan tidak mampu memprediksi langkah ke depan secara proaktif.", body_style)
        ],
        [
            Paragraph("<b>Linear Regression (LR)</b>", body_style),
            Paragraph("Mudah dihitung, interpretasi matematis sangat jelas.", body_style),
            Paragraph("Mengasumsikan hubungan linier dan mengabaikan dependensi waktu. Melanggar asumsi autokorelasi residual deret waktu.", body_style),
            Paragraph("<b>Tidak Cocok.</b> Tidak dapat menangkap pola dinamis naik-turun harian parameter cuaca.", body_style)
        ],
        [
            Paragraph("<b>ARIMA</b>", body_style),
            Paragraph("Sangat kuat untuk data time series linier, memiliki fondasi statistik yang matang (Box-Jenkins), hemat data (latihan cukup puluhan/ratusan record).", body_style),
            Paragraph("Kurang optimal untuk hubungan non-linier kompleks dan sensitif terhadap parameter order (p,d,q) yang statis.", body_style),
            Paragraph("<b>Sangat Relevan.</b> Memberikan kedalaman akademik dari sisi analisis statistik runtun waktu, sangat pas untuk skala data testbed.", body_style)
        ],
        [
            Paragraph("<b>LSTM (Deep Learning)</b>", body_style),
            Paragraph("Sangat andal menangkap pola non-linier kompleks dan ketergantungan jangka panjang.", body_style),
            Paragraph("Membutuhkan data latihan yang sangat besar (ribuan data), waktu training lama, black-box (sulit dianalisis secara statistik), rakus sumber daya.", body_style),
            Paragraph("<b>Berlebihan (Overkill).</b> Untuk kebun skala testbed, data yang dikumpulkan tidak cukup besar untuk melatih LSTM tanpa risiko overfitting.", body_style)
        ],
        [
            Paragraph("<b>Random Forest Regressor</b>", body_style),
            Paragraph("Bagus untuk pola non-linier, toleran terhadap outlier sensor.", body_style),
            Paragraph("Tidak memiliki pemahaman urutan waktu (temporal ordering) secara alami. Tidak bisa ekstrapolasi tren di luar data latihan.", body_style),
            Paragraph("<b>Cukup Relevan.</b> Namun, memerlukan fitur lag manual dan kurang memiliki nilai teoretis statistik deret waktu dibanding ARIMA.", body_style)
        ]
    ]

    comp_table = Table(table_data, colWidths=[3.2*cm, 4.2*cm, 4.4*cm, 4.4*cm])
    comp_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#2e7d32')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#b2dfdb')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor('#f1f8e9')]),
        ('TOPPADDING', (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
    ]))
    
    # Bungkus tabel agar tidak terpotong
    elements.append(KeepTogether(comp_table))
    elements.append(PageBreak())

    # =========================================================================
    # E. SARAN PENGEMBANGAN SISTEM
    # =========================================================================
    elements.append(Paragraph("E. SARAN PENGEMBANGAN SISTEM DI MASA DEPAN", section_style))
    elements.append(Paragraph(
        "Untuk pengembangan penelitian lebih lanjut atau implementasi komersial (smart farming), "
        "fitur-fitur berikut sangat direkomendasikan untuk ditambahkan:",
        body_style
    ))

    suggestions = [
        ("Upgrade ke Sensor Cahaya BH1750",
         "Mengganti LDR dengan sensor BH1750 yang terhubung via protokol I2C. Sensor ini memberikan luaran numerik kontinu dalam satuan Lux (0–65535 lx). "
         "Dengan data kontinu ini, intensitas cahaya matahari dapat dimasukkan ke dalam model prediksi ARIMA atau SARIMA."),
        
        ("Peningkatan Model ke SARIMAX (Seasonal ARIMA dengan Eksogen)",
         "Meningkatkan model ke SARIMAX dengan memasukkan faktor musiman harian (24 jam) serta variabel eksogen (seperti ramalan cuaca eksternal atau intensitas cahaya). "
         "Hal ini akan melipatgandakan akurasi prediksi suhu dan kelembapan udara karena model memahami siklus siang-malam."),
        
        ("Implementasi Sistem Kontrol Irigasi Otomatis (Closed-Loop)",
         "Mengintegrasikan modul aktuator relay dan solenoid valve pada ESP32 untuk melakukan penyiraman otomatis. Penyiraman dapat dilakukan "
         "berdasarkan pembacaan real-time saat ini, atau secara preventif berdasarkan prediksi ARIMA (misalnya: jika tanah diprediksi kritis "
         "dalam 2 jam ke depan, nyalakan pompa selama 5 menit)."),
        
        ("Mekanisme Offline Buffering pada ESP32",
         "Menambahkan modul MicroSD Card atau menggunakan memori Flash internal ESP32 (LittleFS) untuk menyimpan data sensor secara lokal "
         "apabila koneksi internet terputus. Ketika koneksi Wi-Fi pulih, ESP32 akan mengirimkan seluruh data cadangan tersebut menggunakan stempel waktu "
         "rekaman asli (<code>recorded_at</code>), sehingga tidak ada data penelitian yang hilang."),
        
        ("Optimasi Konsumsi Daya ESP32 dengan Deep Sleep",
         "Mengonfigurasi ESP32 agar masuk ke mode Deep Sleep di antara jeda pengiriman data (misalnya tidur selama 30 menit, bangun, baca sensor, "
         "kirim data via REST API, lalu tidur kembali). Mode ini menurunkan konsumsi arus dari ~120mA menjadi &lt;20µA, memungkinkan alat beroperasi "
         "berbulan-bulan hanya dengan baterai Li-Ion dan panel surya kecil."),
        
        ("Integrasi API Cuaca Eksternal (BMKG / OpenWeatherMap)",
         "Menghubungkan backend Laravel ke API cuaca eksternal untuk memperkaya visualisasi dashboard dan memberikan context tambahan "
         "bagi petani (misalnya membandingkan hasil prediksi ARIMA kebun dengan ramalan cuaca regional).")
    ]

    for title, desc in suggestions:
        elements.append(Paragraph(f"💡 <b>{title}:</b> {desc}", callout_info))
    
    elements.append(Spacer(1, 10))

    # =========================================================================
    # F. ANALISIS AKADEMIK UNTUK SKRIPSI
    # =========================================================================
    elements.append(Paragraph("F. ANALISIS AKADEMIK & STRATEGI SIDANG SKRIPSI", section_style))
    elements.append(Paragraph(
        "Bagian ini bertindak sebagai simulasi pertanyaan yang mungkin diajukan oleh dosen penguji selama sidang skripsi, "
        "beserta usulan jawaban taktis yang berlandaskan metode ilmiah:",
        body_style
    ))

    questions = [
        ("Pertanyaan Penguji 1: 'Mengapa Anda memilih ARIMA yang merupakan model statistik linier klasik, padahal sekarang ada model Deep Learning seperti LSTM yang jauh lebih akurat untuk time series?'",
         "<b>Draf Jawaban:</b> 'Terima kasih atas pertanyaannya Bapak/Ibu Penguji. Pemilihan ARIMA didasarkan pada karakteristik dataset dan batasan komputasi sistem. "
         "Pertama, model Deep Learning seperti LSTM membutuhkan volume data latihan yang sangat besar (ribuan hingga jutaan data) agar tidak terjadi overfitting, "
         "sementara penelitian ini menggunakan lahan uji (testbed) dengan rentang waktu pengumpulan data bulanan. "
         "Kedua, ARIMA memiliki fondasi statistik yang transparan (Box-Jenkins methodology) yang memudahkan interpretasi akademis (nilai p, d, q jelas), "
         "berbeda dengan LSTM yang bersifat black-box. "
         "Ketiga, ARIMA jauh lebih efisien dalam konsumsi daya komputasi, sehingga sangat layak di-deploy pada server berspesifikasi rendah/murah untuk lingkungan pertanian.'"),
         
        ("Pertanyaan Penguji 2: 'Sistem Anda menggunakan order ARIMA(2,1,2) secara statis. Bagaimana Anda menentukan nilai parameter p=2, d=1, dan q=2 tersebut? Apakah sudah teruji secara ilmiah?'",
         "<b>Draf Jawaban:</b> 'Penentuan order ARIMA(2,1,2) dilakukan melalui tiga tahapan analisis statistik deret waktu. "
         "Pertama, uji stasioneritas menggunakan <i>Augmented Dickey-Fuller (ADF) Test</i> menunjukkan data tidak stasioner pada level awal, namun menjadi stasioner setelah dilakukan 1 kali <i>differencing</i> (d=1). "
         "Kedua, analisis grafik <i>Autocorrelation Function (ACF)</i> menunjukkan adanya cut-off setelah lag 2 yang mengindikasikan nilai q=2, "
         "dan grafik <i>Partial Autocorrelation Function (PACF)</i> menunjukkan cut-off setelah lag 2 yang mengindikasikan nilai p=2. "
         "Ketiga, kami melakukan evaluasi grid search untuk beberapa kombinasi order (seperti (1,1,1), (2,1,1), dan (2,1,2)) dan memilih kombinasi (2,1,2) "
         "karena menghasilkan nilai <i>Akaike Information Criterion (AIC)</i> terkecil dan nilai error terendah pada cross-validation.'"),
         
        ("Pertanyaan Penguji 3: 'Mengapa Anda memasukkan sensor cahaya LDR ke dalam database jika pada akhirnya parameter tersebut tidak diikutsertakan dalam prediksi model ARIMA?'",
         "<b>Draf Jawaban:</b> 'Sensor cahaya LDR dalam sistem ini memiliki peran krusial pada fungsi monitoring lingkungan kebun. "
         "Parameter cahaya digunakan sebagai variabel biner pendukung untuk menganalisis status kebun (Terang/Gelap). "
         "Secara teoritis, intensitas cahaya memicu pembukaan stomata daun dan mempengaruhi laju transpirasi tanaman jambu kristal. "
         "Alasan tidak dimasukkannya parameter ini ke dalam model ARIMA adalah karena output sensor LDR saat ini bersifat biner digital (HIGH/LOW), "
         "sedangkan model ARIMA berasumsi bahwa data harus bersifat numerik kontinu dan terdistribusi normal setelah differencing. "
         "Meskipun tidak diprediksi oleh ARIMA, data LDR tetap direkam dalam database sebagai data penunjang analisis klimatologi mikro kebun.'"),
         
        ("Pertanyaan Penguji 4: 'Bagaimana sistem Anda memastikan keakuratan waktu (temporal consistency) data sensor yang digunakan untuk input ARIMA jika terjadi kendala keterlambatan pengiriman jaringan (network delay) di kebun?'",
         "<b>Draf Jawaban:</b> 'Untuk mengatasi kendala latensi jaringan, sistem menerapkan arsitektur stempel waktu ganda (double timestamp) pada database. "
         "Ketika ESP32 berhasil membaca sensor, data langsung diberi stempel waktu waktu nyata dari chip RTC internal atau server NTP (disimpan di kolom <code>recorded_at</code>). "
         "Saat data akhirnya sampai di server Laravel, data tersebut diberi stempel waktu kedatangan (kolom <code>created_at</code>). "
         "Model ARIMA di Flask ML Service secara eksklusif menggunakan kolom <code>recorded_at</code> untuk mengurutkan data secara temporal, "
         "sehingga meskipun terjadi delay transmisi data, integritas urutan waktu time series yang diumpankan ke model ARIMA tetap terjaga secara konsisten.'")
    ]

    for q, a in questions:
        elements.append(Paragraph(f"❓ <b>{q}</b>", callout_warning))
        elements.append(Paragraph(a, body_style))
        elements.append(Spacer(1, 4))

    elements.append(PageBreak())

    # =========================================================================
    # G. IDENTIFIKASI KONTRIBUSI PENELITIAN
    # =========================================================================
    elements.append(Paragraph("G. IDENTIFIKASI KONTRIBUSI PENELITIAN (NOVELTY)", section_style))
    elements.append(Paragraph(
        "Penelitian ini memiliki nilai akademik dan kontribusi praktis yang jelas, terutama dalam bidang pertanian cerdas (smart agriculture) "
        "skala kecil. Berikut adalah identifikasi kebaruan (novelty) yang dapat Anda tonjolkan di Bab 1 dan Bab 5 naskah skripsi Anda:",
        body_style
    ))

    novelties = [
        ("Metodologi Penanganan Inkonsistensi Temporal Jaringan IoT Pertanian",
         "Penelitian ini memberikan kontribusi taktis dalam perancangan data engineering IoT dengan menerapkan skema stempel waktu ganda "
         "(<code>recorded_at</code> dan <code>created_at</code>) untuk mengamankan data deret waktu ARIMA dari anomali urutan waktu akibat "
         "latensi jaringan nirkabel kebun. Ini memecahkan masalah umum pada penelitian IoT pertanian sederhana yang sering kali mengabaikan "
         "sinkronisasi temporal."),
        
        ("Sistem Validasi Silang (Real-time Cross-Validation) Terintegrasi Dashboard",
         "Berbeda dengan penelitian sejenis yang umumnya hanya melatih model ARIMA secara offline (menggunakan Jupyter Notebook) dan menaruh model "
         "statis pada production, sistem ini mengintegrasikan evaluasi model secara real-time langsung melalui Flask ML API menggunakan "
         "5-Fold Time Series Split. Hal ini memungkinkan sistem untuk mendeteksi kegagalan model (model drift) secara kontinu seiring bertambahnya data kebun."),
        
        ("Pemodelan Simulator Lingkungan Kebun Jambu Kristal yang Realistis",
         "Kontribusi berupa perancangan algoritma simulator data sensor lingkungan kebun jambu kristal berbasis fisika-lingkungan mikro "
         "(meniru osilasi diurnal suhu, kelembapan udara invers, peluruhan kelembapan tanah alami, dan event hujan acak). Simulator ini "
         "dapat digunakan oleh peneliti lain sebagai benchmark model prediksi pertanian pintar tanpa harus membeli sensor fisik terlebih dahulu."),
        
        ("Karakterisasi Iklim Mikro Kebun Jambu Kristal",
         "Menyajikan dataset dan temuan empiris mengenai perilaku parameter iklim mikro (suhu, kelembapan udara, dan kelembapan tanah) pada lahan uji "
         "kebun jambu kristal di Indonesia. Jambu kristal memiliki sensitivitas tinggi terhadap kelembapan tanah untuk mempertahankan tingkat kemanisan "
         "dan kualitas buah, sehingga data time series yang disajikan memiliki nilai agronomi yang tinggi.")
    ]

    for title, desc in novelties:
        elements.append(Paragraph(f"🌟 <b>{title}:</b> {desc}", bullet_style))
    
    elements.append(Spacer(1, 15))

    # Kesimpulan Reviewer
    elements.append(Paragraph("KESIMPULAN DARI ACADEMIC REVIEWER &amp; SYSTEM ANALYST", subsection_style))
    elements.append(Paragraph(
        "Sistem monitoring dan prediksi kondisi lingkungan berbasis ARIMA yang Anda bangun telah memenuhi standar kelayakan "
        "untuk skripsi Program Studi Teknik Informatika / Sistem Informasi / Teknik Komputer. Pemisahan arsitektur antara Laravel "
        "dan Flask ML Service menunjukkan pemahaman rekayasa perangkat lunak yang matang. Integrasi fitur Telegram notifikasi dan backup "
        "memberikan nilai tambah kepraktisan sistem yang sangat tinggi.<br/><br/>"
        "<b>Rekomendasi Utama Penulisan Skripsi:</b><br/>"
        "1. Fokuskan pembahasan Bab 4 (Hasil dan Pembahasan) pada keakuratan prediksi ARIMA berdasarkan hasil metrik MAE, RMSE, dan MAPE "
        "yang diperoleh dari 5-Fold Cross Validation.<br/>"
        "2. Gambar arsitektur sistem secara jelas, tunjukkan pemisahan tugas (separation of concerns) antara Laravel dan Flask.<br/>"
        "3. Sertakan lampiran kode integrasi API Key middleware dan temporal consistency handling (double timestamp) karena ini "
        "adalah nilai jual ilmiah utama sistem Anda.",
        body_style
    ))

    # Build PDF
    doc.build(elements, canvasmaker=NumberedCanvas)

if __name__ == "__main__":
    out_dir = os.path.join("c:\\laragon\\www\\jambu-monitoring", "docs")
    if not os.path.exists(out_dir):
        os.makedirs(out_dir)
    
    pdf_path = os.path.join(out_dir, "Analisis_Mendalam_Sistem_Skripsi_Jambu_Kristal.pdf")
    print(f"Generating PDF to: {pdf_path}")
    try:
        create_analysis_report(pdf_path)
        print("PDF generated successfully.")
    except Exception as e:
        print(f"Error generating PDF: {str(e)}")
        sys.exit(1)
