<?php
class Dokumen {
    private $conn;
    private $table_name = "dokumen";

    // Label tampil untuk nilai enum
    const JENIS = [
        'skripsi' => 'Skripsi',
        'laporan_kp' => 'Laporan KP',
        'jurnal' => 'Jurnal',
        'ebook' => 'Ebook',
        'catatan' => 'Catatan',
    ];
    const AKSES = [
        'publik-abstrak' => 'Publik-abstrak',
        'warga-kampus' => 'Warga kampus',
        'terbatas' => 'Terbatas',
    ];
    const STATUS = [
        'terbit' => 'Terbit',
        'menunggu' => 'Menunggu pustakawan',
        'diturunkan' => 'Diturunkan',
    ];

    public $id;
    public $slug;
    public $judul;
    public $penulis;
    public $jenis;
    public $prodi;
    public $tahun;
    public $pembimbing;
    public $akses;
    public $bisa_unduh;
    public $dibaca;
    public $unduhan;
    public $halaman;
    public $terbit_pada;
    public $kata_kunci;
    public $abstrak;
    public $cover;
    public $file_pdf;
    public $status;
    public $pengunggah_id;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Isi properti dari baris DB
    private function isi($row) {
        foreach ($row as $kolom => $nilai) {
            $this->$kolom = $nilai;
        }
    }

