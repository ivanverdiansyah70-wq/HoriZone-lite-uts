<?php
// Panel koleksi: daftar (default), ubah (?slug=d1), hapus. Markup = 'panel-data-koleksi' dan 'panel-koleksi-detail'.
// Bagian prototipe tanpa data DB (render, riwayat verifikasi, statistik pembaca unik/durasi, filter prodi) tidak dibuat (K-05).
// Tambah (?aksi=tambah) + unggah cover/PDF ke uploads/ (T-07).
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'pustakawan']);

$db = (new Database())->getConnection();
$dokumen = new Dokumen($db);
$b = BASE_URL . 'panel/koleksi.php';

// ---- Pesan flash (PRG) ----
function flash($jenis, $teks) { $_SESSION['flash'] = [$jenis, $teks]; }

// Hapus file unggahan (uploads/...), file contoh (assets/img/seed/) dibiarkan
function hapusBerkas($path) {
    if (!$path || strpos($path, 'uploads/') !== 0) return;
    $file = realpath(ROOT_PATH . $path);
    $folder = realpath(ROOT_PATH . 'uploads');
    if ($file && $folder && strpos($file, $folder . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
        unlink($file);
    }
}

$galat = '';
$nilai = null; // isian form saat validasi gagal

// Simpan satu file unggahan ke uploads/<folder>/. Hasil: path relatif | null (tidak ada file / gagal; cek $galat)
function simpanUnggahan($kunci, $folder, $mimeOk, $maks, $label, &$galat) {
    if (!isset($_FILES[$kunci]) || !is_array($_FILES[$kunci]) || $_FILES[$kunci]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$kunci];
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        $galat = $label . ' terlalu besar (maks. ' . round($maks / 1048576) . ' MB).';
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
        $galat = $label . ' gagal diunggah. Coba lagi.';
    } elseif ($f['size'] > $maks) {
        $galat = $label . ' terlalu besar (maks. ' . round($maks / 1048576) . ' MB).';
    } elseif (!is_uploaded_file($f['tmp_name'])) {
        $galat = $label . ' tidak valid.';
    } else {
        // MIME dari isi file, bukan dari ekstensi atau type kiriman klien
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset($mimeOk[$mime])) {
            $galat = $label . ' harus berformat ' . implode(', ', array_unique($mimeOk)) . '.';
        } else {
            $nama = bin2hex(random_bytes(16)) . '.' . $mimeOk[$mime];
            if (move_uploaded_file($f['tmp_name'], ROOT_PATH . 'uploads/' . $folder . '/' . $nama)) {
                return 'uploads/' . $folder . '/' . $nama;
            }
            $galat = $label . ' gagal disimpan.';
        }
    }
    return null;
}
$MIME_COVER = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$MIME_PDF = ['application/pdf' => 'pdf'];

// Ambil isian form + validasi sederhana. Hasil: [nilai, galat]
function bacaIsian() {
    $isi = fn($k) => trim((string) ($_POST[$k] ?? ''));
    $n = [
        'judul' => $isi('judul'), 'penulis' => $isi('penulis'), 'jenis' => $isi('jenis'),
        'prodi' => $isi('prodi'), 'tahun' => $isi('tahun'), 'pembimbing' => $isi('pembimbing'),
        'halaman' => $isi('halaman'), 'akses' => $isi('akses'), 'status' => $isi('status'),
        'kata_kunci' => $isi('kata_kunci'), 'abstrak' => $isi('abstrak'),
        'bisa_unduh' => isset($_POST['bisa_unduh']) ? 1 : 0,
    ];
    $g = '';
    if ($n['judul'] === '' || $n['penulis'] === '') {
        $g = 'Judul dan penulis wajib diisi.';
    } elseif ($n['prodi'] === '') {
        $g = 'Program studi wajib diisi.';
    } elseif (!ctype_digit($n['tahun']) || (int) $n['tahun'] < 1900 || (int) $n['tahun'] > 2100) {
        $g = 'Tahun harus berupa angka 1900-2100.';
    } elseif ($n['halaman'] !== '' && !ctype_digit($n['halaman'])) {
        $g = 'Jumlah halaman harus berupa angka.';
    } elseif (!isset(Dokumen::JENIS[$n['jenis']]) || !isset(Dokumen::AKSES[$n['akses']]) || !isset(Dokumen::STATUS[$n['status']])) {
        $g = 'Jenis, akses, atau status tidak valid.';
    }
    return [$n, $g];
}

