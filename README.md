# RekapMapel SIJA 📋

Sistem pelaporan rekap tugas siswa berbasis web untuk jurusan **SIJA (Sistem Informatika Jaringan dan Aplikasi)**. Siswa mengupload foto dokumen rekap tugas yang sudah ditandatangani guru mapel, lalu sistem otomatis mengirim notifikasi ke orang tua/wali via WhatsApp — dan wali kelas/guru bisa memantau ketuntasan seluruh siswa dari satu dashboard.

## ✨ Fitur

**Untuk siswa**
- Upload foto dokumen rekap tugas (JPG/PNG, maks 5MB)
- Isi alasan kalau belum sempat upload tepat waktu (form dibuka otomatis tiap awal bulan untuk periode bulan sebelumnya)
- Lihat riwayat sendiri — upload foto dan alasan pembinaan ditampilkan terpisah biar jelas

**Untuk admin**
- Dashboard monitoring ketuntasan tugas per siswa & per kelas
- Lihat riwayat foto lengkap per siswa (bukan cuma upload terakhir)
- "Sentil" manual — kirim pengingat WhatsApp ke orang tua siswa yang belum upload
- Hapus data siswa beserta seluruh riwayatnya (untuk siswa lulus/pindah)

**Otomatisasi**
- Notifikasi WhatsApp otomatis (via Fonnte API) tiap ada upload baru
- Cron pengingat bulanan (`cron_pengingat.php`)
- Cron pembersihan data per semester (`cron_cleanup_semester.php`)
- Guard keamanan supaya script cron tidak bisa diakses langsung lewat browser (`cron_guard.php`)

## 🛠️ Tech Stack

- PHP native (PDO untuk akses database)
- MySQL / MariaDB
- Bootstrap 5 + Bootstrap Icons
- [Fonnte API](https://fonnte.com) — gateway notifikasi WhatsApp
- Docker & Docker Compose untuk deployment

## 📁 Struktur Proyek

```
.
├── Dockerfile
├── docker-compose.yml
├── koneksi.php              # koneksi database (PDO)
├── login.php / proses_login.php / logout.php
├── signup.php                # pendaftaran akun siswa
├── upload_rekap.php          # halaman siswa: upload & riwayat
├── dashboard_admin.php       # dashboard admin: monitoring & riwayat foto
├── get_riwayat_foto.php      # endpoint AJAX riwayat foto per siswa
├── fonnte.php                 # helper kirim notifikasi WhatsApp
├── cron_pengingat.php         # cron: pengingat bulanan
├── cron_cleanup_semester.php  # cron: bersihkan data per semester
├── cron_guard.php             # proteksi akses cron dari web
└── uploads/                   # folder penyimpanan foto rekap
```

## 🚀 Instalasi (Docker)

1. Clone repo ini:
   ```bash
   git clone https://github.com/willygerrard/rekapmapel-sija.git
   cd rekapmapel-sija
   ```
2. Siapkan file environment (buat `.env` di root project) berisi minimal:
   ```env
   DB_HOST=db
   DB_NAME=rekapmapel
   DB_USER=root
   DB_PASS=your_password
   FONNTE_TOKEN=your_fonnte_token
   ```
3. Build & jalankan container:
   ```bash
   docker compose up -d --build
   ```
4. Akses aplikasi di `http://localhost` (atau domain/port sesuai konfigurasi `docker-compose.yml`).

> Sesuaikan nama variabel `.env` di atas dengan yang dipakai di `koneksi.php` kalau berbeda.

## ⏰ Menjadwalkan Cron

Tambahkan ke crontab server (bukan di dalam container, arahkan ke endpoint yang dilindungi `cron_guard.php`):
```bash
0 7 1 * * php /path/to/cron_pengingat.php
0 0 1 7,1 * php /path/to/cron_cleanup_semester.php
```
Sesuaikan jadwal dengan kalender akademik sekolah.

## 🔒 Catatan Keamanan

- Jangan commit file `.env` atau token Fonnte ke repo — sudah masuk `.gitignore`.
- Folder `uploads/` sebaiknya di-mount sebagai volume yang sama antara container yang menulis file (PHP-FPM) dan yang menyajikannya (Nginx), supaya foto yang diupload siswa bisa langsung terlihat oleh admin.

## 👤 Dibuat oleh

Willy — Guru & Kepala Lab SIJA, SMKN 11 Malang.

## 📄 Lisensi

Proyek internal untuk kebutuhan SMKN 11 Malang. Silakan hubungi pemilik repo untuk penggunaan di luar itu.
