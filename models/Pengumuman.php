<?php
class Pengumuman {
    private $conn;
    private $table_name = "pengumuman";

    public $id;
    public $judul;
    public $isi;
    public $tingkat;
    public $target;
    public $tampil_mulai;
    public $tampil_selesai;
    public $status;
    public $dibuat_oleh;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET judul=:judul, isi=:isi, tingkat=:tingkat, target=:target,
                      tampil_mulai=:tampil_mulai, tampil_selesai=:tampil_selesai,
                      status=:status, dibuat_oleh=:dibuat_oleh";
        $stmt = $this->conn->prepare($query);
        $tingkat = $this->tingkat ?: 'info';
        $target = $this->target ?: 'semua';
        $status = $this->status ?: 'tayang';

        $stmt->bindParam(':judul', $this->judul);
        $stmt->bindParam(':isi', $this->isi);
        $stmt->bindParam(':tingkat', $tingkat);
        $stmt->bindParam(':target', $target);
        $stmt->bindParam(':tampil_mulai', $this->tampil_mulai);
        $stmt->bindParam(':tampil_selesai', $this->tampil_selesai);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':dibuat_oleh', $this->dibuat_oleh);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " ORDER BY id");
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            foreach ($row as $kolom => $nilai) {
                $this->$kolom = $nilai;
            }
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET judul=:judul, isi=:isi, tingkat=:tingkat, target=:target,
                      tampil_mulai=:tampil_mulai, tampil_selesai=:tampil_selesai, status=:status
                  WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':judul', $this->judul);
        $stmt->bindParam(':isi', $this->isi);
        $stmt->bindParam(':tingkat', $this->tingkat);
        $stmt->bindParam(':target', $this->target);
        $stmt->bindParam(':tampil_mulai', $this->tampil_mulai);
        $stmt->bindParam(':tampil_selesai', $this->tampil_selesai);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function delete() {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id = :id");
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Banner beranda: berstatus tayang, punya isi, untuk semua (atau $target), paling lama $batas baris
    public function tayang($target = 'semua', $batas = 3) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . "
            WHERE status = 'tayang' AND isi IS NOT NULL AND target IN ('semua', :target)
            ORDER BY id LIMIT :batas");
        $stmt->bindParam(':target', $target);
        $stmt->bindValue(':batas', (int) $batas, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function hitung() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table_name);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
