# ORGA-ATTEND — Sistem Informasi Presensi & Kehadiran Organisasi

Aplikasi presensi kehadiran organisasi berbasis web yang modern, aman, dan responsif. Dibangun menggunakan **Laravel 13**, **MySQL**, **Tailwind CSS**, **Alpine.js**, dan terintegrasi otomatis dengan **Google Sheets API** (salinan rekaman kehadiran) serta **Google Drive API** (penyimpanan privat foto selfie kehadiran).

---

## 🚀 Fitur Utama

### 1. Autentikasi & Otorisasi Berbasis Peran
- **Login Username & Password**: Tidak mewajibkan email sebagai identitas login utama.
- **Pencegahan Pendaftaran Publik**: Anggota hanya dapat didaftarkan dan dikelola oleh Administrator.
- **Role-Based Access Control**:
  - `ADMIN`: Akses penuh ke panel administrator, manajemen data master, verifikasi izin, laporan, dan sinkronisasi.
  - `MEMBER`: Akses ke dashboard anggota, kegiatan aktif, kamera selfie presensi, riwayat kehadiran, dan pengajuan izin/sakit.
- **Keamanan Akun**: Rate limiting login (5 kali percobaan per menit), regenerasi ID sesi otomatis, penonaktifan akun nonaktif, dan penggantian password mandiri.

### 2. Manajemen Data Master (Admin Panel)
- **Data Divisi**: Pengelolaan kode, nama, deskripsi, dan relasi anggota per divisi.
- **Data Anggota**: Pendaftaran anggota, penugasan divisi, aktivasi/nonaktivasi akun, reset password aman oleh admin, dan filter pencarian dinamis.
- **Data Kegiatan**: Pembuatan kegiatan dengan kode otomatis, lokasi, tanggal, waktu mulai/selesai, serta status (`DRAFT`, `PUBLISHED`, `COMPLETED`, `CANCELLED`).
- **Pendaftaran Peserta**: Pendaftaran peserta perorangan maupun massal (*batch enroll*) berdasarkan divisi atau seluruh anggota aktif.
- **Sesi Absensi Tunggal**: **Aturan tegas:** Setiap kegiatan hanya boleh memiliki satu sesi absensi aktif dengan konfigurasi jendela waktu buka/tutup dan toleransi keterlambatan.

### 3. Attendance Engine & Kamera Selfie
- **Validasi Berlapis**:
  1. Keanggotaan peserta dalam kegiatan.
  2. Status sesi absensi (`OPEN`) dan kesesuaian jendela waktu.
  3. Pencegahan absensi ganda menggunakan *database transaction* dan *unique constraint*.
- **Kamera HP Real-Time**:
  - Menggunakan API `navigator.mediaDevices.getUserMedia` dengan prioritas kamera depan (*selfie*).
  - Pratinjau langsung (*live video stream*), penanda framing wajah, tombol ambil foto, dan tombol ulangi foto (*retake*).
  - Kompresi gambar sisi klien (*client-side canvas compression*) ke format JPEG berkualitas optimal.
  - Opsi kamera cadangan (*fallback file capture*) untuk perangkat atau browser lama.
- **Penentuan Status Otomatis**:
  - `HADIR`: Hadir sebelum batas toleransi keterlambatan berakhir.
  - `TERLAMBAT`: Hadir setelah toleransi waktu terlampaui.

### 4. Pengajuan Izin & Sakit
- Anggota dapat mengajukan permohonan `IZIN` atau `SAKIT` disertai alasan dan unggah bukti/surat dokter.
- Administrator meninjau (*review*) dengan opsi **Setujui (Approve)** atau **Tolak (Reject)**.
- Persetujuan izin/sakit otomatis memperbarui catatan kehadiran anggota menjadi status `IZIN` atau `SAKIT`.

