<?php
// Jelajah & Cari: daftar dokumen terbit, cari judul/penulis (GET q), saring jenis (GET jenis). Hanya baca (K-07).
require_once 'config/config.php';

// Ambil parameter GET: bukan string (mis. q[]=x) = diabaikan; jenis di luar daftar = diabaikan
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$q = mb_substr($q, 0, 100);
$jenis = is_string($_GET['jenis'] ?? null) && isset(Dokumen::JENIS[$_GET['jenis']]) ? $_GET['jenis'] : null;

$dokumen = new Dokumen((new Database())->getConnection());
$hasil = $dokumen->cari($q, $jenis, null, true)->fetchAll(PDO::FETCH_ASSOC);
$jumlah = count($hasil);

// Ikon lucide per jenis (isi <svg>)
$IKON = [
    'skripsi' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path><path d="M22 10v6"></path><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>',
    'jurnal' => '<path d="M15 12h-5"></path><path d="M15 8h-5"></path><path d="M19 17V5a2 2 0 0 0-2-2H4"></path><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"></path>',
    'laporan_kp' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>',
    'ebook' => '<path d="M12 5v16"></path><path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z"></path>',
    'catatan' => '<path d="M13.4 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7.4"></path><path d="M2 6h4"></path><path d="M2 10h4"></path><path d="M2 14h4"></path><path d="M2 18h4"></path><path d="M21.378 5.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"></path>',
];
function svg($kelas, $isi) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $kelas . '" aria-hidden="true">' . $isi . '</svg>';
}
$ikon_cari = '<path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle>';
$ikon_x = '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>';
$ikon_panah = '<path d="m6 9 6 6 6-6"></path>';

// URL jelajah dengan q + jenis tertentu (kosong = tidak disertakan)
function urlJelajah($q, $jenis) {
    $p = array_filter(['q' => $q, 'jenis' => $jenis], function ($v) { return $v !== '' && $v !== null; });
    return BASE_URL . 'jelajah.php' . ($p ? '?' . http_build_query($p) : '');
}

// Menu pilihan jenis (dipakai desktop dan HP; $id harus unik)
function menuJenis($id, $q, $jenis) {
    $h = '<div id="' . $id . '" class="jenis-menu" role="menu" data-state="closed" hidden>';
    $h .= '<a role="menuitem" href="' . e(urlJelajah($q, null)) . '"' . ($jenis === null ? ' aria-current="true"' : '') . '>Semua jenis</a>';
    foreach (Dokumen::JENIS as $nilai => $label) {
        $h .= '<a role="menuitem" href="' . e(urlJelajah($q, $nilai)) . '"' . ($jenis === $nilai ? ' aria-current="true"' : '') . '>' . e($label) . '</a>';
    }
    return $h . '</div>';
}

