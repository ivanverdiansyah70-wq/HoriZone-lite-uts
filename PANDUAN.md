# Panduan Menjalankan & Mempelajari HoriZone Lite

Panduan ini untuk yang baru pertama kali menjalankan proyek. Ikuti berurutan: **Bagian A** sampai aplikasi jalan, **Bagian B** untuk mencoba semua fitur, **Bagian C** untuk memahami kodenya.

Ringkasnya: HoriZone Lite adalah repositori karya ilmiah kampus (skripsi, laporan KP, jurnal, e-book, catatan) dengan PHP native + PDO dan MySQL. Tidak butuh Composer, npm, atau internet saat dijalankan.

---

## A. Menjalankan aplikasi

### A1. Siapkan server lokal

Pilih salah satu.

**Laragon (disarankan, Windows)**
1. Unduh dan pasang Laragon (edisi *Full*, sudah berisi Apache, PHP 8, MySQL).
2. Buka Laragon → klik **Start All**. Tunggu sampai Apache dan MySQL berstatus hijau/running.
3. Folder web ada di `C:\laragon\www\` (atau di lokasi instalasi Laragon kamu, mis. `E:\A-Server\laragon\www\`).

**XAMPP**
1. Pasang XAMPP, buka *XAMPP Control Panel* → **Start** pada Apache dan MySQL.
2. Folder web ada di `C:\xampp\htdocs\`.

Kebutuhan minimum: PHP 8.x dengan ekstensi `pdo_mysql` dan `fileinfo` (keduanya aktif bawaan di Laragon/XAMPP), MySQL/MariaDB, Apache yang membaca `.htaccess` (`AllowOverride All`, bawaan Laragon/XAMPP).

### A2. Taruh kode di folder web dengan nama `horizone-lite`

Nama folder **harus** `horizone-lite`, karena alamat dasar aplikasi diatur di `config/config.php`:

```php
define('BASE_URL', '/horizone-lite/');
```

Cara mengambil kode (pilih satu):

```bash
# 1) git clone langsung ke folder web (Laragon)
cd C:\laragon\www
git clone -b main https://github.com/ivanverdiansyah70-wq/HoriZone-lite-rel.git horizone-lite
```

2) Unduh ZIP dari GitHub → ekstrak → ganti nama foldernya jadi `horizone-lite` → pindahkan ke `www` / `htdocs`.

3) Kode sudah ada di tempat lain dan tidak ingin dipindah? Buat *junction* (Command Prompt sebagai admin):

```bat
mklink /J C:\laragon\www\horizone-lite E:\lokasi\HoriZone-lite-rel
```

> Kalau ingin nama folder lain (mis. `repo-kampus`), ubah `BASE_URL` menjadi `'/repo-kampus/'`.

### A3. Cek kredensial database

Bawaan Laragon/XAMPP: user `root`, password kosong. Kalau MySQL kamu berbeda, ubah **dua** tempat dengan nilai yang sama:

- `config/database.php` → dipakai aplikasi
- bagian atas `install.php` → dipakai saat instalasi

```php
$host = 'localhost';  $db_name = 'horizone_lite';  $username = 'root';  $password = '';
```

### A4. Instal database (sekali saja)

Buka di browser:

```
http://localhost/horizone-lite/install.php
```

Yang terjadi:
1. Membuat database `horizone_lite` beserta tabel `users`, `dokumen`, `pengumuman` dari `database/schema.sql`.
2. Mengisi data contoh: 10 pengguna, 24 dokumen, 5 pengumuman.
3. Menyalin PDF contoh (`database/seed/contoh.pdf`) menjadi 17 berkas `uploads/pdf/seed-<slug>.pdf` untuk dokumen terbit yang boleh diunduh.

Halaman akan menampilkan pesan seperti *"Koneksi ke MySQL berhasil"*, *"Schema dan data awal berhasil diimpor"*, dan jumlah data.

Membuka `install.php` **lagi** setelah terinstal akan memberi **403 "Sudah terinstal"**. Ini disengaja, supaya orang lain tidak bisa menghapus database lewat browser.

### A5. Buka aplikasi

| Halaman | Alamat |
|---|---|
| Beranda | http://localhost/horizone-lite/ |
| Masuk (login) | http://localhost/horizone-lite/masuk.php |
| Panel (setelah login staf) | http://localhost/horizone-lite/panel/dasbor.php |

Akun bawaan:

| Username | Password | Role | Setelah login diarahkan ke |
|---|---|---|---|
| `admin` | `admin123` | Admin | Panel dasbor (semua menu) |
| `pustakawan` | `pustakawan123` | Pustakawan | Panel dasbor (tanpa menu Pengguna) |
| `mahasiswa` | `mahasiswa123` | Mahasiswa | Beranda situs |

### A6. Mengembalikan data ke kondisi awal (reset)

Reset menghapus **semua** data lalu memasang ulang data contoh. Hanya bisa lewat terminal:

```bash
cd C:\laragon\www\horizone-lite
php install.php --reset
```

Kalau perintah `php` tidak dikenal, buka **Laragon → Terminal** (PATH sudah diatur), atau panggil langsung, mis. `C:\laragon\bin\php\php-8.x\php.exe install.php --reset`. Di XAMPP: `C:\xampp\php\php.exe install.php --reset`.

Berkas unggahan lama di `uploads/cover/` dan `uploads/pdf/` (selain `seed-*.pdf`) tidak ikut terhapus. Hapus manual bila perlu, tapi jangan hapus `.htaccess` dan `.gitkeep`.

---

## B. Mencoba semua fitur (skenario demo)

Jalankan berurutan. Kolom "Yang harus terlihat" bisa dipakai sebagai daftar periksa saat presentasi.

### B1. Sebagai tamu (belum login)

| Langkah | Yang harus terlihat |
|---|---|
| Buka beranda | Pengumuman, jumlah koleksi per jenis, "Koleksi terbaru", "Paling banyak dibaca" |
| Klik ikon bulan/matahari | Tema gelap/terang berganti; tetap tersimpan saat halaman dibuka ulang |
| Buka **Jelajah**, ketik `Sistem` di kotak cari | Hanya dokumen yang judul/penulisnya mengandung "Sistem" (3 hasil) |
| Pilih filter jenis **Jurnal** | Hanya jurnal (5 hasil) |
| Klik sebuah dokumen | Detail: penulis, prodi, tahun, abstrak, tombol **"Masuk untuk membaca"** |
| Buka `dokumen.php?slug=tidak-ada` | Halaman 404 |
| Buka `panel/dasbor.php` | Diarahkan ke halaman masuk |

### B2. Sebagai mahasiswa

| Langkah | Yang harus terlihat |
|---|---|
| Login `mahasiswa` / `mahasiswa123` | Diarahkan ke beranda, ada tombol **Keluar** |
| Buka dokumen **d1** (`dokumen.php?slug=d1`) | Tombol **Baca dokumen** dan **Unduh PDF** |
| Klik Baca / Unduh | PDF terbuka di browser / terunduh |
| Buka dokumen **d4** | Tidak ada tombol Unduh (`bisa_unduh = 0`) |
| Buka `panel/dasbor.php` | Halaman **akses ditolak (403)** |
| Klik Keluar | Kembali ke halaman masuk |

### B3. Sebagai pustakawan

| Langkah | Yang harus terlihat |
|---|---|
| Login `pustakawan` / `pustakawan123` | Panel dasbor dengan angka dari database |
| **Koleksi → Unggah** | Isi judul, penulis, jenis, dst. Pilih cover (JPG/PNG/WebP ≤ 2 MB) dan PDF (≤ 10 MB) → Simpan |
| Kembali ke daftar Koleksi | Dokumen baru muncul paling atas |
| Coba unggah berkas `.txt` yang diganti nama jadi `.pdf` | Ditolak: tipe dicek dari isi berkas, bukan nama |
| Klik dokumen → ubah judul → Simpan | Judul berubah, pesan sukses tampil |
| Hapus dokumen tadi | Muncul konfirmasi; setelah OK dokumen dan berkasnya hilang |
| **Pengumuman → Tambah** | Isi judul, isi, target, tanggal tampil → muncul di daftar dan (jika status Tayang dan targetnya sesuai) di beranda |
| Buka `panel/pengguna.php` | Akses ditolak: menu Pengguna khusus admin |

### B4. Sebagai admin

| Langkah | Yang harus terlihat |
|---|---|
| Login `admin` / `admin123` | Dasbor dengan menu Koleksi, Pengguna, Pengumuman |
| **Pengguna → Tambah** | Buat akun baru (mis. role mahasiswa) → bisa login dengan akun itu |
| Ubah status akun itu menjadi **nonaktif** | Akun tidak bisa login; sesi yang sedang aktif ikut putus pada klik berikutnya |
| Coba hapus akun `admin` sendiri | Ditolak (admin tidak bisa menghapus/menurunkan dirinya) |

### B5. Uji keamanan sederhana (untuk ditunjukkan ke dosen)

| Uji | Hasil yang benar |
|---|---|
| Login dengan username `' OR 1=1 --` | Gagal login (pesan salah biasa), tidak ada error SQL |
| Cari `<script>alert(1)</script>` di Jelajah | Teks tampil apa adanya, **tidak** muncul popup |
| Buka `http://localhost/horizone-lite/config/config.php` atau `database/schema.sql` | 403 (folder non-publik dilindungi `.htaccess`) |
| Buka `http://localhost/horizone-lite/uploads/pdf/` | 403 (PDF hanya bisa diambil lewat `dokumen.php` setelah login) |

