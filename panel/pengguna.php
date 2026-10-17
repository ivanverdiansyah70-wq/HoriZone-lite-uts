<?php
// Panel pengguna (admin saja): daftar, tambah, ubah, hapus. Markup = 'panel-pengguna' dan 'panel-pengguna-detail' (K-05: bagian tanpa data DB tidak dibuat).
//   pengguna.php               daftar (?q= cari, ?status=aktif|nonaktif|berbatas, ?role=)
//   pengguna.php?aksi=tambah   form tambah
//   pengguna.php?slug=...      detail + form ubah
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$db = (new Database())->getConnection();
$saya = (int) $_SESSION['user_id'];
$b = BASE_URL . 'panel/pengguna.php';
$ROLE = ['admin' => 'Admin', 'pustakawan' => 'Pustakawan', 'mahasiswa' => 'Mahasiswa'];
$STATUS = ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'];
$BATAS = ['nama' => 150, 'username' => 120, 'email' => 150, 'nim_nidn' => 30, 'prodi' => 100];

// ---- Pembantu tampilan ----
function ikon_svg($isi, $kelas) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $kelas . '" aria-hidden="true">' . $isi . '</svg>';
}
const IK_BUKU = '<path d="M12 5v16"></path><path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z"></path>';
const IK_DOSEN = '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path><path d="M22 10v6"></path><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>';
const IK_PUSTAKAWAN = '<path d="m16 6 4 14"></path><path d="M12 6v14"></path><path d="M8 8v12"></path><path d="M4 4v16"></path>';
const IK_ADMIN = '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path>';
const IK_UNDUH = '<path d="M12 15V3"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="m7 10 5 5 5-5"></path>';
const IK_JAM = '<circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path>';
const IK_CARI = '<path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle>';
const IK_UBAH = '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path>';
const IK_TAMBAH = '<path d="M5 12h14"></path><path d="M12 5v14"></path>';
const IK_KEMBALI = '<path d="m15 18-6-6 6-6"></path>';

// Lencana role (label peran_tampil, K-06): [kelas warna lencana-*, ikon]
function gaya_peran($label, $role) {
    $peta = [
        'Mahasiswa' => ['mahasiswa', IK_BUKU],
        'Dosen' => ['dosen', IK_DOSEN],
        'Pustakawan' => ['pustakawan', IK_PUSTAKAWAN],
        'Admin' => ['admin', IK_ADMIN],
    ];
    return $peta[$label] ?? $peta[ucfirst($role)] ?? $peta['Mahasiswa'];
}

// $p = table | list | section | sidebar-section-list (awalan kelas semantik prototipe)
function lencana($label, $warna, $isi, $p) {
    $kelasIkon = $p === 'table' ? 'panel-main__table-icon has-size'
        : 'panel-main__' . $p . '-icon panel-main__' . $p . '-icon--size-3 has-size';
    return '<span class="panel-main__' . $p . '-text--gap-1 lencana-' . $warna . '">'
        . ikon_svg($isi, $kelasIkon) . e($label) . '</span>';
}

function lencana_user($u, $p) {
    [$warna, $isi] = gaya_peran($u['peran_tampil'], $u['role']);
    $h = lencana($u['peran_tampil'], $warna, $isi, $p);
    if (!empty($u['lencana'])) {
        $h .= lencana($u['lencana'], 'unduh', IK_UNDUH, $p);
    }
    return $h;
}

function pil_status($status, $p) {
    $varian = $status === 'aktif' ? 'success-soft' : 'destructive-soft';
    if ($p === 'section' && $status !== 'aktif') {
        $p = 'list'; // prototipe hanya punya pil Aktif di bagian ini; Nonaktif memakai varian daftar
    }
    return '<span class="panel-main__' . $p . '-text--bg-' . $varian . '"><span aria-hidden="true" class="panel-main__' . $p . '-text--bg-current has-size"></span>'
        . ($status === 'aktif' ? 'Aktif' : 'Nonaktif') . '</span>';
}