### 5. Google Drive API (Penyimpanan Foto Selfie Privat)
- Foto presensi diunggah ke Google Drive dengan struktur folder hierarki:
  ```text
  ORGA-ATTEND/
    Attendance/
      {EVENT-CODE}/
        ATT-{ATTENDANCE-ID}.jpg
  ```
- File bersifat **privat** (tidak dapat diakses publik secara langsung).
- Akses foto melalui endpoint backend (`/attendance/{attendance}/photo`) yang memverifikasi autentikasi (hanya Administrator atau anggota pemilik foto yang bersangkutan).
- Penanganan kegagalan: Foto tetap tersimpan aman di disk lokal privat dengan antrean percobaan ulang (*retry mechanism*).

### 6. Google Sheets API & Transactional Outbox
- **Transactional Outbox**: Rekaman sinkronisasi dicatat ke tabel `sync_outbox` dalam transaksi MySQL dan baru dijalankan setelah *commit* berhasil.
- **Idempotent Upsert**: Setiap data memiliki ID unik; sistem membaca ID di kolom A sebelum memperbarui baris atau menambahkan baris baru, mencegah data duplikat.
- **Keamanan Data**: Password dan token autentikasi **tidak pernah dikirim** ke Google Sheets.
- **8 Lembar Kerja (Sheets)**:
  1. `Users`
  2. `Divisions`
  3. `Events`
  4. `EventParticipants`
  5. `Attendance`
  6. `LeaveRequests`
  7. `AuditLog`
  8. `SyncStatus`
- **Halaman Monitoring**: Menampilkan metrik data tersinkronisasi, antrean menunggu, data gagal, detail error, tombol retry per baris, dan tombol *full resync*.

### 7. Laporan & Ekspor
- Filter multidimensi: rentang tanggal, kegiatan, divisi, anggota, dan status kehadiran.
- Ekspor ke **Microsoft Excel (CSV dengan UTF-8 BOM)**.
- Cetak / Ekspor **PDF** dengan tata letak dokumen formal berstandar cetak.

---

## 🛠️ Persyaratan Sistem

- PHP `>= 8.3` (Diuji dan berjalan optimal pada PHP `8.5`)
- Ekstensi PHP: `pdo_mysql`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `zip`
- Composer `>= 2.0`
- MySQL `>= 8.0`
- Node.js `>= 18.0` & NPM

---

## ⚙️ Panduan Instalasi Lokal

### 1. Clone Repository & Masuk ke Folder Proyek
```bash
cd d:\absensi
```

### 2. Pasang Dependensi Backend & Frontend
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (.env)
Salin file konfigurasi contoh:
```bash
copy .env.example .env
```
Sesuaikan parameter database MySQL Anda di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orga_attend
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

Buat kunci aplikasi (*application key*):
```bash
php artisan key:generate
```

### 4. Migrasi & Seeding Database
Jalankan migrasi tabel dan seed data awal:
```bash
php artisan migrate:fresh --seed
```

### 5. Kompilasi Aset Frontend
```bash
npm run build
```

### 6. Menjalankan Antrean Background Queue
Jalankan worker antrean untuk memproses unggah foto ke Google Drive dan sinkronisasi Google Sheets:
```bash
php artisan queue:work --tries=3
```

