# Sistem Pengelolaan Absensi Peserta Magang - PT PLN Suku Cadang

Sistem Pengelolaan Absensi Peserta Magang PT PLN Suku Cadang adalah aplikasi web berbasis Laravel yang dirancang untuk membantu proses pengelolaan kehadiran peserta magang secara terstruktur. Sistem ini mendukung alur operasional dari pendaftaran magang, pencatatan absensi masuk dan pulang, pengajuan ketidakhadiran, pengelolaan laporan, hingga monitoring data oleh admin.

Proyek ini menyediakan dua area utama:
- `User`: peserta magang untuk melakukan presensi, melihat riwayat, mengajukan izin/sakit/lupa absensi, dan mengelola dokumen.
- `Admin`: pengelola sistem untuk memantau data absensi, memverifikasi pengajuan, mengelola master data, dan memonitor peserta magang.

## Fitur Utama
- Landing page dan form pendaftaran magang online.
- Login terpisah untuk admin dan user.
- Presensi masuk dan pulang peserta magang.
- Kalender riwayat kehadiran bulanan.
- Pengajuan ketidakhadiran:
  - Izin
  - Sakit
  - Lupa absensi
- Pengelolaan laporan:
  - Laporan bulanan
  - Laporan akhir
  - Draft/template dokumen magang
- Dashboard admin untuk monitoring operasional.
- Pengelolaan data master:
  - Peserta magang
  - Divisi
  - Mentor
  - Hari libur nasional
- Rekap dan tampilan data absensi per peserta.

## Teknologi
- Laravel 8
- PHP
- MySQL / MariaDB
- Blade Template
- Bootstrap
- JavaScript
- Laravel Mix

## Tampilan Sistem

### Landing Page
Halaman awal sistem untuk memberikan informasi umum dan akses ke proses pendaftaran magang.

![Landing Page](images-readme/landing%20page.png)

### Login
Halaman autentikasi untuk masuk ke sistem sesuai role pengguna.

![Login](images-readme/login%20fitur.png)

---

## Role User

Role user ditujukan untuk peserta magang yang menggunakan sistem dalam aktivitas harian dan pengelolaan dokumen magang.

### 1. Homepage User
Halaman utama user yang menampilkan ringkasan akses cepat ke fitur-fitur penting.

![Homepage User](images-readme/homepage.png)

### 2. Absensi Masuk dan Pulang
User dapat melakukan presensi masuk dan pulang sesuai jadwal kerja yang berlaku.

![Absensi User](images-readme/absensi.png)

### 3. Kalender Riwayat Absensi User
Riwayat kehadiran user ditampilkan dalam format kalender bulanan agar lebih mudah dibaca.

![Kalender Riwayat User](images-readme/kalender%20riwayat%20absensi.png)

### 4. Pengajuan Ketidakhadiran
User dapat mengajukan ketidakhadiran untuk kebutuhan administratif.

#### Izin / Sakit
![Izin dan Sakit](images-readme/izin-sakit.png)

#### Lupa Absensi
![Lupa Absensi](images-readme/lupa-absen.png)

### 5. Draft Absensi
Fitur ini digunakan user untuk mengakses draft dokumen absensi yang tersedia.

![Upload Draft Absensi](images-readme/upload%20draft%20absensi.png)

### 6. Profil User
User dapat melihat dan memperbarui informasi profil yang berkaitan dengan data magang.

![Profil User](images-readme/profil%20user.png)

---

## Role Admin

Role admin digunakan untuk mengelola keseluruhan sistem, memonitor data peserta, dan memastikan proses administrasi magang berjalan dengan baik.

### 1. Homepage Admin
Dashboard admin menampilkan ringkasan monitoring sistem dan akses ke menu operasional.

![Homepage Admin](images-readme/homepage%20admin.png)

### 2. Daftar Peserta Magang
Admin dapat melihat daftar seluruh peserta magang yang terdaftar di sistem.

![Daftar Peserta Magang](images-readme/daftar%20peserta%20magang.png)

