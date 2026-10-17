<?php
// Footer situs: footer teks + navigasi bawah (HP) + penutup #root + skrip. Pasangan site_header.php.
// Variabel: $halaman_aktif ('beranda' | 'jelajah' | ''); sesi: user_role bila login.
$halaman_aktif = $halaman_aktif ?? '';
$login = isLoggedIn();
$nav = 'situs-nav__list-link situs-nav__list-link--text-hz-hint-';
?>
<footer class="situs-footer">© <?= date('Y') ?> Universitas Demo · Powered by HoriZone</footer>
<nav aria-label="Menu utama" class="situs-nav"><ul class="situs-nav__list">
<li><a <?= $halaman_aktif === 'beranda' ? 'aria-current="page" ' : '' ?>class="<?= $nav . ($halaman_aktif === 'beranda' ? 'semibold' : 'medium') ?>" href="<?= BASE_URL ?>index.php"><?php if ($halaman_aktif === 'beranda'): ?><span aria-hidden="true" class="situs-nav__list-text"></span><?php endif; ?><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="situs-nav__list-icon has-size" aria-hidden="true"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>Beranda</a></li>
<li><a <?= $halaman_aktif === 'jelajah' ? 'aria-current="page" ' : '' ?>class="<?= $nav . ($halaman_aktif === 'jelajah' ? 'semibold' : 'medium') ?>" href="<?= BASE_URL ?>jelajah.php"><?php if ($halaman_aktif === 'jelajah'): ?><span aria-hidden="true" class="situs-nav__list-text"></span><?php endif; ?><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="situs-nav__list-icon has-size" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z"></path></svg>Jelajah</a></li>
<li><?php if ($login): ?><form method="post" action="<?= BASE_URL ?>logout.php" class="form-keluar"><?= csrf_field() ?><button class="situs-nav__list-button" type="submit"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="situs-nav__list-icon has-size" aria-hidden="true"><circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path></svg>Keluar</button></form><?php else: ?><a class="situs-nav__list-button" href="<?= BASE_URL ?>masuk.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="situs-nav__list-icon has-size" aria-hidden="true"><circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path></svg>Akun</a><?php endif; ?></li>
</ul></nav>
</div>
</div>
<script src="<?= BASE_URL ?>assets/js/app.js" defer></script>
</body>
</html>
