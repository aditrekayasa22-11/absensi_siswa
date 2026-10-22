# PresensiKu — Sistem Absensi Karyawan

Aplikasi absensi karyawan berbasis **PHP native**, **MySQL/MariaDB**, HTML, CSS, dan JavaScript, dengan notifikasi **WhatsApp melalui Fonnte**.

Aplikasi mencakup seluruh tugas jobsheet: registrasi karyawan, integrasi Fonnte, notifikasi WhatsApp pada registrasi/ubah/hapus karyawan, CRUD karyawan, absensi masuk & pulang, dashboard admin, laporan, serta penerapan keamanan dasar.

---

## 1. Fitur

### Autentikasi & Akun
- Login dengan email + password, logout aman.
- Registrasi karyawan: NIK, nama, jabatan, nomor WhatsApp, email, password, konfirmasi password.
- Validasi seluruh field wajib, password minimal 8 karakter dan harus sama.
- NIK dan email unik (dicek di server dan database).
- Password disimpan menggunakan `password_hash()` (bcrypt).
- Pembatasan percobaan login (throttling) dan CSRF token.
- Peran pengguna: `admin` dan `karyawan`.

### Notifikasi WhatsApp (Fonnte)
- Integrasi melalui `src/Fonnte.php` + `src/WhatsAppService.php`.
- Token hanya disimpan di `.env`, tidak pernah ditampilkan di halaman.
- Notifikasi otomatis:
  - Registrasi berhasil → karyawan + admin.
  - Data karyawan diubah → karyawan.
  - Karyawan dihapus → karyawan (dikirim **sebelum** data dihapus).
  - Absensi masuk → karyawan.
  - Absensi pulang → karyawan.
  - Pengajuan izin/sakit → karyawan + admin.
- **Mode simulasi** saat `FONNTE_ENABLED=false` atau token kosong: pesan tetap dicatat di database dan WhatsApp Center tanpa dikirim.
- Halaman **WhatsApp Center** admin: status koneksi, tes kirim, log pesan, dan dokumentasi API.

### Karyawan (Admin)
- Daftar karyawan dengan pencarian dan pagination.
- Tambah, lihat detail, edit, dan hapus karyawan dengan konfirmasi.
- Statistik ringkas: total karyawan, jumlah jabatan, karyawan baru bulan ini, status WhatsApp.
- Alur hapus: ambil data → kirim WhatsApp → hapus dari database (sesuai jobsheet).

### Absensi Karyawan
- Halaman absensi menampilkan nama, NIK, tanggal, status, jam masuk, jam pulang.
- Absen masuk hanya sekali per hari.
- Absen pulang hanya dapat dilakukan setelah absen masuk dan hanya sekali.
- Tombol otomatis menyesuaikan status absensi hari ini.
- Pengajuan **Izin** dan **Sakit** dengan keterangan.
- Ringkasan bulanan (hadir, terlambat, izin, sakit) dan riwayat terbaru.

### Dashboard & Laporan (Admin)
- Dashboard: total karyawan, sudah absen, belum absen, sudah pulang, rekap status, tren 7 hari, aktivitas terbaru, daftar belum absen.
- Monitoring absensi harian dengan filter tanggal, status, dan pencarian.
- Laporan detail dan rekap karyawan per periode.
- Filter tanggal, karyawan, dan status.
- Export CSV dan cetak/PDF (print view).
- Halaman riwayat karyawan dengan filter bulan/status dan pagination.

### Keamanan
- Semua query menggunakan **prepared statement** (PDO, `ATTR_EMULATE_PREPARES = false`).
- Validasi input di server (`src/Validator.php`).
- Output di-escape dengan helper `e()` (`htmlspecialchars`).
- Session aman: cookie `HttpOnly`, `SameSite=Lax`, regenerasi ID saat login, timeout inactivity.
- CSRF token pada seluruh form POST.
- Password tidak pernah ditampilkan atau dicatat.
- Token Fonnte dan API key hanya di `.env`.
- File `.env`, `storage/`, dan folder database dilindungi `.htaccess`.
- Header keamanan dasar dan pembatasan akses berdasarkan peran.

