<?php
// Konfigurasi umum aplikasi
define('BASE_URL', '/horizone-lite/');
define('APP_NAME', 'HoriZone');
define('ROOT_PATH', dirname(__DIR__) . '/');

session_start();

// Autoload class dari models/
spl_autoload_register(function ($class_name) {
    $file = ROOT_PATH . 'models/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once ROOT_PATH . 'config/database.php';

// ---- Autentikasi ----
// K-16: baca ulang user dari DB sekali per request; hilang/nonaktif -> sesi dibersihkan (tamu),
// selain itu role & nama di sesi disegarkan dari DB.
function isLoggedIn() {
    static $hasil = null;
    if ($hasil !== null) {
        return $hasil;
    }
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return $hasil = false;
    }
    $user = new User((new Database())->getConnection());
    $user->id = $_SESSION['user_id'];
    if (!$user->readOne() || $user->status !== 'aktif') {
        $_SESSION = [];
        session_regenerate_id(true);
        return $hasil = false;
    }
    $_SESSION['user_role'] = $user->role;
    $_SESSION['user_nama'] = $user->nama;
    return $hasil = true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'masuk.php');
        exit();
    }
}

function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles, true)) {
        header('Location: ' . BASE_URL . 'unauthorized.php');
        exit();
    }
}

// ---- Keluaran & CSRF ----
// Escape saat render (bukan saat simpan)
function e($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Panggil di awal penanganan POST; gagal -> 400 dan berhenti
function csrf_check() {
    $kirim = $_POST['csrf_token'] ?? '';
    if (!is_string($kirim) || !hash_equals(csrf_token(), $kirim)) {
        http_response_code(400);
        exit('Permintaan tidak valid (token CSRF).');
    }
}

// ---- Format tanggal Indonesia ----
const BULAN_SINGKAT = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// mode: 'tanggal' = 14 Agu 2026 | 'waktu' = 29 Sep 2026, 08.14 | 'singkat' = 29 Sep, 08.14
function formatTanggal($nilai, $mode = 'tanggal') {
    if (empty($nilai)) {
        return '';
    }
    $t = strtotime($nilai);
    $tgl = date('j', $t) . ' ' . BULAN_SINGKAT[(int) date('n', $t)];
    if ($mode === 'singkat') {
        return $tgl . ', ' . date('H.i', $t);
    }
    $tgl .= ' ' . date('Y', $t);
    return $mode === 'waktu' ? $tgl . ', ' . date('H.i', $t) : $tgl;
}

// 20 Sep – 31 Okt 2026 | 1 – 17 Okt 2026 (bulan sama)
function formatRentang($mulai, $selesai) {
    $a = strtotime($mulai);
    $b = strtotime($selesai);
    $awal = date('j', $a);
    if (date('Y-n', $a) !== date('Y-n', $b)) {
        $awal .= ' ' . BULAN_SINGKAT[(int) date('n', $a)];
    }
    return $awal . ' – ' . formatTanggal($selesai);
}
