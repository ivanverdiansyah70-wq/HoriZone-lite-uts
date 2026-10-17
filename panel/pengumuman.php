<?php
// CRUD pengumuman (admin + pustakawan). Markup = 'panel-konten' (daftar) + 'panel-pengumuman-baru' (sheet form).
// Tab prototipe selain Pengumuman (Teks situs, Catatan halaman, Halaman statis) di luar lingkup dan tidak dibuat (K-05).
// Form tambah/ubah dirender dari server: ?aksi=tambah | ?aksi=ubah&id=N; tutup = tautan kembali ke daftar.
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'pustakawan']);

$db = (new Database())->getConnection();
$pengumuman = new Pengumuman($db);
$daftarUrl = BASE_URL . 'panel/pengumuman.php';

const TINGKAT = ['info' => 'Info', 'peringatan' => 'Peringatan'];
const TARGET = ['semua' => 'Semua', 'tamu' => 'Tamu', 'dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'];
const STATUS_PG = ['tayang' => 'Tayang', 'selesai' => 'Selesai', 'terjadwal' => 'Terjadwal'];

// Tanggal Y-m-d valid (ketat) atau null
function tanggalValid($s) {
    $d = DateTime::createFromFormat('Y-m-d', (string) $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

function flash($tipe, $teks) {
    $_SESSION['flash'] = ['tipe' => $tipe, 'teks' => $teks];
}

// ---- POST: simpan / hapus (CSRF, lalu PRG) ----
$galat = '';
$isian = null; // nilai form yang dikirim ulang bila validasi gagal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksiPost = $_POST['action'] ?? '';
    $id = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int) $_POST['id'] : 0;

    if ($aksiPost === 'delete') {
        $pengumuman->id = $id;
        $ok = $pengumuman->delete();
        flash($ok ? 'sukses' : 'galat', $ok ? 'Pengumuman dihapus.' : 'Gagal menghapus pengumuman.');
        header('Location: ' . $daftarUrl);
        exit();
    }

    if ($aksiPost === 'create' || $aksiPost === 'update') {
        $isian = [
            'id' => $id,
            'judul' => trim((string) ($_POST['judul'] ?? '')),
            'isi' => trim((string) ($_POST['isi'] ?? '')),
            'tingkat' => (string) ($_POST['tingkat'] ?? ''),
            'target' => (string) ($_POST['target'] ?? ''),
            'tampil_mulai' => (string) ($_POST['tampil_mulai'] ?? ''),
            'tampil_selesai' => (string) ($_POST['tampil_selesai'] ?? ''),
            'status' => (string) ($_POST['status'] ?? ''),
        ];
        if ($isian['judul'] === '' || $isian['isi'] === '') {
            $galat = 'Judul dan isi wajib diisi.';
        } elseif (mb_strlen($isian['judul']) > 255) {
            $galat = 'Judul maksimal 255 karakter.';
        } elseif (!isset(TINGKAT[$isian['tingkat']]) || !isset(TARGET[$isian['target']]) || !isset(STATUS_PG[$isian['status']])) {
            $galat = 'Tingkat, target, atau status tidak valid.';
        } elseif (!tanggalValid($isian['tampil_mulai']) || !tanggalValid($isian['tampil_selesai'])) {
            $galat = 'Tanggal mulai dan berakhir harus tanggal yang valid.';
        } elseif ($isian['tampil_selesai'] < $isian['tampil_mulai']) {
            $galat = 'Tanggal berakhir tidak boleh sebelum tanggal mulai.';
        }

        if ($galat === '') {
            foreach (['judul', 'isi', 'tingkat', 'target', 'tampil_mulai', 'tampil_selesai', 'status'] as $k) {
                $pengumuman->$k = $isian[$k];
            }
            if ($aksiPost === 'create') {
                $pengumuman->dibuat_oleh = $_SESSION['user_id'];
                $ok = $pengumuman->create();
                flash($ok ? 'sukses' : 'galat', $ok ? 'Pengumuman ditambahkan.' : 'Gagal menambahkan pengumuman.');
            } else {
                $pengumuman->id = $id;
                $ok = $pengumuman->update();
                flash($ok ? 'sukses' : 'galat', $ok ? 'Pengumuman diperbarui.' : 'Gagal memperbarui pengumuman.');
            }
            header('Location: ' . $daftarUrl);
            exit();
        }
    }
}