function sub_user($u, $dengan_angkatan_label = false) {
    $bagian = array_filter([$u['nim_nidn'], $u['prodi'], $u['angkatan']], fn($v) => $v !== null && $v !== '');
    return $bagian ? implode(' · ', $bagian) : 'Staf';
}

function berakhir_teks($u, $p) {
    if (empty($u['berakhir'])) {
        return '';
    }
    $kelasIkon = $p === 'table' ? 'panel-main__table-icon has-size' : 'panel-main__list-icon panel-main__list-icon--size-3 has-size';
    return '<p class="panel-main__' . $p . '-paragraph--gap-1">' . ikon_svg(IK_JAM, $kelasIkon) . 'Berakhir ' . e(formatTanggal($u['berakhir'])) . '</p>';
}

function kosong($v) { return trim((string) $v) === '' ? null : trim((string) $v); }

function flash_set($tipe, $pesan) { $_SESSION['flash'] = [$tipe, $pesan]; }
function alihkan($url) { header('Location: ' . $url); exit(); }

// Pesan ramah untuk galat basis data
function pesan_galat_db(PDOException $ex) {
    $kode = $ex->errorInfo[1] ?? 0;
    $teks = $ex->getMessage();
    if ($kode == 1062) {
        if (strpos($teks, 'username') !== false) return 'Username sudah dipakai pengguna lain.';
        if (strpos($teks, 'email') !== false) return 'Email sudah dipakai pengguna lain.';
        return 'Data sudah dipakai pengguna lain (duplikat).';
    }
    if ($kode == 1451) return 'Pengguna tidak bisa dihapus karena masih dipakai data lain.';
    return 'Terjadi kesalahan basis data. Coba lagi.';
}

// Baca isian form dari POST
function baca_isian() {
    $s = fn($k) => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
    return [
        'nama' => $s('nama'), 'username' => $s('username'), 'email' => $s('email'),
        'role' => $s('role'), 'nim_nidn' => $s('nim_nidn'), 'prodi' => $s('prodi'),
        'status' => $s('status'),
        'password' => is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
    ];
}

// Validasi; kembalikan daftar pesan galat
function validasi($f, $tambah, $ROLE, $STATUS, $BATAS) {
    $g = [];
    foreach (['nama' => 'Nama', 'username' => 'Username', 'email' => 'Email'] as $k => $label) {
        if ($f[$k] === '') $g[] = $label . ' wajib diisi.';
    }
    foreach ($BATAS as $k => $maks) {
        if (mb_strlen($f[$k]) > $maks) $g[] = ucfirst($k) . ' maksimal ' . $maks . ' karakter.';
    }
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $g[] = 'Format email tidak valid.';
    if (!isset($ROLE[$f['role']])) $g[] = 'Role tidak valid.';
    if (!isset($STATUS[$f['status']])) $g[] = 'Status tidak valid.';
    if ($tambah && $f['password'] === '') $g[] = 'Password wajib diisi saat menambah pengguna.';
    if ($f['password'] !== '' && strlen($f['password']) < 6) $g[] = 'Password minimal 6 karakter.';
    return $g;
}