---

## C. Memahami cara kerja kode

### C1. Peta folder

```
horizone-lite/
├── index.php, jelajah.php, dokumen.php   ← halaman situs publik
├── masuk.php, logout.php, unauthorized.php
├── panel/        ← halaman CRUD (dasbor, koleksi, pengguna, pengumuman)
├── models/       ← SEMUA query SQL ada di sini (User, Dokumen, Pengumuman)
├── config/       ← config.php (sesi, login, CSRF, e()), database.php (koneksi PDO)
├── partials/     ← potongan HTML yang dipakai ulang (head, header, footer, sidebar)
├── assets/       ← style.css, app.js, font, gambar contoh
├── database/     ← schema.sql (tabel + data contoh), seed/contoh.pdf
├── uploads/      ← hasil unggahan (cover/, pdf/)
└── install.php   ← pemasang database
```

Aturan utamanya sama dengan sampel `pos_minimart`: **halaman** hanya mengatur alur dan tampilan, sedangkan **model** yang berbicara dengan database.

### C2. Alur satu permintaan (request)

Contoh: pustakawan membuka `panel/koleksi.php`.

```
Browser ──GET──► panel/koleksi.php
                  │ 1. require config/config.php   → session_start, autoload models/, helper
                  │ 2. requireRole(['admin','pustakawan'])
                  │        ├─ belum login        → redirect masuk.php
                  │        ├─ role tidak cocok   → redirect unauthorized.php
                  │        └─ lolos (role & status dibaca ulang dari DB)
                  │ 3. $db = (new Database())->getConnection()      (PDO)
                  │ 4. $dokumen = new Dokumen($db)
                  │ 5. $dokumen->readPanel(...)  → SELECT ... (prepared statement)
                  │ 6. include partials/... lalu cetak HTML, setiap nilai lewat e()
                  ▼
Browser ◄── HTML
```