// ---- GET (atau POST gagal validasi): tentukan form terbuka ----
$aksi = $isian ? ($isian['id'] ? 'ubah' : 'tambah') : ($_GET['aksi'] ?? '');
if ($aksi === 'ubah' && !$isian) {
    $pengumuman->id = (is_string($_GET['id'] ?? null) && ctype_digit($_GET['id'])) ? (int) $_GET['id'] : 0;
    if (!$pengumuman->readOne()) {
        flash('galat', 'Pengumuman tidak ditemukan.');
        header('Location: ' . $daftarUrl);
        exit();
    }
    $isian = [
        'id' => (int) $pengumuman->id, 'judul' => $pengumuman->judul, 'isi' => (string) $pengumuman->isi,
        'tingkat' => $pengumuman->tingkat, 'target' => $pengumuman->target,
        'tampil_mulai' => $pengumuman->tampil_mulai, 'tampil_selesai' => $pengumuman->tampil_selesai,
        'status' => $pengumuman->status,
    ];
} elseif ($aksi === 'tambah' && !$isian) {
    $isian = ['id' => 0, 'judul' => '', 'isi' => '', 'tingkat' => 'info', 'target' => 'semua',
        'tampil_mulai' => date('Y-m-d'), 'tampil_selesai' => date('Y-m-d', strtotime('+14 days')), 'status' => 'tayang'];
} elseif ($aksi !== 'ubah' && $aksi !== 'tambah') {
    $aksi = '';
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$daftar = $pengumuman->readAll()->fetchAll(PDO::FETCH_ASSOC);

// ---- Potongan markup (disalin dari semantik panel-konten) ----
$IKON_INFO = '<path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"></path><path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14"></path><path d="M8 6v8"></path>';
$IKON_PERINGATAN = '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>';
$SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="%s" aria-hidden="true">%s</svg>';

function badgeTingkat($p) {
    global $IKON_INFO, $IKON_PERINGATAN, $SVG;
    $peringatan = $p['tingkat'] === 'peringatan';
    $kelas = $peringatan ? 'badge--bg-warning-soft-text-warning-border-transparent-px-2' : 'badge--bg-primary-soft-text-primary-border-transparent-px-2';
    return '<span data-slot="badge" data-variant="' . ($peringatan ? 'warning' : 'default') . '" class="badge ' . $kelas . ' group/badge has-size">'
        . sprintf($SVG, 'badge__icon has-size', $peringatan ? $IKON_PERINGATAN : $IKON_INFO) . e(TINGKAT[$p['tingkat']]) . '</span>';
}

// $tempat: 'table' atau 'list' (prefiks kelas berbeda)
function badgeStatus($p, $tempat) {
    $warna = ['tayang' => 'bg-success-soft', 'selesai' => 'bg-muted', 'terjadwal' => 'bg-warning-soft'][$p['status']];
    $k = $tempat === 'table' ? 'tabs-content__table-text' : 'tabs-content__list-text tabs-content__list-text';
    return '<span class="' . $k . '--' . $warna . '"><span aria-hidden="true" class="' . $k . '--bg-current has-size"></span>' . e(STATUS_PG[$p['status']]) . '</span>';
}

// Tombol "..." + menu aksi (Ubah / Hapus). Id unik per pengumuman dan per tempat (tabel/daftar).
function menuAksi($p, $tempat) {
    global $SVG, $daftarUrl;
    $mid = 'aksi-' . $tempat . '-' . (int) $p['id'];
    $titik = '<circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle>';
    return '<div class="pengguna-menu-bungkus"><button data-slot="dropdown-menu-trigger" data-variant="ghost" data-size="icon-sm" class="dropdown-menu-trigger dropdown-menu-trigger--border-transparent group/button has-size" aria-label="Aksi untuk ' . e($p['judul']) . '" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="' . $mid . '" data-state="closed">'
        . sprintf($SVG, 'dropdown-menu-trigger__icon dropdown-menu-trigger__icon--size-4-text-muted-foreground has-size', $titik) . '</button>'
        . '<div id="' . $mid . '" class="pengguna-menu" role="menu" data-state="closed" hidden>'
        . '<a role="menuitem" href="' . e($daftarUrl) . '?aksi=ubah&amp;id=' . (int) $p['id'] . '">Ubah</a>'
        . '<form method="post" action="' . e($daftarUrl) . '" data-confirm="Hapus pengumuman ini?">' . csrf_field()
        . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int) $p['id'] . '"><button role="menuitem" type="submit">Hapus</button></form>'
        . '</div></div>';
}

