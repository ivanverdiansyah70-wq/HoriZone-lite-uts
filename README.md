# HoriZone Lite

Repositori karya ilmiah kampus (skripsi, laporan KP, jurnal, e-book, catatan) berbasis **PHP native + PDO** dengan operasi CRUD dan hak akses multi-role. Tampilan mengikuti HoriZone (terang/gelap), tanpa framework, Composer, atau CDN.

## Fitur

### Sistem Autentikasi
- Login dengan username dan password (`password_verify`), hanya akun berstatus aktif
- 3 role: Admin, Pustakawan, Mahasiswa, ditambah tamu (belum login)
- Logout lewat form POST, `session_regenerate_id` saat login

### Admin
- Dasbor panel
- Manajemen koleksi (tambah, ubah, hapus)
- Manajemen pengguna (tambah, ubah, hapus) - khusus admin
- Manajemen pengumuman (tambah, ubah, hapus)
- Akses ke semua fitur situs

### Pustakawan
- Dasbor panel
- Manajemen koleksi dan pengumuman (tanpa halaman pengguna)
- Unggah cover (JPG/PNG/WebP, maks. 2 MB) dan berkas PDF (maks. 10 MB); berkas lama dihapus saat diganti atau koleksi dihapus
- Opsi "Bisa diunduh" per koleksi

### Mahasiswa
- Tanpa akses panel (diarahkan ke halaman akses ditolak)
- Membaca PDF di browser dan mengunduhnya (jika koleksi bisa diunduh)

### Tamu (belum login)
- Beranda, jelajah, dan detail dokumen (metadata dan abstrak)
- Tombol "Masuk untuk membaca" - baca/unduh PDF wajib login

### Situs (semua pengunjung)
- Beranda
- Jelajah: cari judul/penulis dan filter jenis dokumen
- Detail dokumen: penulis, pembimbing, prodi, tahun, abstrak, kata kunci, statistik dibaca/unduhan
- Tema terang/gelap (disimpan di `localStorage`)

Rincian situs publik:
- **Beranda** (`index.php`): pengumuman aktif sesuai target pengunjung (`Pengumuman::tayang()`: target `semua` ditambah target sesuai role yang login, atau `tamu` bila belum login; maks. 3), jumlah koleksi per jenis, koleksi terbaru (6) dan paling banyak dibaca (5). Semua hanya dokumen berstatus terbit.
- **Jelajah** (`jelajah.php`): pencarian judul/penulis, karakter `%` dan `_` diperlakukan literal (`addcslashes` di `Dokumen`), ditambah filter jenis.
- **Detail** (`dokumen.php`): hanya dokumen terbit; status lain atau slug tidak ada menghasilkan 404.

## Data Contoh (Seed)
- `database/schema.sql` memuat 24 dokumen: 22 terbit, 1 menunggu, 1 diturunkan. Hanya yang terbit tampil di situs publik.
- 17 dokumen terbit dengan `bisa_unduh = 1` mendapat PDF contoh: `install.php` menyalin `database/seed/contoh.pdf` ke `uploads/pdf/seed-<slug>.pdf`. Cover contoh ada di `assets/img/seed/`.
- Dokumen dengan `bisa_unduh = 0` hanya bisa dibaca di browser; permintaan unduh ditolak 403 oleh `dokumen.php`.

## Catatan Tampilan
Tampilan mengikuti prototipe HoriZone (terang/gelap). Bagian prototipe yang tidak punya data nyata (forum, komentar, statistik dekoratif, ekspor) sengaja tidak dibuat.

## Instalasi

Panduan langkah demi langkah (skenario demo per role, cara kerja kode, dan solusi masalah umum) ada di [`PANDUAN.md`](PANDUAN.md).

### 1. Persyaratan Sistem
- PHP 8.x (ekstensi PDO MySQL dan fileinfo)
- MySQL atau MariaDB
- Apache dengan `AllowOverride All` agar `.htaccess` berlaku (mis. Laragon atau XAMPP)

### 2. Salin Proyek
Clone atau salin folder proyek ke `www` (Laragon) atau `htdocs` (XAMPP) dengan nama folder **`horizone-lite`**. Jika memakai nama folder lain, ubah `BASE_URL` di `config/config.php`:
```php
define('BASE_URL', '/horizone-lite/');
```