// Pindahkan isian ke model
function isiModel($m, $n) {
    $m->judul = $n['judul'];
    $m->penulis = $n['penulis'];
    $m->jenis = $n['jenis'];
    $m->prodi = $n['prodi'];
    $m->tahun = (int) $n['tahun'];
    $m->pembimbing = $n['pembimbing'] !== '' ? $n['pembimbing'] : null;
    $m->halaman = (int) $n['halaman'];
    $m->akses = $n['akses'];
    $m->bisa_unduh = $n['bisa_unduh'];
    $m->kata_kunci = $n['kata_kunci'] !== '' ? $n['kata_kunci'] : null;
    $m->abstrak = $n['abstrak'] !== '' ? $n['abstrak'] : null;
    $m->status = $n['status'];
    if ($n['status'] === 'terbit' && empty($m->terbit_pada)) $m->terbit_pada = date('Y-m-d');
}

// ---- Proses POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiriman melebihi post_max_size: PHP mengosongkan $_POST dan $_FILES
    if (!$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('err', 'Kiriman terlalu besar. Kecilkan ukuran berkas (cover maks. 2 MB, PDF maks. 10 MB).');
        header('Location: ' . $b);
        exit();
    }
    csrf_check();
    $aksi = $_POST['action'] ?? '';
    $id = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int) $_POST['id'] : 0;

    if ($aksi === 'create') {
        [$nilai, $galat] = bacaIsian();
        $cover = $pdf = null;
        if ($galat === '') {
            $cover = simpanUnggahan('cover', 'cover', $MIME_COVER, 2 * 1048576, 'Sampul', $galat);
            if ($galat === '') $pdf = simpanUnggahan('pdf', 'pdf', $MIME_PDF, 10 * 1048576, 'Berkas PDF', $galat);
            if ($galat === '' && !$pdf) $galat = 'Berkas PDF wajib diunggah.';
        }
        if ($galat === '') {
            isiModel($dokumen, $nilai);
            $dokumen->cover = $cover;
            $dokumen->file_pdf = $pdf;
            $dokumen->pengunggah_id = $_SESSION['user_id'];
            if ($dokumen->create()) {
                flash('ok', 'Dokumen "' . $nilai['judul'] . '" ditambahkan.');
                header('Location: ' . $b . '?slug=' . urlencode($dokumen->slug));
                exit();
            }
            $galat = 'Dokumen gagal disimpan.';
        }
        // gagal: buang file yang sudah terlanjur dipindah
        hapusBerkas($cover);
        hapusBerkas($pdf);
        $_GET['aksi'] = 'tambah'; // tampil ulang form tambah dengan pesan galat
    } elseif ($aksi === 'update' || $aksi === 'delete') {
        $dokumen->id = $id;
        if (!$dokumen->readOne()) {
            flash('err', 'Dokumen tidak ditemukan.');
            header('Location: ' . $b);
            exit();
        }
        $slug = $dokumen->slug;

        if ($aksi === 'delete') {
            $cover = $dokumen->cover;
            $pdf = $dokumen->file_pdf;
            $judul = $dokumen->judul;
            if ($dokumen->delete()) {
                hapusBerkas($cover);
                hapusBerkas($pdf);
                flash('ok', 'Dokumen "' . $judul . '" dihapus.');
            } else {
                flash('err', 'Dokumen gagal dihapus.');
            }
            header('Location: ' . $b);
            exit();
        }

        // update: isian + (opsional) cover/PDF baru
        [$nilai, $galat] = bacaIsian();
        $coverBaru = $pdfBaru = null;
        if ($galat === '') {
            $coverBaru = simpanUnggahan('cover', 'cover', $MIME_COVER, 2 * 1048576, 'Sampul', $galat);
            if ($galat === '') $pdfBaru = simpanUnggahan('pdf', 'pdf', $MIME_PDF, 10 * 1048576, 'Berkas PDF', $galat);
        }
        if ($galat === '') {
            $coverLama = $dokumen->cover;
            $pdfLama = $dokumen->file_pdf;
            isiModel($dokumen, $nilai);
            if ($coverBaru) $dokumen->cover = $coverBaru;
            if ($pdfBaru) $dokumen->file_pdf = $pdfBaru;
            if ($dokumen->update()) {
                // file lama dihapus setelah yang baru sukses tersimpan
                if ($coverBaru) hapusBerkas($coverLama);
                if ($pdfBaru) hapusBerkas($pdfLama);
                flash('ok', 'Perubahan disimpan.');
                header('Location: ' . $b . '?slug=' . urlencode($slug));
                exit();
            }
            $galat = 'Perubahan gagal disimpan.';
        }
        hapusBerkas($coverBaru);
        hapusBerkas($pdfBaru);
        $_GET['slug'] = $slug; // tampil ulang halaman ubah dengan pesan galat
    }
}

