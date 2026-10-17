// Interaksi HoriZone Lite: tema, dropdown, dialog/sheet, tabs. Vanilla JS, tanpa library.
// Memakai atribut data-state (open|closed, active|inactive) yang sama dengan HTML prototipe agar CSS tetap berlaku.
(function () {
  'use strict';
  var html = document.documentElement;

  // ---- Tema: kelas .dark di <html>, disimpan di localStorage 'theme' ----
  var IKON_BULAN = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="@K" aria-hidden="true"><path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401"></path></svg>';
  var IKON_MATAHARI = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="@K" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>';

  var KELAS_IKON = 'button__icon button__icon--size-5 has-size';

  function simpanTema(t) {
    try { localStorage.setItem('theme', t); } catch (e) {}
  }

  function terapkanTema(gelap) {
    html.classList.toggle('dark', gelap);
    html.style.colorScheme = gelap ? 'dark' : 'light';
    document.querySelectorAll('[data-tema]').forEach(function (b) {
      var label = gelap ? 'Ganti ke mode terang' : 'Ganti ke mode gelap';
      var ikon = (gelap ? IKON_MATAHARI : IKON_BULAN).replace('@K', b.getAttribute('data-tema-ikon') || KELAS_IKON);
      // [data-tema-teks] = item menu berlabel (menu HP): ikon + teks aksi; selain itu tombol ikon saja
      if (b.hasAttribute('data-tema-teks')) {
        b.innerHTML = ikon + (gelap ? 'Tema terang' : 'Tema gelap');
      } else {
        b.setAttribute('aria-label', label);
        b.setAttribute('title', label);
        b.innerHTML = ikon;
      }
    });
  }

  terapkanTema(html.classList.contains('dark'));
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-tema]');
    if (!b) return;
    var gelap = !html.classList.contains('dark');
    terapkanTema(gelap);
    simpanTema(gelap ? 'dark' : 'light');
  });

  // ---- Dropdown: tombol [data-slot=dropdown-menu-trigger] membuka [role=menu] di dalam pembungkus yang sama ----
  function tutupDropdown(trigger) {
    var menu = document.getElementById(trigger.getAttribute('aria-controls'));
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('data-state', 'closed');
    if (menu) { menu.setAttribute('data-state', 'closed'); menu.hidden = true; }
  }

  function tutupSemuaDropdown(kecuali) {
    document.querySelectorAll('[data-slot="dropdown-menu-trigger"][aria-expanded="true"]').forEach(function (t) {
      if (t !== kecuali) tutupDropdown(t);
    });
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-slot="dropdown-menu-trigger"]');
    tutupSemuaDropdown(trigger);
    if (!trigger) return;
    var menu = document.getElementById(trigger.getAttribute('aria-controls'));
    if (!menu) return;
    var buka = trigger.getAttribute('aria-expanded') !== 'true';
    trigger.setAttribute('aria-expanded', String(buka));
    trigger.setAttribute('data-state', buka ? 'open' : 'closed');
    menu.setAttribute('data-state', buka ? 'open' : 'closed');
    menu.hidden = !buka;
  });

  // ---- Dialog / sheet: pemicu [data-buka=id]; lapisan (overlay + isi) = elemen [data-lapisan=id] ----
  var pemicuTerakhir = null;

  function lapisan(id) {
    return document.querySelectorAll('[data-lapisan="' + id + '"]');
  }

  function bukaLapisan(id, pemicu) {
    var daftar = lapisan(id);
    if (!daftar.length) return;
    pemicuTerakhir = pemicu || null;
    daftar.forEach(function (el) {
      el.hidden = false;
      el.setAttribute('data-state', 'open');
    });
    if (pemicu) pemicu.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    var isi = document.getElementById(id);
    if (isi) isi.focus();
  }

  function tutupLapisan(id) {
    var daftar = lapisan(id);
    daftar.forEach(function (el) { el.setAttribute('data-state', 'closed'); });
    // beri waktu animasi keluar sebelum disembunyikan
    setTimeout(function () {
      daftar.forEach(function (el) { if (el.getAttribute('data-state') === 'closed') el.hidden = true; });
    }, 200);
    document.body.style.overflow = '';
    document.querySelectorAll('[data-buka="' + id + '"]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    if (pemicuTerakhir) { pemicuTerakhir.focus(); pemicuTerakhir = null; }
  }

  function idTerbuka() {
    var el = document.querySelector('[data-lapisan][data-state="open"]');
    return el ? el.getAttribute('data-lapisan') : null;
  }

  document.addEventListener('click', function (e) {
    var pemicu = e.target.closest('[data-buka]');
    if (pemicu) { bukaLapisan(pemicu.getAttribute('data-buka'), pemicu); return; }
    var tutup = e.target.closest('[data-tutup]');
    if (tutup) {
      var induk = tutup.closest('[data-lapisan]');
      if (induk) tutupLapisan(induk.getAttribute('data-lapisan'));
      return;
    }
    // klik overlay (lapisan tanpa role=dialog) menutup
    var ov = e.target.closest('[data-lapisan]');
    if (ov && ov === e.target && ov.getAttribute('role') !== 'dialog') tutupLapisan(ov.getAttribute('data-lapisan'));
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var id = idTerbuka();
    if (id) { tutupLapisan(id); return; }
    // dialog dirender server: Esc = ikuti tautan Tutup (overlay-nya sudah <a href> yang sama)
    var tt = document.querySelector('[data-tutup-href]');
    if (tt) { window.location.href = tt.getAttribute('data-tutup-href'); return; }
    tutupSemuaDropdown(null);
  });

  // ---- Tabs: [role=tab][aria-controls=idPanel] dalam [role=tablist]; panel [role=tabpanel] ----
  document.addEventListener('click', function (e) {
    var tab = e.target.closest('[role="tab"]');
    if (!tab) return;
    var daftar = tab.closest('[role="tablist"]');
    if (!daftar) return;
    daftar.querySelectorAll('[role="tab"]').forEach(function (t) {
      var aktif = t === tab;
      t.setAttribute('aria-selected', String(aktif));
      t.setAttribute('data-state', aktif ? 'active' : 'inactive');
      var panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) { panel.hidden = !aktif; panel.setAttribute('data-state', aktif ? 'active' : 'inactive'); }
    });
  });

  // ---- Form: [data-confirm="teks"] minta konfirmasi sebelum submit; [data-auto-submit] kirim form saat nilai berubah ----
  document.addEventListener('submit', function (e) {
    var pesan = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (pesan && !window.confirm(pesan)) e.preventDefault();
  });

  document.addEventListener('change', function (e) {
    var el = e.target;
    if (el.hasAttribute && el.hasAttribute('data-auto-submit') && el.form) el.form.submit();
  });

  // ---- Detail dokumen: tombol [data-selengkapnya] membuka/menutup potongan abstrak ----
  document.querySelectorAll('[data-selengkapnya]').forEach(function (tombol) {
    var p = tombol.previousElementSibling;
    if (!p) return;
    tombol.addEventListener('click', function () {
      var buka = tombol.getAttribute('aria-expanded') !== 'true';
      p.style.webkitLineClamp = buka ? 'unset' : '';
      tombol.setAttribute('aria-expanded', String(buka));
      tombol.textContent = buka ? 'Lebih sedikit' : 'Selengkapnya';
    });
  });

  // ---- Masuk: tombol tampilkan/sembunyikan password ----
  var tombolPw = document.querySelector('[aria-label="Tampilkan password"]');
  var isianPw = document.getElementById('password');
  if (tombolPw && isianPw) {
    tombolPw.addEventListener('click', function () {
      var tampil = isianPw.type === 'password';
      isianPw.type = tampil ? 'text' : 'password';
      tombolPw.setAttribute('aria-pressed', tampil ? 'true' : 'false');
    });
  }
})();