### 7. Menjalankan Server Pengembangan
```bash
php artisan serve
```
Akses aplikasi melalui browser: [http://localhost:8000](http://localhost:8000)

---

## 🔑 Akun Bawaan (Hasil Seeder)

| Peran | Username | Password | Deskripsi |
|---|---|---|---|
| **Administrator** | `admin` | `Admin@2026!` | Hak akses penuh admin panel |
| **Anggota 1** | `budi_santoso` | `Member@2026!` | Anggota Divisi IT (Sudah presensi) |
| **Anggota 2** | `siti_aminah` | `Member@2026!` | Anggota Divisi Humas (Belum presensi) |

> 💡 *Administrator baru juga dapat dibuat kapan saja secara interaktif melalui perintah CLI:*
> ```bash
> php artisan orga:create-admin
> ```

---

## ☁️ Panduan Konfigurasi Google Cloud API

1. Buka [Google Cloud Console](https://console.cloud.google.com/).
2. Buat proyek baru (misal: `orga-attend-prod`).
3. Masuk ke **APIs & Services > Library**, aktifkan:
   - **Google Drive API**
   - **Google Sheets API**
4. Masuk ke **APIs & Services > Credentials > Create Credentials > Service Account**:
   - Berikan nama Service Account.
   - Buka tab **Keys > Add Key > Create new key > JSON**.
   - Unduh file JSON tersebut, ganti nama atau simpan ke path:
     `storage/app/google-credentials.json`
5. Buka [Google Drive](https://drive.google.com/), buat sebuah Spreadsheet kosong:
   - Klik tombol **Share (Bagikan)** pada spreadsheet tersebut.
   - Tambahkan alamat email Service Account (contoh: `my-service@project.iam.gserviceaccount.com`) dengan akses **Editor**.
   - Salin ID Spreadsheet dari URL browser (karakter acak di antara `/d/` dan `/edit`).
6. Buka file `.env` aplikasi Anda dan sesuaikan:
   ```env
   GOOGLE_APPLICATION_CREDENTIALS=storage/app/google-credentials.json
   GOOGLE_SHEETS_SPREADSHEET_ID=ID_SPREADSHEET_ANDA
   GOOGLE_DRIVE_FOLDER_ID=ID_FOLDER_DRIVE_OPSIONAL
   GOOGLE_SYNC_ENABLED=true
   ```
7. Buka panel admin pada menu **Google Sheets Sync**, lalu klik tombol **"Inisialisasi Sheet & Header"**. Seluruh 8 lembar kerja beserta baris header akan dibuat secara otomatis!

---

## 🧪 Pengujian Sistem (Automated Tests)

Aplikasi dilengkapi pengujian unit dan fitur komprehensif mencakup autentikasi, aturan sesi tunggal, mesin presensi selfie, pencegahan absensi ganda, persetujuan izin, dan outbox:

```bash
php artisan test
```

### Hasil Pengujian:
```text
Pass: 29 tests
Assertions: 82 assertions
Failures: 0
Duration: ~1.2s
```

---

## 📁 Struktur Direktori Penting

```text
app/
├── Console/Commands/
│   └── OrgaSetupAdminCommand.php       # CLI pembuatan akun admin aman
├── Http/Controllers/
│   ├── Admin/                          # Controller Panel Administrator
│   │   ├── AttendanceController.php
│   │   ├── AttendanceSessionController.php
│   │   ├── AuditLogController.php
│   │   ├── DashboardController.php
│   │   ├── DivisionController.php
│   │   ├── EventController.php
│   │   ├── EventParticipantController.php
│   │   ├── GoogleSyncController.php
│   │   ├── LeaveRequestController.php
│   │   ├── MemberController.php
│   │   ├── ReportController.php
│   │   └── SettingController.php
│   ├── Auth/                           # Controller Autentikasi
│   ├── Member/                         # Controller Panel Anggota
│   └── AttendancePhotoController.php   # Endpoint streaming foto privat terproteksi
├── Jobs/                               # Queue Jobs sinkronisasi & Drive
├── Models/                             # Model Eloquent & Relasi Database
└── Services/
    ├── AttendanceService.php           # Logika transaksi presensi & kamera
    ├── GoogleClientManager.php         # Manajemen Client Google SDK
    ├── GoogleDriveService.php          # Integrasi upload & streaming Google Drive
    ├── GoogleSheetsService.php         # Idempotent upsert 8 sheets
    ├── LeaveRequestService.php         # Alur permohonan & persetujuan izin/sakit
    ├── ReportService.php               # Filter data & ekspor laporan
    └── SyncOutboxService.php           # Pola Transactional Outbox
```

---

## 📜 Lisensi

Aplikasi ORGA-ATTEND dikembangkan untuk kebutuhan organisasi dengan standar kualitas kode industri.