$label_jenis = $jenis !== null ? Dokumen::JENIS[$jenis] : 'Semua jenis';
$judul = 'Jelajah & Cari';
$halaman_aktif = 'jelajah';
include ROOT_PATH . 'partials/head.php';
include ROOT_PATH . 'partials/site_header.php';
?>
<main id="isi" class="situs-main situs-main--max-lg-pb-calc-64px-env-safe-area-inset-bottom"><div class="situs-main__box--gap-4-pb-6-pt-3"><nav aria-label="breadcrumb" data-slot="breadcrumb" class="breadcrumb breadcrumb--mb-2px"><ol data-slot="breadcrumb-list" class="breadcrumb-list"><li data-slot="breadcrumb-item" class="breadcrumb-item"><a data-slot="breadcrumb-link" class="breadcrumb-link" href="<?= BASE_URL ?>index.php">Beranda</a></li><li data-slot="breadcrumb-separator" role="presentation" aria-hidden="true" class="breadcrumb-separator has-size">/</li><li data-slot="breadcrumb-item" class="breadcrumb-item"><span data-slot="breadcrumb-page" role="link" aria-disabled="true" aria-current="page" class="breadcrumb-page">Jelajah</span></li></ol></nav><h1 class="situs-main__title--text-hz-h3">Jelajah &amp; Cari</h1>
<form role="search" method="get" action="<?= BASE_URL ?>jelajah.php" aria-label="Cari dan saring koleksi" class="situs-main__form--bg-card"><div class="situs-main__form-box--relative"><input data-slot="input" class="input input--pr-10-text-hz-body-lg-px-4" aria-label="Cari judul atau penulis" placeholder="Cari judul atau penulis…" type="search" name="q" maxlength="100" value="<?= e($q) ?>"><?= svg('situs-main__form-icon has-size', $ikon_cari) ?></div><div class="situs-main__form-box--gap-4"><?php if ($jenis !== null): ?><input type="hidden" name="jenis" value="<?= e($jenis) ?>"><?php endif; ?><div class="situs-main__form-box--flex-col"><span id="f-jenis-label" class="situs-main__form-text situs-main__form-text--text-hz-overline">Jenis</span><div class="jenis-bungkus"><button type="button" id="f-jenis" aria-haspopup="menu" aria-expanded="false" aria-controls="menu-jenis" data-state="closed" data-slot="dropdown-menu-trigger" aria-labelledby="f-jenis-label f-jenis" class="dropdown-menu-trigger dropdown-menu-trigger--bg-muted"><span class="dropdown-menu-trigger__text--truncate"><?= e($label_jenis) ?></span><?= svg('dropdown-menu-trigger__icon dropdown-menu-trigger__icon--size-4-text-muted-foreground has-size', $ikon_panah) ?></button><?= menuJenis('menu-jenis', $q, $jenis) ?></div></div><button data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" type="submit">Cari</button><a data-slot="button" data-variant="outline" data-size="icon" class="button button--border-input-bg-card-size-9-text-foreground group/button has-size" aria-label="Atur ulang pencarian" href="<?= BASE_URL ?>jelajah.php"><?= svg('button__icon button__icon--size-4 has-size', $ikon_x) ?></a></div></form>
<div class="situs-main__box--gap-1-flex-col-flex"><form role="search" method="get" action="<?= BASE_URL ?>jelajah.php" class="situs-main__box--gap-2-flex"><?php if ($jenis !== null): ?><input type="hidden" name="jenis" value="<?= e($jenis) ?>"><?php endif; ?><div class="situs-main__box--min-w-0-relative-flex-1"><input data-slot="input" class="input input--pr-10-text-hz-body-lg-px-4" aria-label="Cari judul atau penulis" placeholder="Cari judul atau penulis…" type="search" name="q" maxlength="100" value="<?= e($q) ?>"><?= svg('situs-main__icon situs-main__icon--translate-y-1-2-absolute has-size', $ikon_cari) ?></div><div class="jenis-bungkus"><button type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="menu-jenis-hp" data-state="closed" data-slot="dropdown-menu-trigger" class="button button--border-input-bg-card-px-5-text-foreground-2 group/button has-size"><?= svg('button__icon button__icon--size-4 has-size', '<path d="M2 5h20"></path><path d="M6 12h12"></path><path d="M9 19h6"></path>') ?>Jenis<?= $jenis !== null ? ' (1)' : '' ?></button><?= menuJenis('menu-jenis-hp', $q, $jenis) ?></div></form>
<?php if ($jenis !== null): ?><ul aria-label="Filter aktif" class="situs-main__list--mx-4"><li class="situs-main__list-item--shrink-0"><a aria-label="Hapus filter <?= e($label_jenis) ?>" class="situs-main__list-button group" href="<?= e(urlJelajah($q, null)) ?>"><span class="situs-main__list-text--border"><?= e($label_jenis) ?><?= svg('situs-main__list-icon has-size', $ikon_x) ?></span></a></li></ul><?php endif; ?></div>
<div class="situs-main__box--gap-0-5-flex-col-flex" aria-live="polite"><p class="situs-main__paragraph--text-hz-body-semibold"><?php if ($jumlah === 0): ?>Tidak ada dokumen yang cocok<?= $q !== '' ? ' dengan “' . e($q) . '”' : '' ?><?php else: ?><?= $jumlah ?> <?= $q !== '' ? 'hasil untuk “' . e($q) . '”' : 'dokumen' ?><?php endif; ?></p><?php if ($jumlah === 0): ?><p class="situs-main__paragraph--text-hz-hint-text-muted-foreground">Coba kata kunci lain, atau <a href="<?= BASE_URL ?>jelajah.php"><u>atur ulang pencarian</u></a>.</p><?php endif; ?></div>
<?php if ($jumlah > 0): ?><ul class="situs-main__list--gap-x-5">
<?php foreach ($hasil as $d):
    $url = BASE_URL . 'dokumen.php?slug=' . rawurlencode($d['slug']);
    $namaJenis = Dokumen::JENIS[$d['jenis']] ?? $d['jenis'];
    $isi = $IKON[$d['jenis']] ?? $IKON['catatan'];
    $baris = trim(($d['prodi'] ?? '') . ' · ' . ($d['tahun'] ?? ''), ' ·');