### Tantangan Tambahan yang Diimplementasikan
- Status **Hadir / Terlambat / Izin / Sakit**.
- Batas jam masuk dapat diatur (`JAM_MASUK_BATAS`, default `07:30`).
- Rekap absensi bulanan per karyawan.
- Export CSV dan cetak PDF.
- Notifikasi WhatsApp ke admin.
- API eksternal `api/send_wa.php` dengan API key.
- UI responsif (desktop & mobile) dan mode simulasi.
- Halaman login & registrasi modern: aurora animasi, kata berputar, running text (marquee), jam live, kartu kaca, dan dukungan `prefers-reduced-motion`.

---

## 2. Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP 8.1+ (native, tanpa framework) |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| WhatsApp | Fonnte API (`https://api.fonnte.com/send`) |
| Server lokal | XAMPP (Apache + MySQL) |

---

## 3. Persyaratan

- PHP **8.1 atau lebih baru**
- Ekstensi PHP: `pdo_mysql`, `curl`, `mbstring`
- MySQL / MariaDB berjalan
- Akun Fonnte (opsional untuk mode simulasi)
- Browser modern

---

## 4. Instalasi

### 4.1 Salin proyek
Letakkan folder proyek di:

```text
C:\xampp\htdocs\absensi_siswa
```

### 4.2 Buat file `.env`
Salin `.env.example` menjadi `.env`, lalu sesuaikan:

```env
APP_NAME="PresensiKu"
APP_URL=
APP_TIMEZONE="Asia/Jakarta"
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=absensi_siswa
DB_USER=root
DB_PASS=

FONNTE_ENABLED=false
FONNTE_TOKEN=
FONNTE_COUNTRY_CODE=62
ADMIN_WA=6281234567890

API_KEY=ganti-dengan-kunci-acak-yang-panjang
JAM_MASUK_BATAS=07:30
```

- `APP_URL` boleh dikosongkan; aplikasi mendeteksi URL otomatis (cocok untuk subfolder XAMPP).
- `APP_DEBUG=false` untuk produksi.
- `FONNTE_ENABLED=false` menjalankan mode simulasi.

### 4.3 Jalankan MySQL
Pastikan MySQL/MariaDB aktif. Jika memakai XAMPP Control Panel, jalankan **MySQL**.

### 4.4 Jalankan installer
Buka:

```text
http://localhost/absensi_siswa/install.php
```

Installer akan:
1. Memeriksa kebutuhan sistem.
2. Membuat database `absensi_siswa` bila belum ada.
3. Membuat tabel `users`, `attendance`, dan `whatsapp_logs`.
4. Membuat akun administrator.
5. Menambahkan data demo (opsional, default aktif).
6. Membuat `storage/installed.lock`.

Setelah selesai, **hapus atau nonaktifkan `install.php`** untuk keamanan.

### 4.5 Akses aplikasi

```text
http://localhost/absensi_siswa/
```

---

## 5. Akun Demo

Data demo dibuat jika opsi **“Sertakan data contoh”** dicentang saat instalasi.

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@presensiku.test` | `Admin#12345` |
| Karyawan | `budi@presensiku.test` | `Karyawan#123` |
| Karyawan | `siti@presensiku.test` | `Karyawan#123` |
| Karyawan | `andi@presensiku.test` | `Karyawan#123` |
| Karyawan | `rina@presensiku.test` | `Karyawan#123` |
| Karyawan | `dewa@presensiku.test` | `Karyawan#123` |

> Ganti seluruh password demo sebelum digunakan di lingkungan nyata.

---

## 6. Konfigurasi Fonnte

