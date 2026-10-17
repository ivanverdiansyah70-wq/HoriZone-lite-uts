<?php
// Sidebar panel (desktop): <aside> menu menurut role. Di-include oleh panel_header.php.
// Variabel: $halaman_aktif (kunci menu aktif). $menu_override (opsional, hanya uji) menggantikan menu role.
require_once __DIR__ . '/panel_menu.php';
$menu = $menu_override ?? panel_menu($_SESSION['user_role'] ?? '');
$halaman_aktif = $halaman_aktif ?? '';
?>
<aside class="panel-main__sidebar panel-main__sidebar--bg-card"><nav aria-label="Menu panel"><ul class="panel-main__sidebar-nav-list">
<?php foreach ($menu as $m): ?>
<?php if (!isset($m['item'])): $aktif = $m['kunci'] === $halaman_aktif; ?>
<li><a <?= $aktif ? 'aria-current="page" ' : '' ?>class="panel-main__sidebar-nav-list-link panel-main__sidebar-nav-list-link--<?= $aktif ? 'bg-accent' : 'text-hz-body-lg-medium' ?>" href="<?= e($m['href']) ?>"><?= panel_ikon($m['ikon'], 'panel-main__sidebar-nav-list-icon panel-main__sidebar-nav-list-icon--' . ($aktif ? 'text-primary' : 'text-muted-foreground') . ' has-size') ?><?= e($m['label']) ?></a></li>
<?php else: ?>
<li class="panel-main__sidebar-nav-list-item"><p class="panel-main__sidebar-nav-list-paragraph"><?= e($m['judul']) ?></p><ul class="panel-main__sidebar-nav-list-list">
<?php foreach ($m['item'] as $i): $aktif = $i['kunci'] === $halaman_aktif; ?>
<li><a <?= $aktif ? 'aria-current="page" ' : '' ?>class="panel-main__sidebar-nav-list-list-link panel-main__sidebar-nav-list-list-link--<?= $aktif ? 'bg-accent' : 'text-hz-body-lg-medium' ?>" href="<?= e($i['href']) ?>"><?= panel_ikon($i['ikon'], 'panel-main__sidebar-nav-list-list-icon panel-main__sidebar-nav-list-list-icon--' . ($aktif ? 'text-primary' : 'text-muted-foreground') . ' has-size') ?><?= e($i['label']) ?></a></li>
<?php endforeach; ?>
</ul></li>
<?php endif; ?>
<?php endforeach; ?>
</ul></nav></aside>