### C3. Pola CRUD (dipakai di semua halaman panel)

Buka `panel/pengumuman.php`, file CRUD paling sederhana, sambil membaca bagian ini:

1. **Cek akses** di baris paling atas: `requireRole([...])`.
2. **POST** (simpan/hapus):
   - `csrf_check()`: form tanpa token yang benar ditolak 400.
   - Ambil dan validasi isian (`trim`, cek wajib isi, cek nilai pilihan, cek tanggal).
   - Isi properti model lalu panggil `create()` / `update()` / `delete()`.
   - Simpan pesan sukses/gagal di `$_SESSION['flash']`, lalu `header('Location: ...')`. Pola ini disebut **PRG (Post-Redirect-Get)**: setelah redirect, refresh tidak mengirim form dua kali.
3. **GET** (tampil): `readAll()` / `readOne()` dari model, lalu render HTML.
   - `?aksi=tambah` → form kosong
   - `?aksi=ubah&id=3` → form terisi data
   - tanpa parameter → daftar

`panel/koleksi.php` memakai pola yang sama, ditambah unggah berkas: tipe dicek dengan `finfo`, ukuran dibatasi, nama berkas diacak (`bin2hex(random_bytes(16))`), dan berkas lama dihapus saat diganti.

### C4. Pola model

Contoh dari `models/Dokumen.php`:

```php
public function terbaru($n = 6) {
    $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . "
        WHERE status = 'terbit' ORDER BY created_at DESC, id DESC LIMIT :n");
    $stmt->bindValue(':n', (int) $n, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt;
}
```