1. Daftar/masuk ke [https://fonnte.com](https://fonnte.com).
2. Hubungkan perangkat WhatsApp pada dashboard Fonnte.
3. Salin **token** perangkat.
4. Isi `.env`:

```env
FONNTE_ENABLED=true
FONNTE_TOKEN=token-anda-dari-fonnte
FONNTE_COUNTRY_CODE=62
ADMIN_WA=628xxxxxxxxxx
```

5. Buka **WhatsApp → Tes Pengiriman Pesan** untuk memverifikasi koneksi.
6. Periksa log pada **WhatsApp Center**.

Catatan:
- Format nomor yang didukung: `08xxxxxxxxxx`, `628xxxxxxxxxx`, `+62xxxxxxxxxx`.
- Nomor dinormalisasi otomatis ke format `62`.
- Token **tidak boleh** dibagikan, ditulis di source code, atau di-commit ke repository.
- Saat mode simulasi, pesan tercatat dengan status `simulated`.

---

## 7. Struktur Folder

```text
absensi_siswa/
├── admin/                    # Halaman admin
│   ├── index.php             # Dashboard
│   ├── absensi.php           # Monitoring absensi
│   ├── karyawan*.php         # CRUD karyawan
│   ├── laporan*.php          # Laporan, export, cetak
│   └── wa.php                # WhatsApp Center
├── api/
│   └── send_wa.php           # Endpoint WhatsApp eksternal
├── assets/
│   ├── css/style.css         # Desain & responsive
│   ├── js/app.js             # Interaksi UI
│   └── img/logo.svg
├── config/app.php            # Konfigurasi aplikasi
├── database/
│   ├── schema.sql            # Skema tabel
│   └── demo_seed.php         # Data demo
├── karyawan/                 # Portal karyawan
│   ├── index.php             # Absensi hari ini
│   └── riwayat.php           # Riwayat absensi
├── src/                      # Kelas & helper
│   ├── Auth.php
│   ├── Csrf.php
│   ├── Database.php
│   ├── Env.php
│   ├── Fonnte.php
│   ├── Laporan.php
│   ├── MessageTemplates.php
│   ├── Validator.php
│   ├── WhatsAppService.php
│   ├── helpers.php
│   └── icons.php
├── storage/                  # Log & lock installer
├── templates/                # Layout bersama
├── bootstrap.php             # Bootstrap aplikasi
├── install.php               # Installer
├── login.php
├── register.php
├── logout.php
├── .env.example
├── .htaccess
└── README.md
```

---

## 8. Alur Penggunaan

### Admin
1. Login dengan akun admin.
2. **Dashboard** — pantau kehadiran hari ini.
3. **Karyawan** — tambah/ubah/hapus karyawan; perubahan mengirim WhatsApp.
4. **Absensi** — lihat dan filter catatan absensi.
5. **Laporan** — pilih periode, export CSV, atau cetak PDF.
6. **WhatsApp** — cek koneksi, tes kirim, dan lihat log.

### Karyawan
1. Login dengan akun karyawan.
2. **Absensi** — klik **Absen Masuk** (sekali per hari).
3. Setelah selesai bekerja, klik **Absen Pulang**.
4. Jika tidak hadir, ajukan **Izin** atau **Sakit**.
5. **Riwayat** — lihat catatan absensi per bulan.

---

## 9. API WhatsApp

Endpoint: `POST /api/send_wa.php`

Header:

```http
X-API-Key: <API_KEY dari .env>
Content-Type: application/json
```

Body:

```json
{
  "target": "6281234567890",
  "message": "Pesan uji dari integrasi eksternal."
}
```

Contoh `curl`:

```bash
curl -X POST http://localhost/absensi_siswa/api/send_wa.php \
  -H "X-API-Key: kunci-api-anda" \
  -H "Content-Type: application/json" \
  -d '{"target":"6281234567890","message":"Halo dari API"}'
```

Respons sukses:

```json
{
  "success": true,
  "simulated": false,
  "status": "sent",
  "target": "6281234567890",
  "message": "Pesan berhasil masuk antrean Fonnte."
}
```

Kode status:
- `200` berhasil
- `401` API key salah
- `405` metode bukan POST
- `422` input tidak valid
- `503` API key belum dikonfigurasi
- `502` gagal mengirim ke Fonnte

Saat mode simulasi, API mengembalikan `success: false` dengan status `simulated` agar pemanggil mengetahui pesan belum benar-benar terkirim.

---

## 10. Checklist Pengujian (Jobsheet)

| No | Skenario | Hasil yang Diharapkan |
|---|---|---|
| 1 | Registrasi dengan field kosong | Ditolak dengan pesan validasi |
| 2 | Password dan konfirmasi berbeda | Ditolak |
| 3 | NIK/email sudah terdaftar | Ditolak, data tidak tersimpan |
| 4 | Registrasi valid | Akun dibuat, password ter-hash, WhatsApp ke karyawan & admin |
| 5 | Login valid/invalid | Berhasil masuk / pesan error |
| 6 | Akses halaman admin tanpa login | Dialihkan ke login |
| 7 | Tambah karyawan | Data tersimpan |
| 8 | Edit karyawan | Data berubah + WhatsApp terkirim |
| 9 | Hapus karyawan | Konfirmasi → WhatsApp terkirim → data terhapus |
| 10 | Absen masuk pertama | Tersimpan, status Hadir/Terlambat, WhatsApp terkirim |
| 11 | Absen masuk kedua | Ditolak |
| 12 | Absen pulang sebelum masuk | Ditolak |
| 13 | Absen pulang setelah masuk | Tersimpan + WhatsApp terkirim |
| 14 | Absen pulang kedua | Ditolak |
| 15 | Dashboard admin | Menampilkan total karyawan, hadir, belum absen, sudah pulang |
| 16 | Laporan | Kolom No, Nama, NIK, Tanggal, Masuk, Pulang, Status |
| 17 | Export CSV / cetak PDF | File/print view berhasil |
| 18 | WhatsApp Center | Status koneksi, tes kirim, log tampil |
| 19 | API dengan key valid | Pesan diproses |
| 20 | API dengan key salah | `401 Unauthorized` |
| 21 | XSS pada input | Output ter-escape |
| 22 | SQL injection | Tidak berhasil (prepared statement) |

---

## 11. Keamanan & Catatan Produksi

- Hapus `install.php` setelah instalasi selesai.
- Set `APP_DEBUG=false` dan gunakan password database yang kuat.
- Ganti `API_KEY` dengan string acak panjang, misalnya:

  ```bash
  openssl rand -hex 32
  ```

- Jangan commit `.env` ke repository. File `.gitignore` sudah mengecualikannya.
- Gunakan HTTPS saat aplikasi diakses publik.
- Batasi akses ke folder `storage/` dan `database/`.
- Ganti password seluruh akun demo.
- Backup database secara berkala.

### Reset / Instalasi Ulang
1. Hapus `storage/installed.lock`.
2. Hapus database `absensi_siswa` (opsional, untuk data bersih).
3. Buka kembali `install.php` dan jalankan instalasi.

---

## 12. Troubleshooting

| Masalah | Solusi |
|---|---|
| “Koneksi database gagal” | Pastikan MySQL berjalan dan nilai `DB_*` di `.env` benar |
| Halaman installer terkunci | Hapus `storage/installed.lock` |
| WhatsApp tidak terkirim | Periksa `FONNTE_ENABLED`, token, kuota, dan koneksi perangkat Fonnte |
| Pesan berstatus `simulated` | Normal saat mode simulasi; isi token dan set `FONNTE_ENABLED=true` |
| Aset CSS/JS tidak muncul | Pastikan aplikasi diakses melalui web server, bukan `file://` |
| Tanggal/jam tidak sesuai | Sesuaikan `APP_TIMEZONE` (default `Asia/Jakarta`) |
| Absen ditolak “sudah absen” | Memang dibatasi satu kali per hari per karyawan |

---

## 13. Lisensi

Proyek ini dibuat untuk keperluan pembelajaran dan tugas jobsheet. Silakan digunakan dan dikembangkan sesuai kebutuhan.
