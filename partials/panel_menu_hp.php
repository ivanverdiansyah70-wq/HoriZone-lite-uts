<?php
// Menu panel HP: sheet (overlay + panel geser) dari kiri. Dibuka tombol [data-buka="menu-hp"] di header HP.
// Variabel: $halaman_aktif, $menu_override (uji), $menu_hp_terbuka (true = dirender terbuka dari server, hanya uji).
require_once __DIR__ . '/panel_menu.php';
$menu = $menu_override ?? panel_menu($_SESSION['user_role'] ?? '');
$halaman_aktif = $halaman_aktif ?? '';
$buka = !empty($menu_hp_terbuka);
$s = $buka ? 'open' : 'closed';
$h = $buka ? '' : ' hidden';
?>
<div data-state="<?= $s ?>" data-slot="sheet-overlay" data-lapisan="menu-hp" class="sheet-overlay"<?= $h ?>></div>
<div role="dialog" id="menu-hp" data-state="<?= $s ?>" data-slot="sheet-content" data-lapisan="menu-hp" data-side="left" class="sheet-content sheet-content--lg-hidden" tabindex="-1" aria-labelledby="menu-hp-judul"<?= $h ?><?= $buka ? " autofocus" : "" ?>>
<div data-slot="sheet-header" class="sheet-header sheet-header--flex-row"><h2 id="menu-hp-judul" data-slot="sheet-title" class="sheet-title sheet-title--font-medium">Menu panel</h2><p data-slot="sheet-description" class="sheet-description sheet-description--sr-only">Navigasi halaman pengelola</p><div class="sheet-header__box"><a aria-label="HoriZone, ke Beranda" class="sheet-header__link" href="<?= BASE_URL ?>index.php"><span aria-hidden="true" class="sheet-header__text--bg-secondary has-size">Z</span><span class="sheet-header__text--text-hz-wordmark">HORIZONE</span></a></div></div>
<div class="sheet-content__box sheet-content__box--pt-2"><nav aria-label="Menu panel"><ul class="sheet-content__nav-list">
<?php foreach ($menu as $m): ?>
<?php if (!isset($m['item'])): $aktif = $m['kunci'] === $halaman_aktif; ?>
<li><a <?= $aktif ? 'aria-current="page" ' : '' ?>class="sheet-content__nav-list-link<?= $aktif ? ' sheet-content__nav-list-list-link--bg-accent' : '' ?>" href="<?= e($m['href']) ?>"><?= panel_ikon($m['ikon'], 'sheet-content__nav-list-icon has-size') ?><?= e($m['label']) ?></a></li>
<?php else: ?>
<li class="sheet-content__nav-list-item"><p class="sheet-content__nav-list-paragraph"><?= e($m['judul']) ?></p><ul class="sheet-content__nav-list-list">
<?php foreach ($m['item'] as $i): $aktif = $i['kunci'] === $halaman_aktif; ?>
<li><a <?= $aktif ? 'aria-current="page" ' : '' ?>class="sheet-content__nav-list-list-link sheet-content__nav-list-list-link--<?= $aktif ? 'bg-accent' : 'text-hz-body-lg-medium' ?>" href="<?= e($i['href']) ?>"><?= panel_ikon($i['ikon'], 'sheet-content__nav-list-list-icon sheet-content__nav-list-list-icon--' . ($aktif ? 'text-primary' : 'text-muted-foreground') . ' has-size') ?><?= e($i['label']) ?></a></li>
<?php endforeach; ?>
</ul></li>
<?php endif; ?>
<?php endforeach; ?>
<li><button type="button" data-tema data-tema-teks data-tema-ikon="sheet-content__nav-list-icon has-size" class="sheet-content__nav-list-link"></button></li>
<li><form method="post" action="<?= BASE_URL ?>logout.php"><?= csrf_field() ?><button type="submit" class="sheet-content__nav-list-link"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="sheet-content__nav-list-icon has-size"><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path></svg>Keluar</button></form></li>
</ul></nav></div>
<button data-slot="sheet-close" data-variant="ghost" data-size="icon-sm" data-tutup class="sheet-close group/button has-size" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg><span class="sheet-close__text">Close</span></button>
</div>