// ---- Pesan flash untuk ditampilkan ----
$pesan = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if ($galat !== '') $pesan = ['err', $galat];

// ---- Ikon (isi path lucide) ----
function ikon($isi, $kelas) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $kelas . ' has-size" aria-hidden="true">' . $isi . '</svg>';
}
$IK_JENIS = [
    'skripsi' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"></path><path d="M22 10v6"></path><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>',
    'ebook' => '<path d="M12 5v16"></path><path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z"></path>',
    'jurnal' => '<path d="M15 12h-5"></path><path d="M15 8h-5"></path><path d="M19 17V5a2 2 0 0 0-2-2H4"></path><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"></path>',
    'laporan_kp' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>',
];
$IK_JENIS['catatan'] = $IK_JENIS['ebook'];
$IK_CARI = '<path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle>';
$IK_UNGGAH = '<path d="M12 13V7"></path><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"></path><path d="m9 10 3-3 3 3"></path>';
$IK_UBAH = '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path>';
$IK_HAPUS = '<path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>';
$IK_BUKA = '<path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>';
$IK_KIRI = '<path d="m15 18-6-6 6-6"></path>';
$IK_KANAN = '<path d="m9 18 6-6-6-6"></path>';

$KELAS_STATUS = ['terbit' => 'success-soft', 'menunggu' => 'warning-soft', 'diturunkan' => 'destructive-soft'];
function angka($n) { return number_format((int) $n, 0, ',', '.'); }

// Bagian form: metadata dokumen (dipakai halaman tambah dan ubah)
function bagianIsian($f, $judulBagian) {
?>
<section aria-labelledby="form-ubah"><div class="panel-main__form-section-box--items-center-flex-gap-11px"><h2 id="form-ubah" class="panel-main__form-section-heading"><?= e($judulBagian) ?></h2></div>
<div class="panel-main__form-section-box--mt-3-5-gap-3-5-flex-col">
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-judul">Judul</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-judul" name="judul" required value="<?= e($f['judul']) ?>"></div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-penulis">Penulis</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-penulis" name="penulis" required value="<?= e($f['penulis']) ?>"></div>
<div class="panel-main__form-section-box--grid-gap-3-5-lg-grid-cols-2">
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-jenis">Jenis</label><div class="select-bungkus"><select class="select-trigger select-trigger--px-3-5-rounded-md-text-hz-body-w-full has-size" id="f-jenis" name="jenis"><?php foreach (Dokumen::JENIS as $k => $lbl): ?><option value="<?= e($k) ?>"<?= $f['jenis'] === $k ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?></select><?= chevron_select() ?></div></div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-prodi">Program studi</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-prodi" name="prodi" required value="<?= e($f['prodi']) ?>"></div>
</div>
<div class="panel-main__form-section-box--grid-gap-3-5-lg-grid-cols-2">
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-tahun">Tahun</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-tahun" name="tahun" inputmode="numeric" required value="<?= e($f['tahun']) ?>"></div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-halaman">Jumlah halaman</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-halaman" name="halaman" inputmode="numeric" value="<?= e($f['halaman']) ?>"></div>
</div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-pembimbing">Pembimbing (opsional)</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-pembimbing" name="pembimbing" value="<?= e($f['pembimbing']) ?>"></div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-abstrak">Abstrak</label><textarea data-slot="textarea" class="textarea textarea--field-sizing-fixed has-size" id="f-abstrak" name="abstrak" rows="6"><?= e($f['abstrak']) ?></textarea></div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-kata">Kata kunci (pisahkan dengan koma)</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-kata" name="kata_kunci" value="<?= e($f['kata_kunci']) ?>"></div>
<div class="panel-main__form-section-box--grid-gap-3-5-lg-grid-cols-2">
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-akses">Akses</label><div class="select-bungkus"><select class="select-trigger select-trigger--px-3-5-rounded-md-text-hz-body-w-full has-size" id="f-akses" name="akses"><?php foreach (Dokumen::AKSES as $k => $lbl): ?><option value="<?= e($k) ?>"<?= $f['akses'] === $k ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?></select><?= chevron_select() ?></div></div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-status">Status</label><div class="select-bungkus"><select class="select-trigger select-trigger--px-3-5-rounded-md-text-hz-body-w-full has-size" id="f-status" name="status"><?php foreach (Dokumen::STATUS as $k => $lbl): ?><option value="<?= e($k) ?>"<?= $f['status'] === $k ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?></select><?= chevron_select() ?></div></div>
</div>
<label class="panel-main__form-section-label"><input type="checkbox" name="bisa_unduh" value="1"<?= $f['bisa_unduh'] ? ' checked' : '' ?>> Bisa diunduh</label>
</div></section>
<?php
}