### 3. Konfigurasi Database
Sesuaikan kredensial di `config/database.php` **dan** bagian atas `install.php` (nilai harus sama):
```php
private $host = 'localhost';
private $db_name = 'horizone_lite';
private $username = 'root';
private $password = '';
```

### 4. Jalankan Instalasi
Buka sekali: `http://localhost/horizone-lite/install.php`

Skrip membuat database `horizone_lite`, tabel, dan data contoh dari `database/schema.sql`. Setelah terinstal, membuka `install.php` lagi menghasilkan **403**. Pasang ulang (semua data hilang) hanya lewat CLI:
```
php install.php --reset
```

### 5. Izin Folder
Folder `uploads/` (`uploads/cover/` dan `uploads/pdf/`) harus bisa ditulis oleh web server.

### 6. Akses Sistem
- URL: `http://localhost/horizone-lite/`
- Login: `http://localhost/horizone-lite/masuk.php`

## Akun Default

| Username   | Password       | Role       | Akses |
|------------|----------------|------------|-------|
| admin      | admin123       | Admin      | Panel penuh (koleksi, pengguna, pengumuman) |
| pustakawan | pustakawan123  | Pustakawan | Panel koleksi dan pengumuman |
| mahasiswa  | mahasiswa123   | Mahasiswa  | Situs, baca/unduh PDF |

Segera ganti password akun default (menu Pengguna oleh admin) sebelum dipakai di luar lingkungan pengembangan.

## Struktur Folder

```
horizone-lite/
├── assets/
│   ├── css/style.css          # CSS (token + komponen)
│   ├── js/app.js              # JS vanilla (tema, interaksi)
│   ├── fonts/                 # Font Poppins
│   └── img/seed/              # Gambar data contoh
├── config/
│   ├── config.php             # Session, BASE_URL, autoload, helper (requireRole, e, csrf)
│   └── database.php           # Koneksi PDO
├── database/
│   ├── schema.sql             # Tabel + data contoh
│   └── seed/contoh.pdf        # PDF contoh
├── models/
│   ├── User.php               # Model pengguna
│   ├── Dokumen.php            # Model koleksi
│   └── Pengumuman.php         # Model pengumuman
├── partials/                  # Potongan tampilan (head, header/footer situs, menu/sidebar panel)
├── panel/
│   ├── dasbor.php             # Dasbor
│   ├── koleksi.php            # CRUD koleksi + unggah cover/PDF
│   ├── pengguna.php           # CRUD pengguna (admin)
│   └── pengumuman.php         # CRUD pengumuman
├── uploads/
│   ├── cover/                 # Hasil unggah cover
│   └── pdf/                   # Hasil unggah PDF (akses langsung ditolak)
├── index.php                  # Beranda
├── jelajah.php                # Pencarian dan filter koleksi
├── dokumen.php                # Detail, baca, dan unduh dokumen
├── masuk.php                  # Login
├── logout.php                 # Logout (POST)
├── unauthorized.php           # Halaman akses ditolak
├── install.php                # Instalasi database
├── PANDUAN.md                 # Panduan menjalankan & mempelajari
└── .htaccess                  # Proteksi folder non-publik
```

## Keamanan
- SQL injection dicegah dengan PDO prepared statements
- Password disimpan dengan `password_hash()`
- Token CSRF pada setiap form POST
- Semua output di-escape dengan `e()` (`htmlspecialchars`)
- Validasi unggahan: tipe via `finfo` (bukan ekstensi), batas ukuran, nama file acak
- `uploads/` tidak mengeksekusi skrip; PDF hanya lewat `dokumen.php` (wajib login)
- `.htaccess` menolak akses HTTP ke `.git`, `config`, `models`, `database`, `partials`, dan berkas `.md`/`.sql`/`.json`
- Logout hanya lewat POST; role dan status pengguna dibaca ulang dari database tiap request
- `install.php` terkunci setelah terinstal; reset hanya lewat CLI

## Lisensi

Proyek ini dibuat untuk keperluan pembelajaran (tugas UTS).
