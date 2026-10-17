<?php
// Dasbor panel (admin + pustakawan): ringkasan angka dari DB. Markup = 'panel-dasbor' (K-05: bagian tanpa data DB tidak dibuat).
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'pustakawan']);
require_once ROOT_PATH . 'partials/panel_menu.php'; // panel_ikon()

$db = (new Database())->getConnection();
$dokumen = new Dokumen($db);
$user = new User($db);
$pengumuman = new Pengumuman($db);

// ---- Data ----
$semuaDokumen = $dokumen->cari('', null, null, false)->fetchAll(PDO::FETCH_ASSOC);
$stat = $dokumen->statistik();
$totalDibaca = $stat['dibaca'];
$totalUnduhan = $stat['unduhan'];
$jmlDokumen = $dokumen->hitungPanel();
$jmlMenunggu = $dokumen->hitung(null, 'menunggu');
$jmlDiturunkan = $dokumen->hitung(null, 'diturunkan');
$jmlUserAktif = $user->hitung('aktif');
$jmlUserNonaktif = $user->hitung('nonaktif');
$jmlPengumuman = $pengumuman->hitung();
// Semua pengumuman tayang: tayang() menyaring per target, jadi gabungkan semua target tanpa duplikat
$daftarTayang = [];
foreach (['semua', 'tamu', 'dosen', 'mahasiswa'] as $target) {
    foreach ($pengumuman->tayang($target, 100)->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $daftarTayang[$p['id']] = $p;
    }
}
ksort($daftarTayang);
$terbaru = $dokumen->readPanel(0, 4)->fetchAll(PDO::FETCH_ASSOC);
$populer = $dokumen->populer(4)->fetchAll(PDO::FETCH_ASSOC);
$tanpaPdf = count(array_filter($semuaDokumen, fn($d) => empty($d['file_pdf'])));
$tanpaCover = count(array_filter($semuaDokumen, fn($d) => empty($d['cover'])));

$nama = $_SESSION['user_nama'] ?? '';
$nama_depan = explode(' ', trim($nama))[0];
$adalahAdmin = ($_SESSION['user_role'] === 'admin');
$b = BASE_URL . 'panel/';

function angka($n) { return number_format((int) $n, 0, ',', '.'); }

// <svg> ikon lucide dari isi path
function dasbor_svg($isi, $kelas) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $kelas . ' has-size" aria-hidden="true">' . $isi . '</svg>';
}
$IK_BUKU = '<path d="M12 5v16"></path><path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z"></path>';
$IK_UNDUH = '<path d="M12 15V3"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="m7 10 5 5 5-5"></path>';
$IK_CEK = '<path d="M13 5h8"></path><path d="M13 12h8"></path><path d="M13 19h8"></path><path d="m3 17 2 2 4-4"></path><path d="m3 7 2 2 4-4"></path>';
$IK_PERINGATAN = '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>';
$IK_PANAH = '<path d="m9 18 6-6-6-6"></path>';

// Kartu angka: [ikon, angka, label, keterangan]
$kartu = [
    [panel_ikon('data-koleksi', 'panel-main__icon'), angka($jmlDokumen), 'Total koleksi',
        angka($dokumen->hitung('skripsi')) . ' skripsi · ' . angka($dokumen->hitung('ebook')) . ' ebook'],
    [panel_ikon('data-pengguna', 'panel-main__icon'), angka($jmlUserAktif), 'Pengguna aktif', angka($jmlUserNonaktif) . ' nonaktif'],
    [dasbor_svg($IK_BUKU, 'panel-main__icon'), angka($totalDibaca), 'Total dibaca', 'semua dokumen'],
    [dasbor_svg($IK_UNDUH, 'panel-main__icon'), angka($totalUnduhan), 'Total unduhan', 'semua dokumen'],
    [dasbor_svg($IK_CEK, 'panel-main__icon'), angka($jmlMenunggu), 'Verifikasi menunggu', angka($jmlDiturunkan) . ' diturunkan'],
    [panel_ikon('konten', 'panel-main__icon'), angka(count($daftarTayang)), 'Pengumuman tayang', 'dari ' . angka($jmlPengumuman) . ' pengumuman'],
];