// Bagian form: unggah cover dan PDF; $cover/$pdf = path file saat ini (kosong saat tambah)
function bagianBerkas($cover, $pdf) {
?>
<section aria-labelledby="form-berkas"><div class="panel-main__form-section-box--items-center-flex-gap-11px"><h2 id="form-berkas" class="panel-main__form-section-heading">Berkas</h2></div>
<div class="panel-main__form-section-box--mt-3-5-gap-3-5-flex-col">
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-cover">Sampul (opsional, JPG/PNG/WebP maks. 2 MB)</label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-cover" name="cover" type="file" accept="image/png,image/jpeg,image/webp"><?php if ($cover): ?><p class="panel-main__form-section-paragraph--text-muted-foreground">Sampul saat ini: <?= e(basename($cover)) ?>. Pilih file baru untuk mengganti.</p><?php endif; ?></div>
<div class="panel-main__form-section-box--flex-col-flex-gap-7px"><label data-slot="label" class="label label--text-hz-label" for="f-pdf">Berkas PDF<?= $pdf ? ' (opsional, maks. 10 MB)' : ' (wajib, maks. 10 MB)' ?></label><input data-slot="input" class="input input--text-hz-body-h-42px" id="f-pdf" name="pdf" type="file" accept="application/pdf,.pdf"<?= $pdf ? '' : ' required' ?>><?php if ($pdf): ?><p class="panel-main__form-section-paragraph--text-muted-foreground">PDF saat ini: <?= e(basename($pdf)) ?>. Pilih file baru untuk mengganti.</p><?php endif; ?></div>
</div></section>
<?php
}

// Kotak pesan flash
function tampilPesan($pesan) {
    if (!$pesan) return '';
    $kelas = $pesan[0] === 'ok' ? 'panel-main__text--text-success' : 'panel-main__text--text-destructive';
    return '<p role="' . ($pesan[0] === 'ok' ? 'status' : 'alert') . '" class="panel-main__paragraph ' . $kelas . '">' . e($pesan[1]) . '</p>';
}

$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$halamanAktif = 'koleksi';