function opsi($daftar, $dipilih) {
    $h = '';
    foreach ($daftar as $nilai => $label) {
        $h .= '<option value="' . e($nilai) . '"' . ((string) $nilai === $dipilih ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $h;
}

// Form tambah/ubah. $pre = 'sheet-content' (desktop) | 'drawer-content' (HP); $sfx = akhiran id unik (d/h).
function formPengumuman($pre, $sfx, $aksi, $isian, $galat) {
    global $daftarUrl;
    $f = $pre . '__form-box--flex';
    $kSel = 'select-trigger select-trigger--px-3-5-rounded-md-text-hz-body-w-full has-size';
    $h = '<form id="form-pengumuman-' . $sfx . '" method="post" action="' . e($daftarUrl) . '" class="' . $pre . '__form">' . csrf_field()
        . '<input type="hidden" name="action" value="' . ($aksi === 'ubah' ? 'update' : 'create') . '"><input type="hidden" name="id" value="' . (int) $isian['id'] . '">';
    if ($galat) {
        $h .= '<div data-slot="alert" role="alert" class="alert alert--bg-warning-soft-content-start group/alert has-size"><div data-slot="alert-title" class="alert-title">' . e($galat) . '</div></div>';
    }
    $h .= '<div class="' . $f . '"><label data-slot="label" class="label label--text-hz-label" for="pg-judul-' . $sfx . '">Judul</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="pg-judul-' . $sfx . '" name="judul" maxlength="255" required value="' . e($isian['judul']) . '"></div>'
        . '<div class="' . $f . '"><label data-slot="label" class="label label--text-hz-label" for="pg-isi-' . $sfx . '">Isi</label><textarea data-slot="textarea" class="textarea textarea--h-20 has-size" id="pg-isi-' . $sfx . '" name="isi" required aria-describedby="pg-isi-desc-' . $sfx . '">' . e($isian['isi']) . '</textarea><p id="pg-isi-desc-' . $sfx . '" class="' . $pre . '__form-paragraph--mt-px">Satu atau dua kalimat.</p></div>'
        . '<div class="' . $f . '"><label data-slot="label" class="label label--text-hz-label" for="pg-tingkat-' . $sfx . '">Tingkat</label><div class="select-bungkus"><select class="' . $kSel . '" id="pg-tingkat-' . $sfx . '" name="tingkat">' . opsi(TINGKAT, $isian['tingkat']) . '</select>' . chevron_select() . '</div></div>'
        . '<div class="' . $f . '"><label data-slot="label" class="label label--text-hz-label" for="pg-target-' . $sfx . '">Target</label><div class="select-bungkus"><select class="' . $kSel . '" id="pg-target-' . $sfx . '" name="target">' . opsi(TARGET, $isian['target']) . '</select>' . chevron_select() . '</div></div>'
        . '<div class="' . $pre . '__form-box--gap-3"><div class="' . $f . '"><label data-slot="label" class="label label--text-hz-label" for="pg-mulai-' . $sfx . '">Mulai tampil</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="pg-mulai-' . $sfx . '" name="tampil_mulai" type="date" required value="' . e($isian['tampil_mulai']) . '"></div>'
        . '<div class="' . $f . '"><label data-slot="label" class="label label--text-hz-label" for="pg-akhir-' . $sfx . '">Berakhir</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="pg-akhir-' . $sfx . '" name="tampil_selesai" type="date" required value="' . e($isian['tampil_selesai']) . '"></div></div>'
        . '<div class="' . $f . '"><label data-slot="label" class="label label--text-hz-label" for="pg-status-' . $sfx . '">Status</label><div class="select-bungkus"><select class="' . $kSel . '" id="pg-status-' . $sfx . '" name="status">' . opsi(STATUS_PG, $isian['status']) . '</select>' . chevron_select() . '</div></div>'
        . '</form>';
    return $h;
}

$judul = 'Pengumuman';
$crumb = ['Situs', 'Konten'];
$halaman_aktif = 'pengumuman';
include ROOT_PATH . 'partials/head.php';
include ROOT_PATH . 'partials/panel_header.php';
?>
<div class="panel-main__box--flex-col-gap-4-flex-lg-gap-22px"><header class="panel-main__header"><h1 class="panel-main__header-title">Pengumuman</h1><div class="panel-main__header-box panel-main__header-box--flex-col-gap-3"><p class="panel-main__header-paragraph panel-main__header-paragraph--max-w-560px">Banner yang tampil di atas Beranda sesuai jadwal dan target.</p><div class="panel-main__header-box panel-main__header-box--gap-2-max-lg-flex-1"><a data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" href="<?= e($daftarUrl) ?>?aksi=tambah"><?= sprintf($SVG, 'button__icon button__icon--size-4 has-size', '<path d="M5 12h14"></path><path d="M12 5v14"></path>') ?>Buat pengumuman</a></div></div></header>
<?php if ($flash): ?>
<div data-slot="alert" role="alert" class="alert alert--bg-<?= $flash['tipe'] === 'sukses' ? 'primary' : 'warning' ?>-soft-content-start group/alert has-size"><div data-slot="alert-title" class="alert-title"><?= e($flash['teks']) ?></div></div>
<?php endif; ?>
<div dir="ltr" data-orientation="horizontal" data-slot="tabs" class="tabs tabs--gap-4 group/tabs"><div role="tablist" aria-orientation="horizontal" data-slot="tabs-list" class="tabs-list" aria-label="Jenis konten" tabindex="0" data-orientation="horizontal"><button type="button" role="tab" aria-selected="true" aria-controls="tab-pengumuman" data-state="active" id="tab-pengumuman-pemicu" data-slot="tabs-trigger" class="tabs-trigger has-size" tabindex="-1" data-orientation="horizontal">Pengumuman</button></div><div data-state="active" data-orientation="horizontal" role="tabpanel" aria-labelledby="tab-pengumuman-pemicu" id="tab-pengumuman" tabindex="0" data-slot="tabs-content" class="tabs-content tabs-content--flex"><div class="tabs-content__box--bg-card"><table class="tabs-content__table"><colgroup><col><col class="col-w-118"><col class="col-w-96"><col class="col-w-170"><col class="col-w-112"><col class="col-w-52"></colgroup><thead><tr class="tabs-content__table-row tabs-content__table-row--text-hz-overline"><th class="tabs-content__table-head-cell--pl-5">Pengumuman</th><th>Tingkat</th><th>Target</th><th>Jadwal tampil</th><th>Status</th><th class="tabs-content__table-head-cell--pr-5"><span class="tabs-content__table-text--sr-only">Aksi</span></th></tr></thead><tbody>
<?php foreach ($daftar as $p): ?>
<tr class="tabs-content__table-row tabs-content__table-row--align-middle"><td class="tabs-content__table-cell--pl-5"><?= e($p['judul']) ?></td><td><?= badgeTingkat($p) ?></td><td class="tabs-content__table-cell--text-hz-sm"><?= e(TARGET[$p['target']]) ?></td><td class="tabs-content__table-cell--text-hz-sm"><?= e(formatRentang($p['tampil_mulai'], $p['tampil_selesai'])) ?></td><td><?= badgeStatus($p, 'table') ?></td><td class="tabs-content__table-cell--pr-3-5"><?= menuAksi($p, 't') ?></td></tr>
<?php endforeach; ?>
<?php if (!$daftar): ?><tr class="tabs-content__table-row"><td class="tabs-content__table-cell--pl-5" colspan="6">Belum ada pengumuman.</td></tr><?php endif; ?>
</tbody></table><div class="tabs-content__box--border-t"><p class="tabs-content__paragraph"><?= count($daftar) ?> pengumuman · banner tampil di atas Beranda sesuai jadwal</p></div></div><ul class="tabs-content__list">
<?php foreach ($daftar as $p): ?>
<li class="tabs-content__list-item"><div class="tabs-content__list-box--flex-1"><div class="tabs-content__list-box--flex"><?= badgeTingkat($p) ?><?= badgeStatus($p, 'list') ?></div><p class="tabs-content__list-paragraph--mt-1-5"><?= e($p['judul']) ?></p><p class="tabs-content__list-paragraph--text-hz-xs">Untuk <?= e(strtolower(TARGET[$p['target']])) ?> · <?= e(formatRentang($p['tampil_mulai'], $p['tampil_selesai'])) ?></p></div><?= menuAksi($p, 'l') ?></li>
<?php endforeach; ?>
</ul></div></div></div>
<?php if ($aksi !== ''): // dialog position:fixed: cukup di akhir konten, tanpa dipindah ?>
<?php
// Prototipe: sheet kanan di desktop, drawer bawah di HP. Keduanya dirender; CSS di atas menyembunyikan salah satu.
$judulDlg = $aksi === 'ubah' ? 'Ubah pengumuman' : 'Buat pengumuman';
$labelKirim = $aksi === 'ubah' ? 'Simpan' : 'Tambah';
$ketDlg = 'Banner tampil di atas Beranda sesuai jadwal dan target.';
$silang = sprintf($SVG, '', '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>');
?>
<div class="dlg-desktop" data-tutup-href="<?= e($daftarUrl) ?>">
<a data-slot="sheet-overlay" class="sheet-overlay" data-state="open" aria-label="Tutup" href="<?= e($daftarUrl) ?>"></a><div role="dialog" data-state="open" data-slot="sheet-content" data-side="right" class="sheet-content sheet-content--sm-max-w-440px" tabindex="-1" aria-labelledby="dlg-d-judul" autofocus><div data-slot="sheet-header" class="sheet-header sheet-header--flex-col"><h2 id="dlg-d-judul" data-slot="sheet-title" class="sheet-title sheet-title--text-hz-h4"><?= $judulDlg ?></h2><p data-slot="sheet-description" class="sheet-description sheet-description--text-hz-sm"><?= $ketDlg ?></p></div><div class="sheet-content__box sheet-content__box--flex"><?= formPengumuman('sheet-content', 'd', $aksi, $isian, $galat) ?></div><div data-slot="sheet-footer" class="sheet-footer"><a data-slot="button" data-variant="outline" data-size="default" class="button button--border-input-bg-card-px-5-text-foreground-2 group/button has-size" href="<?= e($daftarUrl) ?>">Batal</a><button data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" type="submit" form="form-pengumuman-d"><?= $labelKirim ?></button></div><a data-slot="sheet-close" data-variant="ghost" data-size="icon-sm" class="sheet-close group/button has-size" href="<?= e($daftarUrl) ?>"><?= $silang ?><span class="sheet-close__text">Tutup</span></a></div>
</div>
<div class="dlg-hp" data-tutup-href="<?= e($daftarUrl) ?>">
<a data-slot="drawer-overlay" class="drawer-overlay" data-state="open" aria-label="Tutup" href="<?= e($daftarUrl) ?>"></a><div role="dialog" data-state="open" data-vaul-drawer-direction="bottom" data-vaul-drawer="" data-slot="drawer-content" class="drawer-content group/drawer-content" tabindex="-1" aria-labelledby="dlg-h-judul" autofocus><div class="drawer-content__box--bg-muted"></div><div data-slot="drawer-header" class="drawer-header"><h2 id="dlg-h-judul" data-slot="drawer-title" class="drawer-title"><?= $judulDlg ?></h2><p data-slot="drawer-description" class="drawer-description"><?= $ketDlg ?></p></div><div class="drawer-content__box--flex"><?= formPengumuman('drawer-content', 'h', $aksi, $isian, $galat) ?></div><div data-slot="drawer-footer" class="drawer-footer"><a data-slot="button" data-variant="outline" data-size="lg" class="button button--px-6-text-hz-body-lg-medium-border-input-bg-card group/button has-size" href="<?= e($daftarUrl) ?>">Batal</a><button data-slot="button" data-variant="default" data-size="lg" class="button button--px-6-text-hz-body-lg-medium-bg-primary-text-primary-foreground group/button has-size" type="submit" form="form-pengumuman-h"><?= $labelKirim ?></button></div></div>
</div>
<?php endif; ?>
<?php include ROOT_PATH . 'partials/panel_footer.php';