// ---- Aksi POST (PRG; galat validasi menampilkan ulang form) ----
$galat = [];
$isian = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $id = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int) $_POST['id'] : 0;

    if ($aksi === 'delete') {
        $u = new User($db);
        $u->id = $id;
        if ($id === $saya) {
            flash_set('galat', 'Anda tidak bisa menghapus akun sendiri.');
        } elseif (!$u->readOne()) {
            flash_set('galat', 'Pengguna tidak ditemukan.');
        } else {
            try {
                $u->delete();
                flash_set('ok', 'Pengguna "' . $u->nama . '" dihapus.');
            } catch (PDOException $ex) {
                flash_set('galat', pesan_galat_db($ex));
            }
        }
        alihkan($b);
    }

    if ($aksi === 'create' || $aksi === 'update') {
        $tambah = ($aksi === 'create');
        $isian = baca_isian();
        $galat = validasi($isian, $tambah, $ROLE, $STATUS, $BATAS);
        $u = new User($db);
        if (!$tambah) {
            $u->id = $id;
            if (!$u->readOne()) {
                flash_set('galat', 'Pengguna tidak ditemukan.');
                alihkan($b);
            }
            if ($id === $saya && ($isian['role'] !== 'admin' || $isian['status'] !== 'aktif')) {
                $galat[] = 'Anda tidak bisa menurunkan role atau menonaktifkan akun sendiri.';
            }
        }
        if (!$galat) {
            $u->nama = $isian['nama'];
            $u->username = $isian['username'];
            $u->email = $isian['email'];
            $u->nim_nidn = kosong($isian['nim_nidn']);
            $u->prodi = kosong($isian['prodi']);
            $u->status = $isian['status'];
            $u->password = $isian['password']; // polos; di-hash oleh model, kosong = tidak diubah
            try {
                if ($tambah) {
                    $u->role = $isian['role'];
                    // Slug unik dari nama (model hanya membuat slug dasar)
                    $dasar = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($u->nama)), '-') ?: 'pengguna';
                    $u->slug = $dasar;
                    $cek = new User($db);
                    for ($n = 2; $cek->readBySlug($u->slug); $n++) {
                        $u->slug = $dasar . '-' . $n;
                    }
                    $u->create();
                    flash_set('ok', 'Pengguna "' . $u->nama . '" ditambahkan.');
                    alihkan($b);
                }
                if ($u->role !== $isian['role']) {
                    $u->peran_tampil = $ROLE[$isian['role']]; // role berubah: label ikut
                }
                $u->role = $isian['role'];
                $u->update();
                if ($id === $saya) {
                    $_SESSION['user_nama'] = $u->nama;
                }
                flash_set('ok', 'Data pengguna disimpan.' . ($isian['password'] !== '' ? ' Password diganti.' : ''));
                alihkan($b . '?slug=' . urlencode($u->slug));
            } catch (PDOException $ex) {
                $galat[] = pesan_galat_db($ex);
            }
        }
    }
}