// ======================= HALAMAN UBAH =======================
if ($slug !== '') {
    $d = new Dokumen($db);
    if (!$d->readBySlug($slug)) {
        flash('err', 'Dokumen tidak ditemukan.');
        header('Location: ' . $b);
        exit();
    }
    // Nilai form: dari DB, atau isian terakhir bila validasi gagal
    $f = [
        'judul' => $d->judul, 'penulis' => $d->penulis, 'jenis' => $d->jenis, 'prodi' => $d->prodi,
        'tahun' => $d->tahun, 'pembimbing' => $d->pembimbing, 'halaman' => $d->halaman, 'akses' => $d->akses,
        'status' => $d->status, 'kata_kunci' => $d->kata_kunci, 'abstrak' => $d->abstrak, 'bisa_unduh' => $d->bisa_unduh,
    ];
    if ($nilai) $f = $nilai;

    $judul = 'Ubah Koleksi';
    $crumb = ['Koleksi', $d->judul];
    $halaman_aktif = $halamanAktif;
    include ROOT_PATH . 'partials/head.php';
    include ROOT_PATH . 'partials/panel_header.php';
    $kIkon = $IK_JENIS[$d->jenis] ?? $IK_JENIS['ebook'];
    $kStatus = $KELAS_STATUS[$d->status] ?? 'success-soft';
    $teksMeta = $d->penulis . ' · ' . $d->prodi . ' · ' . $d->tahun;
?>
<div class="panel-main__box--flex-col-gap-4-flex-lg-gap-22px"><a class="panel-main__link" href="<?= e($b) ?>"><?= ikon($IK_KIRI, 'panel-main__icon') ?>Data Koleksi</a>
<?= tampilPesan($pesan) ?>
<section class="panel-main__section panel-main__section--gap-4-flex-flex-col-p-4-2"><div class="panel-main__section-box--w-24-shrink-0"><div class="panel-main__section-box--bg-card-border"><div class="panel-main__section-box--bg-primary-h-1-5"></div><div class="panel-main__section-box--px-2-py-2-5"><span class="panel-main__section-text--text-hz-badge"><?= e(Dokumen::JENIS[$d->jenis] ?? $d->jenis) ?></span><span class="panel-main__section-text--bg-primary-soft has-size"><?= ikon($kIkon, 'panel-main__section-icon panel-main__section-icon--size-5') ?></span><span class="panel-main__section-text--text-hz-hint"><?= e($d->tahun) ?></span></div></div></div>
<div class="panel-main__section-box--flex-1-min-w-0"><div class="panel-main__section-box--gap-2-items-center"><span data-slot="badge" data-variant="outline" class="badge badge--border-border-text-foreground-h-25px-px-9px group/badge has-size"><?= e(Dokumen::JENIS[$d->jenis] ?? $d->jenis) ?></span><span class="panel-main__section-text--bg-<?= $kStatus ?>"><span aria-hidden="true" class="panel-main__section-text--bg-current has-size"></span><?= e(Dokumen::STATUS[$d->status] ?? $d->status) ?></span></div>
<h1 class="panel-main__section-title"><?= e($d->judul) ?></h1>
<p class="panel-main__section-paragraph--text-hz-sm-text-muted-foreground-lg-text-hz-body"><?= e($teksMeta) ?></p>
<div class="panel-main__section-box--mt-2-gap-2"><a data-slot="button" data-variant="outline" data-size="sm" class="button button--rounded-md-border-input-bg-card-text-hz-xs-medium group/button has-size" href="<?= BASE_URL ?>dokumen.php?id=<?= e(urlencode($d->slug)) ?>"><?= ikon($IK_BUKA, 'button__icon button__icon--size-4') ?>Buka di situs</a>
<form method="post" action="<?= e($b) ?>" data-confirm="Hapus dokumen ini beserta file-nya?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $d->id ?>"><button data-slot="button" data-variant="outline" data-size="sm" class="button button--border-destructive-40-text-destructive-rounded-md-bg-card group/button has-size" type="submit"><?= ikon($IK_HAPUS, 'button__icon button__icon--size-4') ?><span>Hapus</span></button></form></div></div></section>
<div class="panel-main__box--grid-cols-2-gap-3-grid-lg-grid-cols-4"><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-hz-stat"><?= angka($d->dibaca) ?></span><span class="panel-main__text--text-hz-xs">Dilihat</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-hz-stat"><?= angka($d->unduhan) ?></span><span class="panel-main__text--text-hz-xs">Unduhan</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-hz-stat"><?= angka($d->halaman) ?></span><span class="panel-main__text--text-hz-xs">Halaman</span></div></div>
<form method="post" action="<?= e($b) ?>?slug=<?= e(urlencode($d->slug)) ?>" enctype="multipart/form-data" novalidate class="panel-main__form"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $d->id ?>">
<?php bagianIsian($f, 'Ubah metadata'); bagianBerkas($d->cover, $d->file_pdf); ?>
<div class="panel-main__form-box panel-main__form-box--justify-between"><a data-slot="button" data-variant="secondary" data-size="default" class="button button--bg-muted-text-primary-px-5-text-hz-body-medium group/button has-size" href="<?= e($b) ?>">Kembali</a><button data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" type="submit">Simpan perubahan</button></div>
</form>
</div>
<?php
    include ROOT_PATH . 'partials/panel_footer.php';
    exit();
}

// ======================= HALAMAN TAMBAH =======================
if (($_GET['aksi'] ?? '') === 'tambah') {
    $f = $nilai ?: [
        'judul' => '', 'penulis' => '', 'jenis' => 'ebook', 'prodi' => '', 'tahun' => date('Y'), 'pembimbing' => '',
        'halaman' => '', 'akses' => 'publik-abstrak', 'status' => 'terbit', 'kata_kunci' => '', 'abstrak' => '', 'bisa_unduh' => 1,
    ];
    $judul = 'Unggah Ebook';
    $crumb = ['Koleksi', 'Unggah Ebook'];
    $halaman_aktif = $halamanAktif;
    include ROOT_PATH . 'partials/head.php';
    include ROOT_PATH . 'partials/panel_header.php';
?>
<div class="panel-main__box--flex-col-gap-4-flex-lg-gap-22px"><header class="panel-main__header"><h1 class="panel-main__header-title">Unggah Ebook</h1><div class="panel-main__header-box panel-main__header-box--flex-col-gap-3"><p class="panel-main__header-paragraph panel-main__header-paragraph--max-w-560px">Isi data dokumen, lalu unggah sampul (opsional) dan berkas PDF.</p></div></header>
<?= tampilPesan($pesan) ?>
<form method="post" action="<?= e($b) ?>?aksi=tambah" enctype="multipart/form-data" novalidate class="panel-main__form"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<?php bagianIsian($f, 'Identitas dokumen'); bagianBerkas(null, null); ?>
<div class="panel-main__form-box panel-main__form-box--justify-between"><a data-slot="button" data-variant="secondary" data-size="default" class="button button--bg-muted-text-primary-px-5-text-hz-body-medium group/button has-size" href="<?= e($b) ?>">Batal</a><button data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" type="submit">Unggah</button></div>
</form>
</div>
<?php
    include ROOT_PATH . 'partials/panel_footer.php';
    exit();
}

