-- HoriZone Lite - skema + seed (MySQL 8.4, InnoDB, utf8mb4)
-- Diimpor oleh install.php. Seed mengikuti sample-data.json prototipe.
--
-- AKUN DEFAULT (untuk README):
--   admin       / admin123        (role admin, Yusuf Hidayat)
--   pustakawan  / pustakawan123   (role pustakawan, Rina Oktaviani)
--   mahasiswa   / mahasiswa123    (role mahasiswa, Raka Pratama)
--   Pengguna seed lain (username = slug nama) memakai sandi sesuai role-nya
--   (mahasiswa123 / pustakawan123).
-- Urutan tampil dokumen = ORDER BY created_at DESC, id DESC (K-04); created_at seed mengikuti urutan prototipe.
-- Catatan: peran "Dosen" prototipe disimpan role=mahasiswa, peran_tampil='Dosen'.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pengumuman;
DROP TABLE IF EXISTS dokumen;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,            -- dipakai di URL, mis. raka-pratama
  nama VARCHAR(150) NOT NULL,
  username VARCHAR(120) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,               -- hasil password_hash
  role ENUM('admin','pustakawan','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
  peran_tampil VARCHAR(40) NOT NULL DEFAULT 'Mahasiswa', -- label di daftar (Mahasiswa/Dosen/Pustakawan/Admin)
  lencana VARCHAR(60) NULL,                     -- mis. Pengunduh Skripsi
  berakhir DATE NULL,                           -- role/lencana berakhir
  nim_nidn VARCHAR(30) NULL,                    -- NULL untuk staf
  prodi VARCHAR(100) NULL,
  angkatan SMALLINT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  terakhir_masuk DATETIME NULL,
  dibaca INT UNSIGNED NOT NULL DEFAULT 0,
  unggahan INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dokumen (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(20) NOT NULL UNIQUE,             -- dipakai di URL, mis. d1
  judul VARCHAR(255) NOT NULL,
  penulis VARCHAR(255) NOT NULL,                -- nama dipisah koma
  jenis ENUM('skripsi','laporan_kp','jurnal','ebook','catatan') NOT NULL,
  prodi VARCHAR(100) NOT NULL,
  tahun SMALLINT NOT NULL,
  pembimbing VARCHAR(150) NULL,
  akses ENUM('publik-abstrak','warga-kampus','terbatas') NOT NULL DEFAULT 'publik-abstrak',
  bisa_unduh TINYINT(1) NOT NULL DEFAULT 0,
  dibaca INT UNSIGNED NOT NULL DEFAULT 0,
  unduhan INT UNSIGNED NOT NULL DEFAULT 0,
  halaman INT UNSIGNED NOT NULL DEFAULT 0,
  terbit_pada DATE NULL,
  kata_kunci VARCHAR(255) NULL,                 -- dipisah koma
  abstrak TEXT NULL,
  cover VARCHAR(255) NULL,                      -- path relatif, mis. assets/img/seed/poster.png
  file_pdf VARCHAR(255) NULL,                   -- path relatif di uploads/pdf/
  status ENUM('terbit','menunggu','diturunkan') NOT NULL DEFAULT 'terbit',
  pengunggah_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_dokumen_jenis (jenis),
  KEY idx_dokumen_status (status),
  KEY idx_dokumen_created (created_at),
  CONSTRAINT fk_dokumen_pengunggah FOREIGN KEY (pengunggah_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pengumuman (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  isi TEXT NULL,
  tingkat ENUM('info','peringatan') NOT NULL DEFAULT 'info',
  target ENUM('semua','tamu','dosen','mahasiswa') NOT NULL DEFAULT 'semua',
  tampil_mulai DATE NOT NULL,
  tampil_selesai DATE NOT NULL,
  status ENUM('tayang','selesai','terjadwal') NOT NULL DEFAULT 'tayang',
  dibuat_oleh INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pengumuman_pembuat FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id,slug,nama,username,email,password,role,peran_tampil,lencana,berakhir,nim_nidn,prodi,angkatan,status,terakhir_masuk,dibaca,unggahan) VALUES
(1,'raka-pratama','Raka Pratama','mahasiswa','raka.pratama@univdemo.test','$2y$10$lwieMZBZz7Adq6g.Tn777Oc2arv/7mteZc/K4hm7JUN9.965wITu6','mahasiswa','Mahasiswa',NULL,NULL,'2210511017','Informatika',2022,'aktif','2026-09-29 08:14:00',46,6),
(2,'nadia-putri-lestari','Nadia Putri Lestari','nadia-putri-lestari','nadia.lestari@univdemo.test','$2y$10$lwieMZBZz7Adq6g.Tn777Oc2arv/7mteZc/K4hm7JUN9.965wITu6','mahasiswa','Mahasiswa','Pengunduh Skripsi','2026-10-05','2110512004','Sistem Informasi',2021,'aktif','2026-09-29 07:51:00',64,4),
(3,'dr-hendra-kusuma-m-kom','Dr. Hendra Kusuma, M.Kom.','dr-hendra-kusuma-m-kom','hendra.kusuma@univdemo.test','$2y$10$lwieMZBZz7Adq6g.Tn777Oc2arv/7mteZc/K4hm7JUN9.965wITu6','mahasiswa','Dosen',NULL,NULL,'0412078503','Informatika',NULL,'aktif','2026-09-28 20:03:00',31,9),
(4,'rina-oktaviani','Rina Oktaviani','pustakawan','rina.oktaviani@univdemo.test','$2y$10$keTPO4Wat62NGdjjFw5v8uS124cdGFp32y1CSjMZZtgU/z/1t.rr2','pustakawan','Pustakawan',NULL,NULL,NULL,NULL,NULL,'aktif','2026-09-29 08:40:00',12,22),
(5,'fajar-ramadhan','Fajar Ramadhan','fajar-ramadhan','fajar.ramadhan@univdemo.test','$2y$10$lwieMZBZz7Adq6g.Tn777Oc2arv/7mteZc/K4hm7JUN9.965wITu6','mahasiswa','Mahasiswa','Pengunduh Skripsi','2026-10-03','2110511042','Informatika',2021,'aktif','2026-09-28 22:17:00',58,3),
(6,'siti-aminah-m-si','Siti Aminah, M.Si.','siti-aminah-m-si','siti.aminah@univdemo.test','$2y$10$lwieMZBZz7Adq6g.Tn777Oc2arv/7mteZc/K4hm7JUN9.965wITu6','mahasiswa','Dosen',NULL,NULL,'0305118702','Sistem Informasi',NULL,'aktif','2026-09-27 13:09:00',19,7),
(7,'dimas-aryo-nugroho','Dimas Aryo Nugroho','dimas-aryo-nugroho','dimas.nugroho@univdemo.test','$2y$10$lwieMZBZz7Adq6g.Tn777Oc2arv/7mteZc/K4hm7JUN9.965wITu6','mahasiswa','Mahasiswa',NULL,NULL,'2010531012','Ilmu Komunikasi',2020,'nonaktif','2026-08-02 10:44:00',22,1),
(8,'yusuf-hidayat','Yusuf Hidayat','admin','yusuf.hidayat@univdemo.test','$2y$10$FFxPdy/vVuD1BW/dZi5HUeYXJMG8o8WUTp2Irqubh0pJVDPZySDRK','admin','Admin',NULL,NULL,NULL,NULL,NULL,'aktif','2026-09-29 08:02:00',5,0),
(9,'hana-pertiwi','Hana Pertiwi','hana-pertiwi','hana-pertiwi@univdemo.test','$2y$10$keTPO4Wat62NGdjjFw5v8uS124cdGFp32y1CSjMZZtgU/z/1t.rr2','pustakawan','Pustakawan',NULL,'2026-10-04',NULL,NULL,NULL,'aktif','2026-09-29 07:30:00',8,14),
(10,'bagas-wicaksono','Bagas Wicaksono','bagas-wicaksono','bagas.wicaksono@univdemo.test','$2y$10$lwieMZBZz7Adq6g.Tn777Oc2arv/7mteZc/K4hm7JUN9.965wITu6','mahasiswa','Mahasiswa','Pengunduh Skripsi','2026-10-06','2110511008','Informatika',2021,'aktif','2026-09-27 19:12:00',27,2);

INSERT INTO dokumen (id,slug,judul,penulis,jenis,prodi,tahun,pembimbing,akses,bisa_unduh,dibaca,unduhan,halaman,terbit_pada,kata_kunci,abstrak,cover,file_pdf,status,pengunggah_id,created_at) VALUES
(1,'d1','Sistem Rekomendasi Buku Perpustakaan Menggunakan Collaborative Filtering','Nadia Putri Lestari','skripsi','Sistem Informasi',2025,'Siti Aminah, M.Si.','publik-abstrak',1,1284,96,84,'2026-08-14','sistem rekomendasi, collaborative filtering, perpustakaan','Penelitian ini membangun sistem rekomendasi buku untuk perpustakaan kampus dengan metode item-based collaborative filtering. Data peminjaman 3 tahun diolah untuk menghitung kemiripan antarbuku. Hasil pengujian menunjukkan nilai precision@10 sebesar 0,71 dan meningkatkan peminjaman buku rekomendasi sebesar 18%.',NULL,NULL,'terbit',4,'2026-09-30 00:00:00'),
(2,'d2','Analisis Sentimen Ulasan Aplikasi Akademik dengan IndoBERT','Fajar Ramadhan','skripsi','Informatika',2025,'Dr. Hendra Kusuma, M.Kom.','publik-abstrak',1,972,71,76,'2026-07-30','analisis sentimen, IndoBERT, NLP','Ulasan pengguna aplikasi akademik diklasifikasikan menjadi positif, netral, dan negatif memakai model IndoBERT yang di-fine-tune. Model mencapai F1 0,89.',NULL,NULL,'terbit',4,'2026-09-29 21:00:00'),
(3,'d3','Deteksi Plagiarisme Dokumen Akademik Berbasis N-Gram dan Winnowing','Dr. Hendra Kusuma, M.Kom., Fajar Ramadhan','jurnal','Informatika',2025,NULL,'publik-abstrak',1,640,58,12,'2026-06-02','plagiarisme, n-gram, winnowing','Artikel ini membandingkan n-gram dan winnowing untuk deteksi kemiripan dokumen akademik berbahasa Indonesia.',NULL,NULL,'terbit',4,'2026-09-29 20:00:00'),
(4,'d4','Pengantar Basis Data Relasional','Siti Aminah, M.Si.','ebook','Sistem Informasi',2024,NULL,'warga-kampus',0,2310,0,214,'2026-02-11','basis data, SQL, normalisasi','Buku ajar basis data relasional: model relasional, SQL, normalisasi, dan transaksi.','assets/img/seed/poster.png',NULL,'terbit',4,'2026-09-29 23:00:00'),
(5,'d5','Evaluasi Proses Rekrutmen di Koperasi Sejahtera Mandiri','Putu Ayu Kirana','laporan_kp','Manajemen',2025,'Andi Saputra, M.M.','warga-kampus',1,211,14,48,'2026-05-20','rekrutmen, SDM, koperasi','Laporan kerja praktik tentang evaluasi alur rekrutmen dan usulan perbaikannya.',NULL,NULL,'menunggu',4,'2026-09-29 18:00:00'),
(6,'d6','Pengaruh Literasi Keuangan terhadap Keputusan Investasi Mahasiswa','Citra Maharani','skripsi','Akuntansi',2025,'Andi Saputra, M.M.','terbatas',0,530,0,92,'2026-08-02','literasi keuangan, investasi','',NULL,NULL,'terbit',4,'2026-09-29 19:00:00'),
(7,'d7','Manajemen Pemasaran Digital untuk UMKM','Andi Saputra, M.M.','ebook','Manajemen',2024,NULL,'warga-kampus',0,1170,0,180,'2026-01-19','pemasaran digital, UMKM','Strategi pemasaran digital praktis untuk usaha mikro, kecil, dan menengah.','assets/img/seed/poster.png',NULL,'terbit',4,'2026-09-29 22:00:00'),
(8,'d8','Rangkuman Normalisasi 1NF hingga BCNF','Nadia Putri Lestari','catatan','Sistem Informasi',2026,NULL,'warga-kampus',0,402,0,9,'2026-09-18','normalisasi','',NULL,NULL,'terbit',4,'2026-09-29 06:00:00'),
(9,'d9','Pembahasan UTS Struktur Data 2025','Fajar Ramadhan','catatan','Informatika',2026,NULL,'warga-kampus',0,356,0,14,'2026-09-21','struktur data, UTS','',NULL,NULL,'terbit',4,'2026-09-29 05:00:00'),
(10,'d10','Pengembangan Modul Absensi Berbasis QR di Dinas Kominfo Kota Contoh','Raka Pratama','laporan_kp','Informatika',2025,'Dr. Hendra Kusuma, M.Kom.','warga-kampus',1,188,9,52,'2026-03-12','absensi, QR code','',NULL,NULL,'diturunkan',4,'2026-09-29 17:00:00'),
(11,'d11','Perancangan UI Aplikasi Konseling Mahasiswa Berbasis Design Thinking','Dimas Aryo Nugroho','skripsi','Ilmu Komunikasi',2025,'Siti Aminah, M.Si.','publik-abstrak',1,315,22,88,'2026-09-25','design thinking, UI','',NULL,NULL,'terbit',4,'2026-09-29 16:00:00'),
(12,'d12','Adopsi E-Wallet oleh Pedagang Pasar Tradisional','Andi Saputra, M.M., Putu Ayu Kirana','jurnal','Manajemen',2026,NULL,'publik-abstrak',1,204,17,10,'2026-09-27','e-wallet, UMKM','',NULL,NULL,'terbit',4,'2026-09-29 15:00:00'),
(13,'d13','Rancang Bangun Repositori Institusi Berbasis Standar Dublin Core','Bagas Wicaksono','skripsi','Sistem Informasi',2025,'Siti Aminah, M.Si.','publik-abstrak',1,486,41,92,'2026-07-21','repositori institusi, Dublin Core, metadata','Skripsi ini merancang repositori institusi dengan skema metadata Dublin Core agar karya ilmiah kampus mudah ditemukan dan dipanen layanan pengindeks.',NULL,NULL,'terbit',4,'2026-09-29 14:00:00'),
(14,'d14','Evaluasi Usabilitas Repositori Digital Kampus dengan System Usability Scale','Siti Aminah, M.Si., Maya Anggraini','jurnal','Sistem Informasi',2026,NULL,'publik-abstrak',1,452,37,11,'2026-08-30','usabilitas, SUS, repositori digital','Pengujian usabilitas repositori digital kampus pada 40 responden menghasilkan skor SUS 72,5 (kategori baik) dengan catatan perbaikan pada fitur pencarian lanjutan.',NULL,NULL,'terbit',4,'2026-09-29 12:00:00'),
(15,'d15','Pencarian Toleran Salah Ketik pada Katalog Perpustakaan Menggunakan Levenshtein Distance','Raka Pratama','skripsi','Informatika',2026,'Dr. Hendra Kusuma, M.Kom.','publik-abstrak',1,431,29,76,'2026-09-10','levenshtein, pencarian, katalog','Penelitian ini menerapkan Levenshtein distance untuk mencocokkan kata kunci yang salah ketik dengan judul katalog, dengan akurasi 91% pada 500 kueri uji.',NULL,NULL,'terbit',4,'2026-09-29 13:00:00'),
(16,'d16','Klasifikasi Otomatis Subjek Skripsi dengan Naive Bayes','Dr. Hendra Kusuma, M.Kom., Bagas Wicaksono','jurnal','Informatika',2025,NULL,'publik-abstrak',1,398,33,9,'2026-02-14','klasifikasi, naive bayes, subjek','Artikel ini mengklasifikasikan subjek skripsi dari abstrak berbahasa Indonesia menggunakan Naive Bayes multinomial dengan F1 0,84.',NULL,NULL,'terbit',4,'2026-09-29 10:00:00'),
(17,'d17','Digitalisasi Arsip Surat Masuk di Kantor Kecamatan Contoh','Fajar Ramadhan','laporan_kp','Informatika',2025,'Dr. Hendra Kusuma, M.Kom.','warga-kampus',1,276,14,38,'2026-03-05','arsip digital, OCR, kerja praktik','Laporan kerja praktik tentang pemindaian dan pengindeksan 2.400 surat masuk menjadi arsip digital yang bisa dicari.',NULL,NULL,'terbit',4,'2026-09-29 09:00:00'),
(18,'d18','Analisis Kesiapan Adopsi Perpustakaan Digital pada Mahasiswa Baru','Dimas Aryo Nugroho','skripsi','Sistem Informasi',2025,'Siti Aminah, M.Si.','publik-abstrak',1,344,22,88,'2026-05-19','TAM, perpustakaan digital, adopsi','Penelitian kuantitatif dengan model TAM pada 210 mahasiswa baru menunjukkan persepsi kemudahan paling berpengaruh terhadap niat memakai perpustakaan digital.',NULL,NULL,'terbit',4,'2026-09-29 11:00:00'),
(19,'d19','Panduan Menulis Karya Ilmiah untuk Mahasiswa','Rina Oktaviani','ebook','Ilmu Komunikasi',2024,NULL,'warga-kampus',1,512,61,146,'2024-11-02','karya ilmiah, sitasi, panduan','Ebook panduan langkah demi langkah menyusun proposal, mengutip sumber, dan menyiapkan naskah untuk diunggah ke repositori.','assets/img/seed/poster.png',NULL,'terbit',4,'2026-09-29 07:00:00'),
(20,'d20','Strategi Promosi Layanan Perpustakaan melalui Media Sosial','Andi Saputra, M.M., Rina Oktaviani','jurnal','Ilmu Komunikasi',2026,NULL,'publik-abstrak',1,167,12,10,'2026-09-18','promosi, media sosial, perpustakaan','Studi kasus kampanye Instagram perpustakaan kampus selama satu semester dan pengaruhnya terhadap kunjungan layanan.',NULL,NULL,'terbit',4,'2026-09-29 03:00:00'),
(21,'d21','Sistem Informasi Akuntansi Persediaan Barang pada UMKM Batik','Putu Ayu Kirana','skripsi','Akuntansi',2025,'Andi Saputra, M.M.','terbatas',1,143,0,81,'2026-04-22','persediaan, UMKM, sistem informasi akuntansi','Skripsi ini membangun sistem pencatatan persediaan barang metode FIFO untuk UMKM batik skala rumah tangga.',NULL,NULL,'terbit',4,'2026-09-29 02:00:00'),
(22,'d22','Pengembangan Dasbor Statistik Pengunjung Perpustakaan Daerah','Maya Anggraini','laporan_kp','Sistem Informasi',2026,'Siti Aminah, M.Si.','warga-kampus',1,129,8,42,'2026-08-08','dasbor, statistik, perpustakaan daerah','Laporan kerja praktik pembuatan dasbor kunjungan harian dan peminjaman untuk perpustakaan daerah.',NULL,NULL,'terbit',4,'2026-09-29 08:00:00'),
(23,'d23','Ringkasan Materi Sistem Temu Kembali Informasi','Raka Pratama','catatan','Informatika',2026,NULL,'warga-kampus',1,118,9,14,'2026-09-15','temu kembali informasi, TF-IDF, catatan kuliah','Catatan kuliah: model boolean, TF-IDF, dan evaluasi precision-recall untuk sistem pencarian dokumen.',NULL,NULL,'terbit',4,'2026-09-29 04:00:00'),
(24,'d24','Pengaruh Literasi Informasi terhadap Kualitas Skripsi Mahasiswa Manajemen','Nadia Putri Lestari','skripsi','Manajemen',2024,'Andi Saputra, M.M.','publik-abstrak',1,97,6,79,'2025-01-17','literasi informasi, kualitas skripsi','Survei pada 150 mahasiswa tingkat akhir menunjukkan literasi informasi berpengaruh positif dan signifikan terhadap kualitas skripsi.',NULL,NULL,'terbit',4,'2026-09-29 01:00:00');

INSERT INTO pengumuman (id,judul,isi,tingkat,target,tampil_mulai,tampil_selesai,status,dibuat_oleh) VALUES
(1,'Batas unggah Skripsi semester ganjil: 31 Oktober 2026','Pastikan pembimbing utama sudah menyetujui sebelum diajukan ke perpustakaan.','info','semua','2026-09-20','2026-10-31','tayang',8),
(2,'Perpustakaan tutup 17 Oktober (pemeliharaan server)','Repositori tidak dapat diakses pukul 08.00–16.00 WIB. Selesaikan unggahan yang sedang berjalan sebelum hari itu.','peringatan','semua','2026-10-01','2026-10-17','tayang',8),
(3,'Aktivasi akun mahasiswa baru dibuka','Mahasiswa angkatan 2026 dapat mengaktifkan akun dengan NIM dan email kampus melalui menu Aktivasi akun.','info','tamu','2026-09-01','2026-09-30','tayang',8),
(4,'Pelatihan unggah karya untuk dosen',NULL,'info','dosen','2026-09-02','2026-09-09','selesai',8),
(5,'Jam layanan perpustakaan selama UAS',NULL,'info','mahasiswa','2026-12-01','2026-12-12','terjadwal',8);