?><li class="situs-main__list-item--min-w-0"><article class="situs-main__list-article group"><div class="situs-main__list-article-box--hidden"><div class="situs-main__list-article-box--transition-colors"><div class="situs-main__list-article-box--h-2-5"></div><div class="situs-main__list-article-box--min-h-0"><div><span class="situs-main__list-article-text--bg-primary"><?= svg('situs-main__list-article-icon situs-main__list-article-icon--size-3 has-size', $isi) ?><?= e($namaJenis) ?></span></div><div class="situs-main__list-article-box--mt-3"><h3 class="situs-main__list-article-subheading--line-clamp-5"><a title="<?= e($d['judul']) ?>" data-stretched="true" class="situs-main__list-article-link--outline-none-group-hover-underline" href="<?= e($url) ?>"><?= e($d['judul']) ?></a></h3></div><?= svg('situs-main__list-article-icon situs-main__list-article-icon--mt-auto has-size', $isi) ?></div></div><div class="situs-main__list-article-box--bg-muted"><span class="situs-main__list-article-text--text-hz-xs-bold"><?= e($d['prodi']) ?></span><span class="situs-main__list-article-text--shrink-0"><?= e($d['tahun']) ?></span></div></div><div class="situs-main__list-article-box--flex"><a tabindex="-1" aria-hidden="true" class="situs-main__list-article-link--block-rounded-3px" href="<?= e($url) ?>"><div class="situs-main__list-article-box--bg-card"><div class="situs-main__list-article-box--h-1-5"></div><div class="situs-main__list-article-box--px-2"><span class="situs-main__list-article-text--text-hz-badge"><?= e($namaJenis) ?></span><span class="situs-main__list-article-text--bg-primary-soft has-size"><?= svg('situs-main__list-article-icon situs-main__list-article-icon--size-5 has-size', $isi) ?></span><span class="situs-main__list-article-text--text-hz-hint"><?= e($d['tahun']) ?></span></div></div></a><h3 class="situs-main__list-article-subheading--line-clamp-2"><a title="<?= e($d['judul']) ?>" data-stretched="true" class="situs-main__list-article-link--outline-none-after-absolute" href="<?= e($url) ?>"><?= e($d['judul']) ?></a></h3><p class="situs-main__list-article-paragraph"><?= e($baris) ?></p></div></article></li>
<?php endforeach; ?></ul><ul class="situs-main__list--bg-card">
<?php foreach ($hasil as $d): $url = BASE_URL . 'dokumen.php?slug=' . rawurlencode($d['slug']); ?><li><a class="situs-main__list-link situs-main__list-link--p-3" href="<?= e($url) ?>"><span class="situs-main__list-text--self-start"><?= svg('situs-main__list-icon situs-main__list-icon--size-6 has-size', $IKON[$d['jenis']] ?? $IKON['catatan']) ?></span><span class="situs-main__list-text--flex-col"><span class="situs-main__list-text--text-hz-body-semibold"><?= e($d['judul']) ?></span><span class="situs-main__list-text--text-hz-hint"><?= e(Dokumen::JENIS[$d['jenis']] ?? $d['jenis']) ?> · <?= e($d['prodi']) ?> · <?= e($d['tahun']) ?></span><span class="situs-main__list-text--text-hz-caption"><?= e($d['penulis']) ?></span></span></a></li>
<?php endforeach; ?></ul><?php endif; ?></div></main>
<?php include ROOT_PATH . 'partials/site_footer.php'; ?>