    private function ambil($where, $params) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " WHERE " . $where . " LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->isi($row);
            return true;
        }
        return false;
    }

    public function create() {
        // slug sementara, lalu diganti 'd' + id (mis. d25)
        $sementara = 'tmp' . bin2hex(random_bytes(6));
        $query = "INSERT INTO " . $this->table_name . "
                  SET slug=:slug, judul=:judul, penulis=:penulis, jenis=:jenis, prodi=:prodi, tahun=:tahun,
                      pembimbing=:pembimbing, akses=:akses, bisa_unduh=:bisa_unduh, halaman=:halaman,
                      terbit_pada=:terbit_pada, kata_kunci=:kata_kunci, abstrak=:abstrak, cover=:cover,
                      file_pdf=:file_pdf, status=:status, pengunggah_id=:pengunggah_id";
        $stmt = $this->conn->prepare($query);
        $akses = $this->akses ?: 'publik-abstrak';
        $status = $this->status ?: 'terbit';
        $bisa = $this->bisa_unduh ? 1 : 0;
        $halaman = (int) $this->halaman;

        $stmt->bindParam(':slug', $sementara);
        $stmt->bindParam(':judul', $this->judul);
        $stmt->bindParam(':penulis', $this->penulis);
        $stmt->bindParam(':jenis', $this->jenis);
        $stmt->bindParam(':prodi', $this->prodi);
        $stmt->bindParam(':tahun', $this->tahun);
        $stmt->bindParam(':pembimbing', $this->pembimbing);
        $stmt->bindParam(':akses', $akses);
        $stmt->bindParam(':bisa_unduh', $bisa);
        $stmt->bindParam(':halaman', $halaman);
        $stmt->bindParam(':terbit_pada', $this->terbit_pada);
        $stmt->bindParam(':kata_kunci', $this->kata_kunci);
        $stmt->bindParam(':abstrak', $this->abstrak);
        $stmt->bindParam(':cover', $this->cover);
        $stmt->bindParam(':file_pdf', $this->file_pdf);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':pengunggah_id', $this->pengunggah_id);

        if (!$stmt->execute()) {
            return false;
        }
        $this->id = $this->conn->lastInsertId();
        $this->slug = 'd' . $this->id;
        $s = $this->conn->prepare("UPDATE " . $this->table_name . " SET slug = :slug WHERE id = :id");
        $s->bindParam(':slug', $this->slug);
        $s->bindParam(':id', $this->id);
        return $s->execute();
    }

    // Semua dokumen, terbaru dulu
    public function readAll() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC, id DESC");
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        return $this->ambil("id = :id", [':id' => $this->id]);
    }

    // Cari lewat slug (dipakai URL dokumen.php?id=d1)
    public function readBySlug($slug) {
        return $this->ambil("slug = :slug", [':slug' => $slug]);
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET judul=:judul, penulis=:penulis, jenis=:jenis, prodi=:prodi, tahun=:tahun,
                      pembimbing=:pembimbing, akses=:akses, bisa_unduh=:bisa_unduh, halaman=:halaman,
                      terbit_pada=:terbit_pada, kata_kunci=:kata_kunci, abstrak=:abstrak, cover=:cover,
                      file_pdf=:file_pdf, status=:status
                  WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $bisa = $this->bisa_unduh ? 1 : 0;
        $stmt->bindParam(':judul', $this->judul);
        $stmt->bindParam(':penulis', $this->penulis);
        $stmt->bindParam(':jenis', $this->jenis);
        $stmt->bindParam(':prodi', $this->prodi);
        $stmt->bindParam(':tahun', $this->tahun);
        $stmt->bindParam(':pembimbing', $this->pembimbing);
        $stmt->bindParam(':akses', $this->akses);
        $stmt->bindParam(':bisa_unduh', $bisa);
        $stmt->bindParam(':halaman', $this->halaman);
        $stmt->bindParam(':terbit_pada', $this->terbit_pada);
        $stmt->bindParam(':kata_kunci', $this->kata_kunci);
        $stmt->bindParam(':abstrak', $this->abstrak);
        $stmt->bindParam(':cover', $this->cover);
        $stmt->bindParam(':file_pdf', $this->file_pdf);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id = :id");
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Pencarian: kata kunci (judul/penulis), filter jenis & status (null = semua).
    // $hanyaTerbit=true untuk situs publik (K-07); panel memanggil dengan false.
    public function cari($kata = '', $jenis = null, $status = null, $hanyaTerbit = true) {
        $where = [];
        $params = [];
        if ($hanyaTerbit) {
            $where[] = "status = 'terbit'";
        }
        if ($kata !== '' && $kata !== null) {
            $where[] = "(judul LIKE :k1 OR penulis LIKE :k2)";
            foreach (['k1', 'k2'] as $p) {
                $params[':' . $p] = '%' . addcslashes((string) $kata, '%_\\') . '%';
            }
        }
        if ($jenis) {
            $where[] = "jenis = :jenis";
            $params[':jenis'] = $jenis;
        }
        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        $sql = "SELECT * FROM " . $this->table_name
            . ($where ? " WHERE " . implode(" AND ", $where) : "")
            . " ORDER BY created_at DESC, id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // "Koleksi terbaru" beranda: dokumen terbit paling baru, maksimal $n
    public function terbaru($n = 6) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . "
            WHERE status = 'terbit' ORDER BY created_at DESC, id DESC LIMIT :n");
        $stmt->bindValue(':n', (int) $n, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // "Paling banyak dibaca" beranda (hanya terbit), maksimal $n
    public function populer($n = 6) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . "
            WHERE status = 'terbit' ORDER BY dibaca DESC, id LIMIT :n");
        $stmt->bindValue(':n', (int) $n, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Panel data koleksi: semua dokumen (semua status), terbaru dulu, per halaman
    public function readPanel($mulai = 0, $batas = 8) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . "
            ORDER BY created_at DESC, id DESC LIMIT :batas OFFSET :mulai");
        $stmt->bindValue(':batas', (int) $batas, PDO::PARAM_INT);
        $stmt->bindValue(':mulai', max(0, (int) $mulai), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Total baris panel (semua dokumen) untuk paginasi
    public function hitungPanel() {
        return $this->hitung();
    }

    // Jumlah dokumen terbit per jenis: ['skripsi' => n, ...] (semua jenis ada, 0 bila kosong)
    public function hitungPerJenis() {
        $hasil = array_fill_keys(array_keys(self::JENIS), 0);
        $stmt = $this->conn->query("SELECT jenis, COUNT(*) n FROM " . $this->table_name . " WHERE status = 'terbit' GROUP BY jenis");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hasil[$row['jenis']] = (int) $row['n'];
        }
        return $hasil;
    }

    // Hitung dokumen; filter jenis/status opsional
    public function hitung($jenis = null, $status = null) {
        $where = [];
        $params = [];
        if ($jenis) {
            $where[] = "jenis = :jenis";
            $params[':jenis'] = $jenis;
        }
        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table_name . ($where ? " WHERE " . implode(" AND ", $where) : ""));
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
    // Ringkasan seluruh dokumen: total, jumlah dibaca, jumlah unduhan (satu query)
    public function statistik() {
        $stmt = $this->conn->query("SELECT COUNT(*) total, COALESCE(SUM(dibaca),0) dibaca, COALESCE(SUM(unduhan),0) unduhan FROM " . $this->table_name);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Catat satu pembacaan / unduhan
    public function tambahDibaca() {
        $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET dibaca = dibaca + 1 WHERE id = :id");
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function tambahUnduhan() {
        $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET unduhan = unduhan + 1 WHERE id = :id");
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
