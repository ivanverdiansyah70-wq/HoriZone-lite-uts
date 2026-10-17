<?php
// Halaman 403: akses ditolak karena role tidak sesuai.
require_once 'config/config.php';

http_response_code(403);
$judul = 'Tidak ada akses';
$halaman_aktif = '';
include ROOT_PATH . 'partials/head.php';
include ROOT_PATH . 'partials/site_header.php';
?>
<main id="isi" class="situs-main"><div class="situs-main__box--py-16-text-center-justify-center"><div class="situs-main__box--bg-primary-soft-place-items-center-rounded-2xl has-size"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="situs-main__icon situs-main__icon--size-7 has-size" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg></div><p class="situs-main__paragraph--mt-5-text-hz-overline-uppercase">Kode 403</p><h1 class="situs-main__title--mt-1-5">Anda tidak punya akses</h1><p class="situs-main__paragraph--max-w-md-mt-2-text-hz-body">Halaman ini hanya untuk pengelola dengan izin tertentu. Bila Anda memerlukannya, minta Admin memberi role yang sesuai.</p><div class="situs-main__box--mt-6-gap-2-w-full"><a data-slot="button" data-variant="default" data-size="lg" class="button button--px-6-text-hz-body-lg-medium-bg-primary-text-primary-foreground group/button has-size" href="<?= BASE_URL ?>index.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="button__icon button__icon--size-4 has-size" aria-hidden="true"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>Ke Beranda</a></div></div></main>
<footer class="situs-footer">© <?= date('Y') ?> Universitas Demo · Powered by HoriZone</footer>
</div>
</div>
<script src="<?= BASE_URL ?>assets/js/app.js" defer></script>
</body>
</html>