// Perlu tindakan: [ikon, kelas latar ikon, teks, jumlah, varian badge, tautan]
$tindakan = [
    [dasbor_svg($IK_CEK, 'panel-main__section-list-icon'), 'warning-soft', angka($jmlMenunggu) . ' dokumen menunggu verifikasi', $jmlMenunggu, 'warning', $b . 'koleksi.php'],
    [dasbor_svg($IK_PERINGATAN, 'panel-main__section-list-icon'), 'destructive-soft', angka($jmlDiturunkan) . ' dokumen diturunkan', $jmlDiturunkan, 'destructive', $b . 'koleksi.php'],
];
if ($adalahAdmin) {
    $tindakan[] = [panel_ikon('data-pengguna', 'panel-main__section-list-icon'), 'muted', angka($jmlUserNonaktif) . ' pengguna nonaktif', $jmlUserNonaktif, 'outline', $b . 'pengguna.php'];
}
$tindakan[] = [dasbor_svg($IK_BUKU, 'panel-main__section-list-icon'), 'muted', angka($tanpaPdf) . ' dokumen tanpa file PDF', $tanpaPdf, 'outline', $b . 'koleksi.php'];
$tindakan[] = [dasbor_svg($IK_UNDUH, 'panel-main__section-list-icon'), 'muted', angka($tanpaCover) . ' dokumen tanpa cover', $tanpaCover, 'outline', $b . 'koleksi.php'];
$tindakan[] = [panel_ikon('konten', 'panel-main__section-list-icon'), 'muted', angka(count($daftarTayang)) . ' pengumuman sedang tayang', count($daftarTayang), 'outline', $b . 'pengumuman.php'];

$badgeKelas = [
    'warning' => 'badge badge--bg-warning-soft-text-warning-border-transparent-px-2 group/badge has-size',
    'destructive' => 'badge badge--bg-destructive-soft-text-destructive-border-transparent-px-2 group/badge has-size',
    'outline' => 'badge badge--border-border-px-2-text-foreground-h-25px group/badge has-size',
];

