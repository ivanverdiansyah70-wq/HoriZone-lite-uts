<?php
// Instalasi database HoriZone Lite. Jalankan via browser (/horizone-lite/install.php)
// atau CLI: php install.php. Tambah ?reset=1 (browser) / --reset (CLI) untuk menimpa data yang ada.
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cli = PHP_SAPI === 'cli';
// Reset hanya lewat CLI (K-13); ?reset=1 via browser diabaikan.
$reset = $cli && in_array('--reset', $argv ?? [], true);

function tulis($teks) {
    global $cli;
    echo $cli ? $teks . "\n" : '<p>' . htmlspecialchars($teks, ENT_QUOTES, 'UTF-8') . '</p>';
}

$host = 'localhost';
$db_name = 'horizone_lite';
$username = 'root';
$password = '';

if (!$cli) {
    ob_start();
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Instalasi Database - HoriZone Lite</h1>';
}

try {
    // Koneksi tanpa database (untuk membuat database)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    tulis('Koneksi ke MySQL berhasil.');

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    tulis("Database '$db_name' siap.");

    // Jangan timpa data yang sudah ada tanpa --reset
    $ada = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    if ($ada && !$cli) {
        ob_end_clean();
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Sudah terinstal. Reset hanya lewat CLI: php install.php --reset');
    }
    if ($ada && !$reset) {
        tulis('Tabel sudah ada, dilewati. Pakai ' . '--reset' . ' untuk memasang ulang (data hilang).');
    } else {
        $pdo->exec(file_get_contents(__DIR__ . '/database/schema.sql'));
        tulis('Schema dan data awal berhasil diimpor.');

        // PDF contoh: satu salinan per dokumen terbit yang bisa diunduh (K-19)
        $dirPdf = __DIR__ . '/uploads/pdf';
        if (!is_dir($dirPdf)) mkdir($dirPdf, 0755, true);
        $upd = $pdo->prepare('UPDATE dokumen SET file_pdf = :f WHERE id = :id');
        $nPdf = 0;
        foreach ($pdo->query("SELECT id, slug FROM dokumen WHERE status = 'terbit' AND bisa_unduh = 1")->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $rel = 'uploads/pdf/seed-' . $d['slug'] . '.pdf';
            if (copy(__DIR__ . '/database/seed/contoh.pdf', __DIR__ . '/' . $rel)) {
                $upd->execute([':f' => $rel, ':id' => $d['id']]);
                $nPdf++;
            }
        }
        tulis("PDF contoh disalin: $nPdf");
    }

    foreach (['users', 'dokumen', 'pengumuman'] as $tabel) {
        $n = $pdo->query("SELECT COUNT(*) FROM `$tabel`")->fetchColumn();
        tulis("Jumlah $tabel: $n");
    }

    tulis('Akun default: admin/admin123, pustakawan/pustakawan123, mahasiswa/mahasiswa123');
    if (!$cli) {
        echo '<p><a href="masuk.php">Mulai menggunakan HoriZone</a></p>';
    }
} catch (PDOException $e) {
    tulis('Error: ' . $e->getMessage());
    tulis('Pastikan MySQL (Laragon) sudah berjalan dan user root tanpa password.');
    exit(1);
}
