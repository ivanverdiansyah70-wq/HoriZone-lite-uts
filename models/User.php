<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $slug;
    public $nama;
    public $username;
    public $email;
    public $password;        // plaintext saat create/update/login; di DB tersimpan hash
    public $role;
    public $peran_tampil;
    public $lencana;
    public $berakhir;
    public $nim_nidn;
    public $prodi;
    public $angkatan;
    public $status;
    public $terakhir_masuk;
    public $dibaca;
    public $unggahan;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    private static function buatSlug($teks) {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($teks)), '-');
    }

    // Isi properti dari baris DB
    private function isi($row) {
        foreach ($row as $kolom => $nilai) {
            $this->$kolom = $nilai;
        }
    }

    // Ambil satu baris dengan kondisi, isi properti
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
        if (empty($this->slug)) {
            $this->slug = self::buatSlug($this->nama);
        }
        $query = "INSERT INTO " . $this->table_name . "
                  SET slug=:slug, nama=:nama, username=:username, email=:email, password=:password,
                      role=:role, peran_tampil=:peran_tampil, lencana=:lencana, berakhir=:berakhir,
                      nim_nidn=:nim_nidn, prodi=:prodi, angkatan=:angkatan, status=:status";
        $stmt = $this->conn->prepare($query);
        $hash = password_hash($this->password, PASSWORD_DEFAULT);
        $role = $this->role ?: 'mahasiswa';
        $peran = $this->peran_tampil ?: ucfirst($role);
        $status = $this->status ?: 'aktif';

        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':peran_tampil', $peran);
        $stmt->bindParam(':lencana', $this->lencana);
        $stmt->bindParam(':berakhir', $this->berakhir);
        $stmt->bindParam(':nim_nidn', $this->nim_nidn);
        $stmt->bindParam(':prodi', $this->prodi);
        $stmt->bindParam(':angkatan', $this->angkatan);
        $stmt->bindParam(':status', $status);

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
        return $this->ambil("id = :id", [':id' => $this->id]);
    }

    // Cari lewat slug (dipakai URL panel/pengguna)
    public function readBySlug($slug) {
        return $this->ambil("slug = :slug", [':slug' => $slug]);
    }

    // Cari lewat username atau email
    public function findByUsername($username) {
        return $this->ambil("username = :u1 OR email = :u2", [':u1' => $username, ':u2' => $username]);
    }

    // Verifikasi login; sukses -> properti terisi (password = hash), terakhir_masuk diperbarui
    public function login($username, $passwordPolos) {
        if (!$this->findByUsername($username)) {
            return false;
        }
        if ($this->status !== 'aktif' || !password_verify($passwordPolos, $this->password)) {
            return false;
        }
        $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET terakhir_masuk = NOW() WHERE id = :id");
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        return true;
    }

    // Password hanya diganti bila $this->password tidak kosong
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nama=:nama, username=:username, email=:email, role=:role, peran_tampil=:peran_tampil,
                      lencana=:lencana, berakhir=:berakhir, nim_nidn=:nim_nidn, prodi=:prodi,
                      angkatan=:angkatan, status=:status
                  WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':peran_tampil', $this->peran_tampil);
        $stmt->bindParam(':lencana', $this->lencana);
        $stmt->bindParam(':berakhir', $this->berakhir);
        $stmt->bindParam(':nim_nidn', $this->nim_nidn);
        $stmt->bindParam(':prodi', $this->prodi);
        $stmt->bindParam(':angkatan', $this->angkatan);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        if (!$stmt->execute()) {
            return false;
        }
        if (!empty($this->password)) {
            $hash = password_hash($this->password, PASSWORD_DEFAULT);
            $p = $this->conn->prepare("UPDATE " . $this->table_name . " SET password = :password WHERE id = :id");
            $p->bindParam(':password', $hash);
            $p->bindParam(':id', $this->id);
            return $p->execute();
        }
        return true;
    }

    public function delete() {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id = :id");
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Hitung pengguna; $status null = semua
    public function hitung($status = null) {
        if ($status === null) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table_name);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table_name . " WHERE status = :status");
            $stmt->bindParam(':status', $status);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