### 3. Detail Data Peserta
Admin dapat membuka detail lengkap setiap peserta magang.

![Detail Data Peserta](images-readme/detail%20data%20peserta.png)

### 4. Manajemen Data Pendaftar
Fitur untuk mengelola data pendaftar magang yang masuk ke sistem.

![Manajemen Data Pendaftar](images-readme/managemen%20data%20pendaftar.png)

### 5. Manajemen Mentor
Admin mengelola data mentor untuk peserta magang.

![Manajemen Mentor](images-readme/manajemen%20mentor.png)

### 6. Manajemen Divisi
Admin mengelola penamaan dan struktur divisi pada sistem.

![Penamaan Divisi](images-readme/penamaan%20divisi.png)

### 7. Manajemen Hari Libur
Admin dapat mengelola hari libur nasional yang akan memengaruhi kalender absensi.

![Manajemen Hari Libur](images-readme/manajemen%20hari%20libur.png)

### 8. Manajemen Ketidakhadiran
Admin dapat memantau dan mengelola pengajuan ketidakhadiran dari user.

![Manajemen Ketidakhadiran User](images-readme/manajemen%20ketidakhadiran%20user.png)

### 9. Manajemen Laporan Bulanan
Admin dapat memantau dan mengelola laporan bulanan peserta magang.

![Laporan Bulanan User](images-readme/manajemen%20laporan%20bulanan%20user.png)

### 10. Manajemen Laporan Akhir
Admin dapat memantau dan mengelola laporan akhir magang peserta.

![Laporan Akhir User](images-readme/managemen%20laporan%20akhir%20user.png)

### 11. Manajemen Dokumen
Admin dapat mengelola dokumen pendukung atau template yang dapat diakses user.

![Dokumen User](images-readme/manajemen%20dokumen%20untuk%20user.png)

---

## Role Pendaftaran Magang

Bagian ini mencakup alur pendaftaran magang yang terpisah dari fitur inti absensi, tetapi masih terhubung dalam ekosistem sistem.

### 1. Form Pendaftaran Magang
Sistem menyediakan alur pendaftaran magang untuk calon peserta.

![Daftar Magang 1](images-readme/daftar%20magang%201.png)
![Daftar Magang 2](images-readme/daftar%20magang%202.png)

### 2. Status Pendaftaran
Calon peserta dapat memantau status pengajuan pendaftaran magang.

![Status Daftar](images-readme/status%20daftar.png)

---

## Instalasi dan Setup Lokal

### 1. Clone repository
```bash
git clone <repository-url>
cd Sistem_Absensi_PLNSC-main
```

### 2. Install dependency backend dan frontend
```bash
composer install
npm install
```

### 3. Konfigurasi environment
```bash
copy .env.example .env
php artisan key:generate
```

Lalu sesuaikan konfigurasi database pada file `.env`.

### 4. Migrasi dan seeder
```bash
php artisan migrate --seed
```

### 5. Jalankan aplikasi
```bash
php artisan serve
```

Untuk menjalankan asset development:

```bash
npm run dev
```

Untuk build production:

```bash
npm run prod
```

## Akun Default Seeder

Seeder bawaan membuat akun admin berikut:

- Email: `admin@ams.com`
- Password: `admin@ams.com`

## Struktur Hak Akses

### Admin
- Mengelola data peserta magang
- Mengelola divisi dan mentor
- Mengelola hari libur nasional
- Melihat kalender absensi peserta
- Melihat dokumen laporan
- Memantau data pendaftar magang

### User
- Melakukan absensi masuk dan pulang
- Melihat riwayat absensi
- Mengajukan izin, sakit, dan lupa absensi
- Mengelola laporan bulanan dan laporan akhir
- Melihat dokumen/template dari admin
- Mengelola profil

## Catatan
- Endpoint registrasi user masih aktif.
- Fitur reset password belum diaktifkan secara default.
- Gambar dokumentasi README disimpan pada folder `images-readme`.

## Lisensi
MIT License
