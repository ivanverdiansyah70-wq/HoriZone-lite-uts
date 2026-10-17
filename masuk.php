<?php
// Halaman masuk. GET: form. POST: cek CSRF, login lewat model User, regenerasi sesi, redirect menurut role.
require_once 'config/config.php';

// Tujuan setelah login: staf -> panel, mahasiswa -> beranda
function tujuanSetelahMasuk($role) {
    return BASE_URL . (in_array($role, ['admin', 'pustakawan'], true) ? 'panel/dasbor.php' : 'index.php');
}

if (isLoggedIn()) {
    header('Location: ' . tujuanSetelahMasuk($_SESSION['user_role']));
    exit();
}

$username = '';
$galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim(is_string($_POST['username'] ?? null) ? $_POST['username'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $user = new User((new Database())->getConnection());
    // login(): cek status aktif + password_verify, lalu perbarui terakhir_masuk
    if ($username !== '' && $password !== '' && $user->login($username, $password)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['user_nama'] = $user->nama;
        header('Location: ' . tujuanSetelahMasuk($user->role));
        exit();
    }
    // Pesan sama untuk akun tak ada / sandi salah / akun nonaktif (tidak membocorkan info)
    $galat = 'NIM/NIDN, email, atau password salah.';
}

$judul = 'Masuk';
$halaman_aktif = '';
include ROOT_PATH . 'partials/head.php';
include ROOT_PATH . 'partials/site_header.php';
?>
<main id="isi" class="situs-main"><div class="situs-main__box--flex-1-mx-auto-w-full"><section class="situs-main__section--flex-1"><aside class="situs-main__section-sidebar"><div class="situs-main__section-sidebar-box">HORIZONE</div><img alt="" width="210" height="298" class="situs-main__section-sidebar-image" src="<?= BASE_URL ?>assets/img/seed/poster.png"><div><h2 class="situs-main__section-sidebar-heading">Baca, diskusi, dan bagikan karya ilmiah kampus.</h2><p class="situs-main__section-sidebar-paragraph">Skripsi, laporan KP, jurnal, ebook, dan catatan kuliah Universitas Demo dalam satu tempat.</p></div></aside><div class="situs-main__section-box--bg-hero-py-5-text-hero-foreground-px-4"><h2 class="situs-main__section-heading--text-hz-h4">Baca, diskusi, dan bagikan karya ilmiah kampus.</h2><p class="situs-main__section-paragraph--mt-1">Skripsi, laporan KP, jurnal, ebook, dan catatan kuliah Universitas Demo dalam satu tempat.</p></div><div class="situs-main__section-box--gap-4-pb-6-pt-6-flex-1"><form class="situs-main__section-form situs-main__section-form--flex-col" method="post" action="<?= BASE_URL ?>masuk.php" novalidate><?= csrf_field() ?><h1 class="situs-main__section-form-title">Masuk</h1><p class="situs-main__section-form-paragraph--text-hz-body">Masuk dengan NIM/NIDN atau email kampus.</p><div class="situs-main__section-form-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="identity">NIM/NIDN atau email</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="identity" autocomplete="username" placeholder="2210511017 atau nama@univdemo.test"<?= $galat ? ' aria-invalid="true"' : '' ?> value="<?= e($username) ?>" name="username"></div><div class="situs-main__section-form-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="password">Password</label><div class="situs-main__section-form-box--relative"><input data-slot="input" class="input input--pr-11-text-hz-body-h-42px" id="password" autocomplete="current-password" placeholder="••••••••"<?= $galat ? ' aria-invalid="true" aria-describedby="password-desc"' : '' ?> type="password" name="password"><button type="button" aria-label="Tampilkan password" aria-pressed="false" class="situs-main__section-form-button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="situs-main__section-form-icon situs-main__section-form-icon--size-4 has-size" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg></button></div><?php if ($galat): ?><p id="password-desc" class="situs-main__section-form-paragraph--mt-px"><?= e($galat) ?></p><?php endif; ?></div><button data-slot="button" data-variant="default" data-size="lg" class="button button--w-full-px-6-text-hz-body-lg-medium-bg-primary group/button has-size" type="submit">Masuk</button></form><p class="situs-main__section-paragraph--mt-auto">© <?= date('Y') ?> Universitas Demo · Powered by HoriZone</p></div></section></div></main>
<footer class="situs-footer">© <?= date('Y') ?> Universitas Demo · Powered by HoriZone</footer>
</div>
</div>
<script src="<?= BASE_URL ?>assets/js/app.js" defer></script>
</body>
</html>
