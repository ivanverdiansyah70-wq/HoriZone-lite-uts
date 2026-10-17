<?php
// Keluar: hanya POST + CSRF (K-11); GET ditolak 405 agar tidak bisa dipicu tautan/gambar lintas situs.
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}
csrf_check();

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . 'masuk.php');
exit();
