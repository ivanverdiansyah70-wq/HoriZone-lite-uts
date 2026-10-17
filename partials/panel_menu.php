<?php
// Data menu panel per role + helper ikon. Dipakai panel_sidebar.php dan panel_menu_hp.php.
// Ikon lucide = isi <svg> inline (diambil dari HTML prototipe).
if (!function_exists('panel_ikon')) {
    $GLOBALS['IKON_PANEL'] = ($GLOBALS['IKON_PANEL'] ?? []) + [
        'dasbor' => '<rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect>',
        'data-koleksi' => '<path d="m16 6 4 14"></path><path d="M12 6v14"></path><path d="M8 8v12"></path><path d="M4 4v16"></path>',
        'data-pengguna' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><path d="M16 3.128a4 4 0 0 1 0 7.744"></path><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><circle cx="9" cy="7" r="4"></circle>',
        'konten' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path>',
    ];

    // <svg> ikon dengan kelas $kelas
    function panel_ikon($kunci, $kelas) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $kelas . '" aria-hidden="true">'
            . $GLOBALS['IKON_PANEL'][$kunci] . '</svg>';
    }

    // Menu menurut role: admin semua; pustakawan tanpa Pengguna.
    // Elemen tunggal = tautan; elemen ber-'item' = kelompok dengan judul.
    function panel_menu($role) {
        $b = BASE_URL . 'panel/';
        $menu = [
            ['kunci' => 'dasbor', 'label' => 'Dasbor', 'href' => $b . 'dasbor.php', 'ikon' => 'dasbor'],
            ['judul' => 'Koleksi', 'item' => [
                ['kunci' => 'koleksi', 'label' => 'Data Koleksi', 'href' => $b . 'koleksi.php', 'ikon' => 'data-koleksi'],
            ]],
        ];
        if ($role === 'admin') {
            $menu[] = ['judul' => 'Pengguna', 'item' => [
                ['kunci' => 'pengguna', 'label' => 'Data Pengguna', 'href' => $b . 'pengguna.php', 'ikon' => 'data-pengguna'],
            ]];
        }
        $menu[] = ['judul' => 'Situs', 'item' => [
            ['kunci' => 'pengumuman', 'label' => 'Pengumuman', 'href' => $b . 'pengumuman.php', 'ikon' => 'konten'],
        ]];
        return $menu;
    }
}
