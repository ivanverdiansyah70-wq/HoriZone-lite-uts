<?php
// <head> bersama semua halaman (situs + panel). Berakhir di pembuka <body>.
// Variabel: $judul (judul halaman), $tema_server (opsional; 'dark' = kelas .dark dari server, hanya halaman uji).
$judul = $judul ?? 'HoriZone';
$gelap = (isset($tema_server) && $tema_server === 'dark');

// Chevron kustom prototipe (shadcn select-trigger) untuk <select> asli; dibungkus <div class="select-bungkus">.
if (!function_exists('chevron_select')) {
    function chevron_select() {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-trigger__icon has-size" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>';
    }
}
?>
<!doctype html>
<html lang="id"<?= $gelap ? ' class="dark"' : '' ?> data-preset="merah-istana">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= e($judul) ?> · HoriZone</title>
<link rel="icon" href="data:,">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<script>
// Anti-kedip: terapkan tema tersimpan sebelum halaman dirender.
(function () {
  try {
    var t = localStorage.getItem('theme'), h = document.documentElement;
    if (t === 'dark' || t === 'light') {
      h.classList.toggle('dark', t === 'dark');
      h.style.colorScheme = t;
    }
  } catch (e) {}
})();
</script>
</head>
<body>