// ======================= HALAMAN DAFTAR =======================
$q = trim((string) ($_GET['q'] ?? ''));
$fJenis = (string) ($_GET['jenis'] ?? '');
$fStatus = (string) ($_GET['status'] ?? '');
if (!isset(Dokumen::JENIS[$fJenis])) $fJenis = '';
if (!isset(Dokumen::STATUS[$fStatus])) $fStatus = '';
$perHal = 8;
$hal = max(1, (int) ($_GET['hal'] ?? 1));

if ($q !== '' || $fJenis !== '' || $fStatus !== '') {
    $hasil = $dokumen->cari($q, $fJenis ?: null, $fStatus ?: null, false)->fetchAll(PDO::FETCH_ASSOC);
    $total = count($hasil);
} else {
    $total = $dokumen->hitungPanel();
}
$jmlHal = max(1, (int) ceil($total / $perHal));
$hal = min($hal, $jmlHal);
$mulai = ($hal - 1) * $perHal;
$baris = isset($hasil) ? array_slice($hasil, $mulai, $perHal) : $dokumen->readPanel($mulai, $perHal)->fetchAll(PDO::FETCH_ASSOC);

// Angka ringkasan (seluruh DB)
$jmlSemua = $dokumen->hitungPanel();
$jmlStatus = [];
foreach (array_keys(Dokumen::STATUS) as $s) $jmlStatus[$s] = $dokumen->hitung(null, $s);

// URL daftar dengan parameter filter
function urlDaftar($b, $ubah = []) {
    global $q, $fJenis, $fStatus, $hal;
    $p = array_merge(['q' => $q, 'jenis' => $fJenis, 'status' => $fStatus, 'hal' => $hal], $ubah);
    $p = array_filter($p, fn($v) => $v !== '' && $v !== null && $v !== 1);
    return $b . ($p ? '?' . http_build_query($p) : '');
}

$judul = 'Data Koleksi';
$crumb = ['Koleksi', 'Data Koleksi'];
$halaman_aktif = $halamanAktif;
include ROOT_PATH . 'partials/head.php';
include ROOT_PATH . 'partials/panel_header.php';

