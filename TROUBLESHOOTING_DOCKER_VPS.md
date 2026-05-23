# Panduan Troubleshooting Docker & VPS
**Proyek: Sistem Monitoring Kebun Jambu Kristal**

Dokumen ini mencantumkan kumpulan masalah umum, penyebab, dan solusi teknis yang terjadi selama proses deployment di VPS menggunakan Docker. Gunakan dokumen ini sebagai referensi cepat bagi AI model/developer pada sesi berikutnya untuk menghemat token dan waktu analisis.

---

## 1. HTTP 500 Error & Database Connection Refused (127.0.0.1 vs db)
### 🔍 Gejala
- Website memunculkan error `HTTP ERROR 500` saat diakses.
- Log laravel memunculkan error: `SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, Host: 127.0.0.1)`.

### 💡 Penyebab
- Pada environment Docker, aplikasi Laravel (`jambu_app`) dan database MySQL (`jambu_db`) berjalan di container terpisah dalam network yang sama.
- Konfigurasi `DB_HOST` di file `.env` masih mengarah ke `127.0.0.1` (localhost host/VPS) bukan ke service database Docker (`db`).
- Adanya spasi di depan tulisan variable di `.env` (contoh: ` DB_HOST=db`) yang membuat Laravel mengabaikannya dan me-fallback ke default (`127.0.0.1`).

### 🛠️ Solusi
1. Pastikan file `.env` di dalam container dan host tidak memiliki spasi di awal baris:
   ```bash
   # Di Host VPS
   sed -i 's/^ *DB_HOST=.*/DB_HOST=db/' /var/www/jambu-monitoring/.env
   
   # Di Container App
   docker exec jambu_app sed -i 's/^ *DB_HOST=.*/DB_HOST=db/' /var/www/html/.env
   ```
2. Pastikan file `.env` bersih dari spasi pada variabel DB lainnya:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=db_jambu_monitoring
   DB_USERNAME=jambu_user
   DB_PASSWORD=jambupass123
   ```

---

## 2. HTTP 500 Error: Database Credentials Mismatch (Access Denied)
### 🔍 Gejala
- Log Laravel memunculkan: `SQLSTATE[HY000] [1045] Access denied for user 'jambu_user'@'172.18.0.x' (using password: YES)`.
- Environment variable di `docker-compose.yml` sudah benar, tapi MySQL tetap menolak koneksi.

### 💡 Penyebab
- Container MySQL (`jambu_db`) menggunakan volume persistent (`db-data`).
- Saat pertama kali dideploy, MySQL menginisialisasi user/password database berdasarkan `.env` saat itu.
- Jika di kemudian hari password di `.env` diubah, container database **tidak akan memperbarui password** secara otomatis karena direktori data (`/var/lib/mysql`) sudah terbentuk di volume.

### 🛠️ Solusi (Jika Data Aman untuk Dihapus/Reset)
Hapus volume lama dan inisialisasi ulang database bersih dengan password baru dari `.env`:
```bash
# 1. Matikan container dan hapus volume database
cd /var/www/jambu-monitoring && docker compose down -v

# 2. Jalankan kembali container (MySQL akan inisialisasi volume kosong baru)
docker compose up -d

# 3. Jalankan migrasi ulang secara paksa (karena environment production)
docker exec jambu_app php artisan migrate --seed --force

# 4. Bersihkan cache bootstrap
docker exec jambu_app php artisan optimize:clear
```

---

## 3. Web Tidak Berubah setelah `git pull` (Stale Config/Cache)
### 🔍 Gejala
- Perubahan visual (seperti edit navbar atau halaman baru) sudah ter-pull di host VPS, namun tidak tampil di browser.

### 💡 Penyebab
- Laravel men-cache konfigurasi, route, dan view untuk performa cepat di production.
- Menjalankan `php artisan optimize:clear` langsung di VPS (host) akan menghasilkan error database karena host tidak bisa mengakses database container via `127.0.0.1`.
- Cache bootstrap (`services.php` dan `packages.php`) masih menyimpan path/konfigurasi lama.

### 🛠️ Solusi
Jalankan pembersihan cache **di dalam container app** dan hapus file cache bootstrap secara manual jika membandel:
```bash
# 1. Hapus file cache bootstrap secara manual
docker exec jambu_app rm -f /var/www/html/bootstrap/cache/config.php
docker exec jambu_app rm -f /var/www/html/bootstrap/cache/services.php
docker exec jambu_app rm -f /var/www/html/bootstrap/cache/packages.php

# 2. Jalankan perintah clean via artisan inside container
docker exec jambu_app php artisan optimize:clear
docker exec jambu_app php artisan view:clear
docker exec jambu_app php artisan route:clear

# 3. Restart container webserver
docker compose restart app nginx
```

---

## 4. Docker Compose Gagal Start: `ml/.env not found`
### 🔍 Gejala
- Saat menjalankan `docker compose up -d`, muncul error: `env file /var/www/jambu-monitoring/ml/.env not found: stat /var/www/jambu-monitoring/ml/.env: no such file or directory`.

### 💡 Penyebab
- File `docker-compose.yml` mendefinisikan dependency file `.env` di folder `./ml` untuk service Flask ML/ARIMA. Namun file tersebut tidak ikut ter-push ke git karena diabaikan oleh `.gitignore`.

### 🛠️ Solusi
Buat file `.env` kosong atau salin dari template di dalam folder `ml/` di VPS:
```bash
touch /var/www/jambu-monitoring/ml/.env
```

---

## 5. Perintah Berguna untuk Monitoring VPS (Quick Cheat-sheet)
Gunakan perintah ini untuk memeriksa kondisi server secara cepat:

*   **Melihat log Laravel realtime:**
    ```bash
    docker exec jambu_app tail -f /var/www/html/storage/logs/laravel.log
    ```
*   **Melihat log Nginx (Web Server):**
    ```bash
    docker compose logs nginx --tail=20 -f
    ```
*   **Mengecek status & resource container:**
    ```bash
    docker ps
    docker stats
    ```
*   **Mengecek konektivitas database dari Laravel:**
    ```bash
    docker exec jambu_app php artisan tinker --execute="echo config('database.connections.mysql.host');"
    ```