// ---- Data ----
$model = new User($db);
$semua = $model->readAll()->fetchAll(PDO::FETCH_ASSOC);
$slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$mode = 'daftar';
$ubah = null;
if (($_GET['aksi'] ?? '') === 'tambah' || ($isian !== null && ($_POST['action'] ?? '') === 'create')) {
    $mode = 'tambah';
} elseif ($slug !== '' || ($isian !== null && ($_POST['action'] ?? '') === 'update')) {
    $cari = $slug !== '' ? $slug : null;
    $ubahModel = new User($db);
    $ketemu = $cari !== null ? $ubahModel->readBySlug($cari) : ($ubahModel->id = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id']) ? (int) $_POST['id'] : 0)) && $ubahModel->readOne();
    if (!$ketemu) {
        flash_set('galat', 'Pengguna tidak ditemukan.');
        alihkan($b);
    }
    $ubah = [];
    foreach (['id', 'slug', 'nama', 'username', 'email', 'role', 'peran_tampil', 'lencana', 'berakhir', 'nim_nidn', 'prodi', 'angkatan', 'status', 'terakhir_masuk', 'dibaca', 'unggahan', 'created_at'] as $k) {
        $ubah[$k] = $ubahModel->$k;
    }
    $mode = 'ubah';
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Nilai awal form
if ($isian === null) {
    $isian = $mode === 'ubah'
        ? ['nama' => $ubah['nama'], 'username' => $ubah['username'], 'email' => $ubah['email'], 'role' => $ubah['role'],
           'nim_nidn' => (string) $ubah['nim_nidn'], 'prodi' => (string) $ubah['prodi'], 'status' => $ubah['status'], 'password' => '']
        : ['nama' => '', 'username' => '', 'email' => '', 'role' => 'mahasiswa', 'nim_nidn' => '', 'prodi' => '', 'status' => 'aktif', 'password' => ''];
}

// Pemberitahuan (flash sukses/galat + galat validasi)
function tampil_pesan($flash, $galat) {
    $h = '';
    if ($flash) {
        $ok = $flash[0] === 'ok';
        $h .= '<div role="' . ($ok ? 'status' : 'alert') . '" class="pesan ' . ($ok ? 'pesan--ok' : 'pesan--galat') . '">' . e($flash[1]) . '</div>';
    }
    if ($galat) {
        $h .= '<div role="alert" class="pesan pesan--galat"><ul class="pesan__daftar">';
        foreach ($galat as $g) $h .= '<li>' . e($g) . '</li>';
        $h .= '</ul></div>';
    }
    return $h;
}

// Satu kolom form
function kolom($id, $label, $isi) {
    return '<div class="situs-main__section-form-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="' . $id . '">' . $label . '</label>' . $isi . '</div>';
}
function kolom_teks($id, $label, $nilai, $maks, $tipe = 'text', $wajib = false, $auto = '') {
    return kolom($id, $label, '<input data-slot="input" class="input input--text-hz-body-h-42px" id="' . $id . '" name="' . $id . '" type="' . $tipe . '" maxlength="' . $maks . '" value="' . e($nilai) . '"' . ($wajib ? ' required' : '') . ($auto ? ' autocomplete="' . $auto . '"' : '') . '>');
}
function kolom_pilih($id, $label, $opsi, $terpilih) {
    $o = '';
    foreach ($opsi as $nilai => $teks) {
        $o .= '<option value="' . e($nilai) . '"' . ($nilai === $terpilih ? ' selected' : '') . '>' . e($teks) . '</option>';
    }
    return kolom($id, $label, '<div class="select-bungkus"><select class="select-trigger select-trigger--px-3-5-rounded-md-text-hz-body-w-full has-size" id="' . $id . '" name="' . $id . '">' . $o . '</select>' . chevron_select() . '</div>');
}

function form_pengguna($mode, $f, $ubah, $ROLE, $STATUS, $BATAS, $batal) {
    $tambah = $mode === 'tambah';
    $h = '<form method="post" action="' . e($batal) . '" class="panel-main__section panel-main__section--gap-4-flex-flex-col-p-4 form-pengguna" novalidate>' . csrf_field();
    $h .= '<input type="hidden" name="action" value="' . ($tambah ? 'create' : 'update') . '">';
    if (!$tambah) $h .= '<input type="hidden" name="id" value="' . (int) $ubah['id'] . '">';
    $h .= '<h2 class="panel-main__sidebar-section-heading">' . ($tambah ? 'Data pengguna baru' : 'Ubah data pengguna') . '</h2><div class="form-pengguna__kisi">';
    $h .= kolom_teks('nama', 'Nama', $f['nama'], $BATAS['nama'], 'text', true, 'off');
    $h .= kolom_teks('username', 'Username', $f['username'], $BATAS['username'], 'text', true, 'off');
    $h .= kolom_teks('email', 'Email', $f['email'], $BATAS['email'], 'email', true, 'off');
    $h .= kolom_pilih('role', 'Role', $ROLE, $f['role']);
    $h .= kolom_teks('nim_nidn', 'NIM/NIDN', $f['nim_nidn'], $BATAS['nim_nidn']);
    $h .= kolom_teks('prodi', 'Program studi', $f['prodi'], $BATAS['prodi']);
    $h .= kolom_pilih('status', 'Status', $STATUS, $f['status']);
    $h .= kolom('password', $tambah ? 'Password' : 'Password baru (kosongkan bila tidak diganti)', '<input data-slot="input" class="input input--text-hz-body-h-42px" id="password" name="password" type="password" autocomplete="new-password"' . ($tambah ? ' required' : '') . '>');
    $h .= '</div><div class="form-pengguna__aksi"><button data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" type="submit">' . ($tambah ? 'Tambah pengguna' : 'Simpan perubahan') . '</button>'
        . '<a data-slot="button" data-variant="outline" data-size="default" class="button button--border-input-bg-card-px-5-text-foreground-2 group/button has-size" href="' . e($batal) . '">Batal</a></div></form>';
    return $h;
}

$judul = 'Pengguna';
$crumb = ['Pengguna', $mode === 'daftar' ? 'Data Pengguna' : ($mode === 'tambah' ? 'Tambah pengguna' : $ubah['nama'])];
$halaman_aktif = 'pengguna';
include ROOT_PATH . 'partials/head.php';
include ROOT_PATH . 'partials/panel_header.php';

if ($mode === 'daftar') {
    // ---- Filter (GET) ----
    $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    $fs = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
    $fr = is_string($_GET['role'] ?? null) && isset($ROLE[$_GET['role']]) ? $_GET['role'] : '';
    $jml = count($semua);
    $jmlAktif = count(array_filter($semua, fn($u) => $u['status'] === 'aktif'));
    $jmlNonaktif = $jml - $jmlAktif;
    $jmlBatas = count(array_filter($semua, fn($u) => !empty($u['berakhir'])));
    $daftar = array_values(array_filter($semua, function ($u) use ($q, $fs, $fr) {
        if ($fs === 'aktif' || $fs === 'nonaktif') { if ($u['status'] !== $fs) return false; }
        if ($fs === 'berbatas' && empty($u['berakhir'])) return false;
        if ($fr !== '' && $u['role'] !== $fr) return false;
        if ($q !== '') {
            foreach (['nama', 'nim_nidn', 'username', 'email'] as $k) {
                if ($u[$k] !== null && mb_stripos($u[$k], $q) !== false) return true;
            }
            return false;
        }
        return true;
    }));
    $tab = function ($kunci, $label, $n) use ($b, $q, $fr, $fs) {
        $aktif = ($fs === $kunci) || ($kunci === '' && !in_array($fs, ['aktif', 'nonaktif', 'berbatas'], true));
        $param = array_filter(['q' => $q, 'role' => $fr, 'status' => $kunci]);
        return '<a href="' . e($b . ($param ? '?' . http_build_query($param) : '')) . '" data-state="' . ($aktif ? 'active' : 'inactive') . '" aria-current="' . ($aktif ? 'true' : 'false') . '" data-slot="tabs-trigger" class="tabs-trigger has-size">' . e($label) . ' (' . $n . ')</a>';
    };
    $formCari = function ($kelasWrap, $placeholder) use ($b, $q, $fs, $fr) {
                $h = '<form method="get" action="' . e($b) . '" role="search" class="form-cari">';
        if ($fs !== '') $h .= '<input type="hidden" name="status" value="' . e($fs) . '">';
        $h .= '<div class="' . $kelasWrap . '"><input data-slot="input" class="input input--pr-10-text-hz-body-lg-px-4" aria-label="Cari nama atau NIM/NIDN" placeholder="' . $placeholder . '" type="search" name="q" value="' . e($q) . '">'
            . ikon_svg(IK_CARI, 'panel-main__icon panel-main__icon--translate-y-1-2-absolute-pointer-events-none-right-3-5 has-size') . '</div>';
        return $h;
    };
    $tombolUbah = fn($u) => '<a data-slot="button" data-variant="ghost" data-size="icon-sm" class="dropdown-menu-trigger dropdown-menu-trigger--border-transparent group/button has-size" aria-label="Ubah ' . e($u['nama']) . '" href="' . e($b . '?slug=' . urlencode($u['slug'])) . '">' . ikon_svg(IK_UBAH, 'dropdown-menu-trigger__icon dropdown-menu-trigger__icon--size-4-text-muted-foreground has-size') . '</a>';
    ?>
<div class="panel-main__box--flex-col-gap-4-flex-lg-gap-22px"><header class="panel-main__header"><h1 class="panel-main__header-title">Data Pengguna</h1><div class="panel-main__header-box panel-main__header-box--flex-col-gap-3"><p class="panel-main__header-paragraph panel-main__header-paragraph--max-w-560px">Akun beserta role-nya. Buka detail untuk mengubah data, mengganti password, atau menghapus akun.</p><div class="panel-main__header-box panel-main__header-box--gap-2-max-lg-flex-1"><a data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" href="<?= e($b) ?>?aksi=tambah"><?= ikon_svg(IK_TAMBAH, 'button__icon button__icon--size-4 has-size') ?>Tambah pengguna</a></div></div></header>
<?= tampil_pesan($flash, []) ?>
<div class="panel-main__box--grid-cols-2-gap-3-grid-lg-grid-cols-4"><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-hz-stat"><?= $jml ?></span><span class="panel-main__text--text-hz-xs">Total pengguna</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-success"><?= $jmlAktif ?></span><span class="panel-main__text--text-hz-xs">Aktif</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-destructive"><?= $jmlNonaktif ?></span><span class="panel-main__text--text-hz-xs">Nonaktif</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-warning"><?= $jmlBatas ?></span><span class="panel-main__text--text-hz-xs">Role berbatas waktu</span></div></div>
<div dir="ltr" data-orientation="horizontal" data-slot="tabs" class="tabs tabs--gap-2 group/tabs"><div aria-orientation="horizontal" data-slot="tabs-list" class="tabs-list" aria-label="Filter status akun" data-orientation="horizontal"><?= $tab('', 'Semua', $jml) ?><?= $tab('aktif', 'Aktif', $jmlAktif) ?><?= $tab('nonaktif', 'Nonaktif', $jmlNonaktif) ?><?= $tab('berbatas', 'Role berbatas waktu', $jmlBatas) ?></div></div>
<div class="panel-main__box--hidden-overflow-hidden-border-rounded-xl"><div class="panel-main__box--border-b-gap-2-px-5-items-center"><?= $formCari('panel-main__box--min-w-0-shrink-0-relative-w-240px', 'Cari nama atau NIM/NIDN…') ?><div class="select-bungkus"><select data-slot="select-trigger" data-size="default" class="select-trigger select-trigger--px-3-rounded-sm-text-hz-sm-w-170px has-size" name="role" aria-label="Role" data-auto-submit><option value="">Semua role</option><?php foreach ($ROLE as $k => $t): ?><option value="<?= e($k) ?>"<?= $fr === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select><?= chevron_select() ?></div></form></div><table class="panel-main__table"><colgroup><col><col class="col-w-186"><col class="col-w-100"><col class="col-w-118"><col class="col-w-72"><col class="col-w-92"><col class="col-w-52"></colgroup><thead><tr class="panel-main__table-row panel-main__table-row--text-hz-overline-text-muted-foreground-uppercase-font-inherit"><th class="panel-main__table-head-cell--pl-5">Pengguna</th><th>Role</th><th>Status</th><th>Terakhir masuk</th><th class="panel-main__table-head-cell--text-right">Dibaca</th><th class="panel-main__table-head-cell--pr-3-text-right">Unggahan</th><th class="panel-main__table-head-cell--pr-5"><span class="panel-main__table-text--sr-only">Aksi</span></th></tr></thead><tbody>
<?php foreach ($daftar as $u): ?>
<tr class="panel-main__table-row panel-main__table-row--align-middle-h-72px-last-border-b-0"><td class="panel-main__table-cell--pr-4-py-3 panel-main__table-cell--pl-5"><a class="panel-main__table-link panel-main__table-link--block" href="<?= e($b . '?slug=' . urlencode($u['slug'])) ?>"><?= e($u['nama']) ?></a><p class="panel-main__table-paragraph--truncate"><?= e(sub_user($u)) ?></p></td><td class="panel-main__table-cell--pr-3-py-3"><div class="panel-main__table-box panel-main__table-box--flex-col"><div class="panel-main__table-box panel-main__table-box--flex-wrap"><?= lencana_user($u, 'table') ?></div><?= berakhir_teks($u, 'table') ?></div></td><td><?= pil_status($u['status'], 'table') ?></td><td class="panel-main__table-cell--text-hz-sm"><?= e(formatTanggal($u['terakhir_masuk'], 'singkat')) ?></td><td class="panel-main__table-cell--tabular-nums-text-right"><?= (int) $u['dibaca'] ?></td><td class="panel-main__table-cell--tabular-nums-pr-3"><?= (int) $u['unggahan'] ?></td><td class="panel-main__table-cell--pr-3-5-text-right"><?= $tombolUbah($u) ?></td></tr>
<?php endforeach; ?>
<?php if (!$daftar): ?><tr class="panel-main__table-row"><td colspan="7" class="panel-main__table-cell--text-hz-sm">Tidak ada pengguna yang cocok.</td></tr><?php endif; ?>
</tbody></table><div class="panel-main__box--border-t-h-14-justify-between-px-5"><p class="panel-main__paragraph panel-main__paragraph--text-hz-xs">Menampilkan <?= $daftar ? '1–' . count($daftar) : '0' ?> dari <?= count($daftar) ?> pengguna</p></div></div>
<div class="panel-main__box--gap-3-flex-col-flex-lg-hidden"><div class="panel-main__box--gap-2-flex"><?= $formCari('panel-main__box--flex-1-min-w-0-relative', 'Cari nama atau NIM…') ?></form></div><ul class="panel-main__list">
<?php foreach ($daftar as $u): ?>
<li class="panel-main__list-item panel-main__list-item--gap-1-pl-4-pr-1-relative"><div class="panel-main__list-box--pl-2"><a data-stretched="true" class="panel-main__list-link panel-main__list-link--block" href="<?= e($b . '?slug=' . urlencode($u['slug'])) ?>"><?= e($u['nama']) ?></a><p class="panel-main__list-paragraph--text-hz-xs"><?= e(sub_user($u)) ?></p><div class="panel-main__list-box--mt-1-5"><div class="panel-main__list-box--flex-col"><div class="panel-main__list-box--flex-wrap"><?= $u['status'] !== 'aktif' ? pil_status($u['status'], 'list') : '' ?><?= lencana_user($u, 'list') ?></div><?= berakhir_teks($u, 'list') ?></div></div></div><a data-slot="button" data-variant="ghost" data-size="icon" class="button button--z-10-mt-1-relative-size-9 group/button has-size" aria-label="Ubah <?= e($u['nama']) ?>" href="<?= e($b . '?slug=' . urlencode($u['slug'])) ?>"><?= ikon_svg(IK_UBAH, 'button__icon button__icon--text-muted-foreground has-size') ?></a></li>
<?php endforeach; ?>
</ul><p class="panel-main__paragraph panel-main__paragraph--text-center"><?= count($daftar) ?> pengguna</p></div></div>
<?php
} else {
    // ---- Tambah / ubah ----
    $tambah = $mode === 'tambah';
    $formBatal = $b . ($tambah ? '' : '?slug=' . urlencode($ubah['slug']));
    $ini = !$tambah && (int) $ubah['id'] === $saya;
    ?>
<div class="panel-main__box--flex-col-gap-4-flex-lg-gap-22px"><a class="panel-main__link" href="<?= e($b) ?>"><?= ikon_svg(IK_KEMBALI, 'panel-main__icon has-size') ?>Data Pengguna</a>
<?= tampil_pesan($flash, $galat) ?>
<?php if ($tambah): ?>
<header class="panel-main__header"><h1 class="panel-main__header-title">Tambah pengguna</h1><div class="panel-main__header-box panel-main__header-box--flex-col-gap-3"><p class="panel-main__header-paragraph panel-main__header-paragraph--max-w-560px">Buat akun baru. Pengguna masuk dengan username atau email dan password ini.</p></div></header>
<div class="form-pengguna-bungkus"><?= form_pengguna('tambah', $isian, null, $ROLE, $STATUS, $BATAS, $b . '?aksi=tambah') ?></div>
<?php else:
    [$warna, $isiIkon] = gaya_peran($ubah['peran_tampil'], $ubah['role']);
    $sub = array_filter([$ubah['nim_nidn'], $ubah['prodi'], $ubah['angkatan'] ? 'Angkatan ' . $ubah['angkatan'] : null], fn($v) => $v !== null && $v !== '');
    ?>
<section class="panel-main__section panel-main__section--gap-4-flex-flex-col-p-4"><div class="panel-main__section-box--gap-4-flex-1"><span data-slot="avatar" data-size="default" class="avatar avatar--border-2-size-14-lg-size-72px group/avatar has-size"><span data-slot="avatar-fallback" class="avatar-fallback avatar-fallback--text-hz-h4 has-size"><?= e(mb_strtoupper(mb_substr($ubah['nama'], 0, 1))) ?></span></span><div class="panel-main__section-box--gap-1-5-min-w-0"><div class="panel-main__section-box--gap-2-items-center"><h1 class="panel-main__section-title"><?= e($ubah['nama']) ?></h1><?= pil_status($ubah['status'], 'section') ?></div><p class="panel-main__section-paragraph--text-hz-sm-text-muted-foreground"><?= e($sub ? implode(' · ', $sub) : 'Staf') ?></p><div class="panel-main__section-box--gap-1-5-items-center"><?= lencana_user($ubah, 'section') ?></div></div></div>
<?php if (!$ini): ?><div class="panel-main__section-box--gap-2-flex-wrap"><form method="post" action="<?= e($b) ?>" data-confirm="Hapus pengguna ini? Tindakan tidak bisa dibatalkan."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $ubah['id'] ?>"><button data-slot="button" data-variant="outline" data-size="sm" class="button button--border-destructive-40-text-destructive-rounded-md-bg-card group/button has-size" type="submit"><span>Hapus pengguna</span></button></form></div><?php endif; ?></section>
<div class="panel-main__box--grid-cols-2-gap-3-grid-lg-grid-cols-4"><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-hz-stat"><?= (int) $ubah['dibaca'] ?></span><span class="panel-main__text--text-hz-xs">Dokumen dibaca</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-hz-stat"><?= (int) $ubah['unggahan'] ?></span><span class="panel-main__text--text-hz-xs">Unggahan</span></div></div>
<div class="panel-main__box--flex-col-gap-4-flex-lg-flex-row"><?= form_pengguna('ubah', $isian, $ubah, $ROLE, $STATUS, $BATAS, $formBatal) ?>
<aside class="panel-main__sidebar panel-main__sidebar--flex-col"><section aria-labelledby="info-info-akun" class="panel-main__sidebar-section panel-main__sidebar-section--gap-2-5"><h2 id="info-info-akun" class="panel-main__sidebar-section-heading">Info akun</h2><dl class="panel-main__sidebar-section-definition-list">
<?php
    $info = ['Username' => $ubah['username'], 'Email' => $ubah['email'], 'NIM/NIDN' => $ubah['nim_nidn'], 'Program studi' => $ubah['prodi'], 'Angkatan' => $ubah['angkatan'],
        'Dibuat' => formatTanggal($ubah['created_at']), 'Terakhir masuk' => formatTanggal($ubah['terakhir_masuk'], 'singkat')];
    foreach ($info as $label => $nilai):
        if ($nilai === null || $nilai === '') continue; ?>
<div class="panel-main__sidebar-section-definition-list-box"><dt class="panel-main__sidebar-section-definition-list-term"><?= e($label) ?></dt><dd class="panel-main__sidebar-section-definition-list-detail"><?= e($nilai) ?></dd></div>
<?php endforeach; ?>
</dl></section><section aria-labelledby="role-pengguna" class="panel-main__sidebar-section panel-main__sidebar-section--gap-3"><h2 id="role-pengguna" class="panel-main__sidebar-section-heading">Role &amp; masa berlaku</h2><ul class="panel-main__sidebar-section-list panel-main__sidebar-section-list--gap-3"><li class="panel-main__sidebar-section-list-item panel-main__sidebar-section-list-item--items-center"><div class="panel-main__sidebar-section-list-box"><?= lencana($ubah['peran_tampil'], $warna, $isiIkon, 'sidebar-section-list') ?><span class="panel-main__sidebar-section-list-text--text-hz-hint"><?= $ubah['berakhir'] ? 'Berakhir ' . e(formatTanggal($ubah['berakhir'])) : 'Permanen' ?></span></div></li></ul></section></aside></div>
<?php endif; ?>
</div>
<?php
}
include ROOT_PATH . 'partials/panel_footer.php';