$tombol = 'button button--size-8-text-foreground-border-transparent-rounded-sm group/button has-size';
$tab = [['', 'Semua', $jmlSemua], ['terbit', 'Terbit', $jmlStatus['terbit']], ['menunggu', 'Menunggu', $jmlStatus['menunggu']], ['diturunkan', 'Diturunkan', $jmlStatus['diturunkan']]];
?>
<div class="panel-main__box--flex-col-gap-4-flex-lg-gap-22px"><header class="panel-main__header"><h1 class="panel-main__header-title">Data Koleksi</h1><div class="panel-main__header-box panel-main__header-box--flex-col-gap-3"><p class="panel-main__header-paragraph panel-main__header-paragraph--max-w-560px">Semua dokumen di repositori beserta statusnya. Buka detail untuk mengubah metadata atau menghapus dokumen.</p><div class="panel-main__header-box panel-main__header-box--gap-2-max-lg-flex-1"><a data-slot="button" data-variant="default" data-size="default" class="button button--bg-primary-text-primary-foreground-px-5-text-hz-body-medium-3 group/button has-size" href="<?= e($b) ?>?aksi=tambah"><?= ikon($IK_UNGGAH, 'button__icon button__icon--size-4') ?><span>Unggah ebook</span></a></div></div></header>
<?= tampilPesan($pesan) ?>
<div class="panel-main__box--grid-cols-2-gap-3-grid-lg-grid-cols-4"><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-hz-stat"><?= angka($jmlSemua) ?></span><span class="panel-main__text--text-hz-xs">Total dokumen</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-success"><?= angka($jmlStatus['terbit']) ?></span><span class="panel-main__text--text-hz-xs">Terbit</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-warning"><?= angka($jmlStatus['menunggu']) ?></span><span class="panel-main__text--text-hz-xs">Menunggu verifikasi</span></div><div class="panel-main__box--gap-0-5-py-4-border-rounded-xl"><span class="panel-main__text--text-destructive"><?= angka($jmlStatus['diturunkan']) ?></span><span class="panel-main__text--text-hz-xs">Diturunkan</span></div></div>
<div dir="ltr" data-orientation="horizontal" data-slot="tabs" class="tabs tabs--gap-2 group/tabs"><nav data-slot="tabs-list" class="tabs-list" aria-label="Filter status koleksi">
<?php foreach ($tab as [$kode, $nama, $n]): $aktif = ($fStatus === $kode); ?>
<a data-slot="tabs-trigger" class="tabs-trigger has-size" data-state="<?= $aktif ? 'active' : 'inactive' ?>"<?= $aktif ? ' aria-current="page"' : '' ?> href="<?= e(urlDaftar($b, ['status' => $kode, 'hal' => 1])) ?>"><?= e($nama) ?> (<?= angka($n) ?>)</a>
<?php endforeach; ?>
</nav></div>
<div class="panel-main__box--hidden-overflow-hidden-border-rounded-xl"><form method="get" action="<?= e($b) ?>" class="panel-main__box--border-b-gap-2-px-5-items-center"><input type="hidden" name="status" value="<?= e($fStatus) ?>"><div role="search" class="panel-main__box--min-w-0-shrink-0-relative-input-lg-h-42px"><input data-slot="input" class="input input--pr-10-text-hz-body-lg-px-4" name="q" aria-label="Cari judul atau penulis" placeholder="Cari judul atau penulis…" type="search" value="<?= e($q) ?>"><?= ikon($IK_CARI, 'panel-main__icon panel-main__icon--translate-y-1-2-absolute-pointer-events-none-right-3-5') ?></div>
<div class="select-bungkus"><select name="jenis" data-slot="select-trigger" data-size="default" class="select-trigger select-trigger--px-3-rounded-sm-text-hz-sm-w-180px has-size" aria-label="Jenis koleksi" data-auto-submit><option value="">Semua jenis</option><?php foreach (Dokumen::JENIS as $k => $lbl): ?><option value="<?= e($k) ?>"<?= $fJenis === $k ? ' selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?></select><?= chevron_select() ?></div></form>
<table class="panel-main__table"><colgroup><col><col class="col-w-96"><col class="col-w-132"><col class="col-w-56"><col class="col-w-176"><col class="col-w-68"><col class="col-w-52"></colgroup>
<thead><tr class="panel-main__table-row panel-main__table-row--text-hz-overline-text-muted-foreground-uppercase-h-41px"><th class="panel-main__table-head-cell--pl-5-font-inherit">Dokumen</th><th class="panel-main__table-head-cell--font-inherit">Jenis</th><th class="panel-main__table-head-cell--font-inherit">Prodi</th><th class="panel-main__table-head-cell--font-inherit">Tahun</th><th class="panel-main__table-head-cell--font-inherit">Status</th><th class="panel-main__table-head-cell--text-right-font-inherit">Dibaca</th><th class="panel-main__table-head-cell--pr-5-text-right"><span class="panel-main__table-text--sr-only">Aksi</span></th></tr></thead>
<tbody>
<?php foreach ($baris as $r): $ks = $KELAS_STATUS[$r['status']] ?? 'success-soft'; $u = $b . '?slug=' . urlencode($r['slug']); ?>
<tr class="panel-main__table-row panel-main__table-row--align-middle-h-72px-last-border-b-0"><td class="panel-main__table-cell--pl-5-pr-4"><a class="panel-main__table-link panel-main__table-link--line-clamp-2" href="<?= e($u) ?>"><?= e($r['judul']) ?></a><p class="panel-main__table-paragraph--truncate"><?= e($r['penulis']) ?> · <?= e(Dokumen::AKSES[$r['akses']] ?? $r['akses']) ?></p></td><td class="panel-main__table-cell--text-hz-sm"><?= e(Dokumen::JENIS[$r['jenis']] ?? $r['jenis']) ?></td><td class="panel-main__table-cell--truncate-pr-3"><?= e($r['prodi']) ?></td><td class="panel-main__table-cell--text-hz-sm"><?= e($r['tahun']) ?></td><td><span class="panel-main__table-text--bg-<?= $ks ?>"><span aria-hidden="true" class="panel-main__table-text--bg-current has-size"></span><?= e(Dokumen::STATUS[$r['status']] ?? $r['status']) ?></span></td><td class="panel-main__table-cell--tabular-nums-text-right"><?= e($r['dibaca']) ?></td>
<td class="panel-main__table-cell--pr-3-5-text-right"><a data-slot="button" data-variant="ghost" data-size="icon-sm" class="<?= $tombol ?>" href="<?= e($u) ?>" aria-label="Ubah <?= e($r['judul']) ?>"><?= ikon($IK_UBAH, 'button__icon button__icon--size-4') ?></a></td></tr>
<?php endforeach; ?>
<?php if (!$baris): ?><tr class="panel-main__table-row panel-main__table-row--align-middle-h-72px-last-border-b-0"><td colspan="7" class="panel-main__table-cell--pl-5-pr-4">Tidak ada dokumen yang cocok.</td></tr><?php endif; ?>
</tbody></table>
<div class="panel-main__box--border-t-h-14-px-5-items-center"><p class="panel-main__paragraph panel-main__paragraph--text-hz-xs"><?= $total ? 'Menampilkan ' . ($mulai + 1) . '–' . ($mulai + count($baris)) . ' dari ' . angka($total) . ' dokumen' : '0 dokumen' ?></p>
<?php if ($jmlHal > 1): ?><nav aria-label="Halaman" class="paginasi"><?php if ($hal > 1): ?><a data-slot="button" data-variant="ghost" data-size="icon-sm" class="<?= $tombol ?>" href="<?= e(urlDaftar($b, ['hal' => $hal - 1])) ?>" aria-label="Halaman sebelumnya"><?= ikon($IK_KIRI, 'button__icon button__icon--size-4') ?></a><?php endif; ?><span class="panel-main__paragraph panel-main__paragraph--text-hz-xs">Hal. <?= $hal ?> / <?= $jmlHal ?></span><?php if ($hal < $jmlHal): ?><a data-slot="button" data-variant="ghost" data-size="icon-sm" class="<?= $tombol ?>" href="<?= e(urlDaftar($b, ['hal' => $hal + 1])) ?>" aria-label="Halaman berikutnya"><?= ikon($IK_KANAN, 'button__icon button__icon--size-4') ?></a><?php endif; ?></nav><?php endif; ?></div></div>
<div class="panel-main__box--gap-3-flex-col-flex-lg-hidden"><form method="get" action="<?= e($b) ?>" class="panel-main__box--gap-2-flex"><input type="hidden" name="status" value="<?= e($fStatus) ?>"><input type="hidden" name="jenis" value="<?= e($fJenis) ?>"><div role="search" class="panel-main__box--flex-1-min-w-0-relative"><input data-slot="input" class="input input--pr-10-text-hz-body-lg-px-4" name="q" aria-label="Cari judul atau penulis" placeholder="Cari judul atau penulis…" type="search" value="<?= e($q) ?>"><?= ikon($IK_CARI, 'panel-main__icon panel-main__icon--translate-y-1-2-absolute-pointer-events-none-right-3-5') ?></div></form>
<ul class="panel-main__list">
<?php foreach ($baris as $r): $ks = $KELAS_STATUS[$r['status']] ?? 'success-soft'; ?>
<li class="panel-main__list-item panel-main__list-item--gap-3-p-3-relative"><span aria-hidden="true" class="panel-main__list-text--bg-primary-soft"><?= ikon($IK_JENIS[$r['jenis']] ?? $IK_JENIS['ebook'], 'panel-main__list-icon panel-main__list-icon--size-6') ?></span><div class="panel-main__list-box--flex-1"><a data-stretched="true" class="panel-main__list-link panel-main__list-link--line-clamp-2" href="<?= e($b . '?slug=' . urlencode($r['slug'])) ?>"><?= e($r['judul']) ?></a><p class="panel-main__list-paragraph--text-hz-hint"><?= e(Dokumen::JENIS[$r['jenis']] ?? $r['jenis']) ?> · <?= e($r['prodi']) ?> · <?= e($r['tahun']) ?> · <?= e($r['dibaca']) ?> dibaca</p><span class="panel-main__list-text--bg-<?= $ks ?>"><span aria-hidden="true" class="panel-main__list-text--bg-current has-size"></span><?= e(Dokumen::STATUS[$r['status']] ?? $r['status']) ?></span></div></li>
<?php endforeach; ?>
</ul>
<p class="panel-main__paragraph panel-main__paragraph--text-center"><?= angka($total) ?> dokumen<?= $jmlHal > 1 ? ' · hal. ' . $hal . '/' . $jmlHal : '' ?></p>
<?php if ($jmlHal > 1): ?><nav aria-label="Halaman" class="panel-main__box--gap-2-flex"><?php if ($hal > 1): ?><a data-slot="button" data-variant="secondary" data-size="default" class="button button--bg-muted-text-primary-px-5-text-hz-body-medium group/button has-size" href="<?= e(urlDaftar($b, ['hal' => $hal - 1])) ?>">Sebelumnya</a><?php endif; ?><?php if ($hal < $jmlHal): ?><a data-slot="button" data-variant="secondary" data-size="default" class="button button--bg-muted-text-primary-px-5-text-hz-body-medium group/button has-size" href="<?= e(urlDaftar($b, ['hal' => $hal + 1])) ?>">Berikutnya</a><?php endif; ?></nav><?php endif; ?></div>
</div>
<?php include ROOT_PATH . 'partials/panel_footer.php'; ?>