Nilai dari pengguna **tidak pernah** digabung ke string SQL. Nilai selalu masuk lewat placeholder (`:n`, `:k1`, …), sehingga input `' OR 1=1 --` hanya dianggap teks biasa.

### C5. Login, sesi, dan role

- `masuk.php` → `User::login()` mengecek akun aktif + `password_verify()`. Jika benar: `session_regenerate_id(true)` lalu simpan `user_id`, `user_role`, `user_nama` di sesi.
- `config/config.php` → `isLoggedIn()` membaca ulang pengguna dari DB setiap request. Akibatnya, akun yang dinonaktifkan atau diubah role-nya langsung berlaku tanpa perlu login ulang.
- `logout.php` hanya menerima **POST + token CSRF**, jadi tidak bisa dipicu dari tautan jahil.

### C6. Tabel database

| Tabel | Isi penting | Relasi |
|---|---|---|
| `users` | username, password (hash), nama, `role` (admin/pustakawan/mahasiswa), `status` (aktif/nonaktif) | – |
| `dokumen` | slug, judul, penulis, `jenis` (skripsi/laporan_kp/jurnal/ebook/catatan), prodi, tahun, abstrak, cover, file_pdf, `bisa_unduh`, `status` (terbit/menunggu/diturunkan), dibaca, unduhan | `pengunggah_id` → users (SET NULL bila user dihapus) |
| `pengumuman` | judul, isi, `tingkat`, `target` (semua/tamu/dosen/mahasiswa), tanggal tampil, `status` | `dibuat_oleh` → users |

Lihat lengkapnya di `database/schema.sql`, atau buka lewat **Laragon → Database** (HeidiSQL) / phpMyAdmin.

### C7. Latihan untuk memahami kode

1. **Telusuri login.** Mulai dari `<form>` di `masuk.php` → `User::login()` di `models/User.php` → `isLoggedIn()` di `config/config.php`. Catat di mana password dicek dan di mana sesi diisi.
2. **Ikuti satu CRUD.** Tambah pengumuman di panel, lalu cari di `panel/pengumuman.php` baris mana yang memproses `action=create`, dan method model mana yang menjalankan `INSERT`.
3. **Ubah kecil.** Ganti jumlah "Koleksi terbaru" di beranda dari 6 menjadi 4. Petunjuk: cari `terbaru(` di `index.php`.
4. **Cari penjaga keamanan.** Temukan semua tempat `csrf_check()` dipanggil (gunakan pencarian di editor). Mengapa hanya di blok POST?

---

## D. Masalah umum

| Gejala | Penyebab & solusi |
|---|---|
| `Not Found` saat membuka `localhost/horizone-lite/` | Nama folder bukan `horizone-lite`, atau folder tidak di `www`/`htdocs`. Periksa A2. |
| Halaman tanpa gaya (polos) / tautan salah | `BASE_URL` tidak cocok dengan nama folder. |
| `Koneksi gagal: SQLSTATE[HY000] [2002]` | MySQL belum jalan. Start All di Laragon / Start MySQL di XAMPP. |
| `SQLSTATE[HY000] [1045] Access denied` | Username/password MySQL beda. Ubah `config/database.php` **dan** `install.php`. |
| `Unknown database 'horizone_lite'` | `install.php` belum dijalankan. |
| `install.php` → 403 "Sudah terinstal" | Normal. Untuk pasang ulang: `php install.php --reset` (A6). |
| Unggah gagal tanpa pesan jelas / berkas tidak tersimpan | Folder `uploads/cover` atau `uploads/pdf` tidak bisa ditulis, atau berkas melebihi `upload_max_filesize`/`post_max_size` di `php.ini` (perlu ≥ 10M untuk PDF 10 MB). |
| `.htaccess` tidak berpengaruh (mis. `config/config.php` bisa dibuka) | Apache tidak mengizinkan override. Set `AllowOverride All` untuk folder web, restart Apache. |
| Error 500 setelah memindah ke server lain | `.htaccess` memakai sintaks Apache 2.4 (`<If>`, `Require`). Gunakan Apache 2.4 (Laragon/XAMPP terbaru); Nginx tidak membaca `.htaccess`. |
| Tema tidak tersimpan | Browser memblokir `localStorage` (mode privat). Normal; tema kembali ke bawaan. |
| Tombol Baca/Unduh tidak muncul | Belum login, dokumen tidak punya PDF, atau `bisa_unduh = 0` (hanya Unduh yang hilang). |