$judul = 'Dasbor';
$halaman_aktif = 'dasbor';
include ROOT_PATH . 'partials/head.php';
include ROOT_PATH . 'partials/panel_header.php';
?>
<div class="panel-main__box--flex-col-gap-4-flex-lg-gap-22px"><header class="panel-main__header"><h1 class="panel-main__header-title">Dasbor</h1><div class="panel-main__header-box panel-main__header-box--flex-col-gap-3"><p class="panel-main__header-paragraph panel-main__header-paragraph--max-w-560px">Halo, <?= e($nama_depan) ?>. Ringkasan perpustakaan digital kampus.</p><div class="panel-main__header-box panel-main__header-box--gap-2-max-lg-flex-1"><a data-slot="button" data-variant="outline" data-size="default" class="button button--border-input-bg-card-px-5-text-foreground-2 group/button has-size" href="<?= BASE_URL ?>index.php"><span>Lihat situs</span></a></div></div></header>
<div class="panel-main__box--grid-cols-2-gap-3-grid-lg-grid-cols-3">
<?php foreach ($kartu as [$ikon, $angkaKartu, $label, $ket]): ?>
<div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span aria-hidden="true" class="panel-main__text--absolute has-size"><?= $ikon ?></span><span class="panel-main__text--text-hz-stat"><?= e($angkaKartu) ?></span><span class="panel-main__text--text-hz-xs"><?= e($label) ?></span><span class="panel-main__text--mt-1"><?= e($ket) ?></span></div>
<?php endforeach; ?>
</div>
<section aria-labelledby="perlu-tindakan" class="panel-main__section panel-main__section--overflow-hidden"><h2 id="perlu-tindakan" class="panel-main__section-heading--border-b">Perlu tindakan</h2><ul class="panel-main__section-list--lg-grid">
<?php foreach ($tindakan as [$ikon, $latar, $teks, $jml, $varian, $href]): ?>
<li class="panel-main__section-list-item--border-b"><a class="panel-main__section-list-link" href="<?= e($href) ?>"><span class="panel-main__section-list-text--bg-<?= $latar ?> has-size"><?= $ikon ?></span><span class="panel-main__section-list-text--flex-1"><?= e($teks) ?></span><span data-slot="badge" data-variant="<?= $varian ?>" class="<?= $badgeKelas[$varian] ?>"><?= angka($jml) ?></span><?= dasbor_svg($IK_PANAH, 'panel-main__section-list-icon panel-main__section-list-icon--text-muted-foreground') ?></a></li>
<?php endforeach; ?>
</ul></section>
<div class="panel-main__box--grid-gap-4-lg-grid-cols-2">
<section aria-labelledby="dokumen-terbaru" class="panel-main__section panel-main__section--gap-3-min-w-0-flex-flex-col"><div class="panel-main__section-box--gap-x-4-gap-y-2"><div><h2 id="dokumen-terbaru" class="panel-main__section-heading--text-hz-title">Dokumen terbaru</h2><p class="panel-main__section-paragraph--text-hz-hint-text-muted-foreground">Semua status</p></div></div>
<ul>
<?php foreach ($terbaru as $d): ?>
<li><a class="panel-main__section-list-link" href="<?= e($b) ?>koleksi.php"><span class="panel-main__section-list-text--flex-1 panel-main__table-paragraph--truncate"><?= e($d['judul']) ?></span><span data-slot="badge" data-variant="outline" class="<?= $badgeKelas['outline'] ?>"><?= e(Dokumen::STATUS[$d['status']] ?? $d['status']) ?></span></a></li>
<?php endforeach; ?>
<?php if (!$terbaru): ?><li><p class="panel-main__section-paragraph--text-hz-hint-text-muted-foreground">Belum ada dokumen.</p></li><?php endif; ?>
</ul></section>
<section aria-labelledby="dokumen-populer" class="panel-main__section panel-main__section--gap-3-min-w-0-flex-flex-col-2"><div class="panel-main__section-box--gap-x-4-gap-y-2"><div><h2 id="dokumen-populer" class="panel-main__section-heading--text-hz-title">Paling banyak dibaca</h2><p class="panel-main__section-paragraph--text-hz-hint-text-muted-foreground">Dokumen terbit</p></div></div>
<ul>
<?php foreach ($populer as $d): ?>
<li><a class="panel-main__section-list-link" href="<?= e($b) ?>koleksi.php"><span class="panel-main__section-list-text--flex-1 panel-main__table-paragraph--truncate"><?= e($d['judul']) ?></span><span data-slot="badge" data-variant="outline" class="<?= $badgeKelas['outline'] ?>"><?= angka($d['dibaca']) ?></span></a></li>
<?php endforeach; ?>
</ul></section>
<section aria-labelledby="pengumuman-tayang" class="panel-main__section panel-main__section--gap-3-min-w-0-flex-flex-col-2"><div class="panel-main__section-box--gap-x-4-gap-y-2"><div><h2 id="pengumuman-tayang" class="panel-main__section-heading--text-hz-title">Pengumuman tayang</h2><p class="panel-main__section-paragraph--text-hz-hint-text-muted-foreground">Sedang tampil di beranda</p></div></div>
<ul>
<?php foreach (array_slice($daftarTayang, 0, 4) as $p): ?>
<li><a class="panel-main__section-list-link" href="<?= e($b) ?>pengumuman.php"><span class="panel-main__section-list-text--flex-1 panel-main__table-paragraph--truncate"><?= e($p['judul']) ?></span></a></li>
<?php endforeach; ?>
<?php if (!$daftarTayang): ?><li><p class="panel-main__section-paragraph--text-hz-hint-text-muted-foreground">Tidak ada pengumuman tayang.</p></li><?php endif; ?>
</ul></section>
</div></div>
<?php include ROOT_PATH . 'partials/panel_footer.php'; ?>
