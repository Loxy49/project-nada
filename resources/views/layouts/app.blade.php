<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NADA')</title>
    {{-- Pasang tema sebelum halaman digambar supaya tidak berkedip putih saat mode gelap.
         Pilihan disimpan per browser; kalau belum pernah memilih, ikuti pengaturan perangkat. --}}
    <script>
        (function () {
            var tema = null;
            try { tema = localStorage.getItem('nada-tema'); } catch (e) {}
            if (tema !== 'light' && tema !== 'dark') {
                tema = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', tema);
        })();
    </script>
    <style>
        :root {
            --sage: #84a98c;
            --sage-dark: #5a7a62;
            --cream: #fefdf8;
            --white: #ffffff;
            --text: #4b5563;
            --mustard: #d4a017;
            --border: #e5e4e2;
            --muted: #6b7280;
            --subtle: #9ca3af;
            --hover: #f4f3ef;
            --active: #d3e6d6;
            --success-bg: #E7F0E8;
            --success-text: #3f6149;
            --error-bg: #FBEAEA;
            --error-text: #B91C1C;
        }
        /* ===== Mode gelap: --white = permukaan kartu, --cream = latar halaman ===== */
        html[data-theme="dark"] {
            color-scheme: dark;
            --sage-dark: #a7c4ad;
            --cream: #141816;
            --white: #1c211e;
            --text: #e4e7e3;
            --border: #313833;
            --muted: #a3aba5;
            --subtle: #8a938c;
            --hover: #252b27;
            --active: #2b3b2f;
            --success-bg: rgba(132,169,140,0.16);
            --success-text: #a7c4ad;
            --error-bg: rgba(239,68,68,0.14);
            --error-text: #f87171;
        }
        * { box-sizing: border-box; font-family: -apple-system, 'Segoe UI', sans-serif; }
        body { margin: 0; background: var(--cream); color: var(--text); }
        .layout { display: flex; min-height: 100vh; }

        /* ===== Sidebar putih ===== */
        .sidebar {
            width: 240px; background: var(--white); border-right: 1px solid var(--border);
            flex-shrink: 0; display: flex; flex-direction: column; padding-bottom: 1rem;
            position: sticky; top: 0; height: 100vh; overflow-y: auto; align-self: flex-start;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 17px;
            color: var(--text); padding: 1.25rem 1.25rem 1rem;
        }
        .sidebar-brand { text-decoration: none; }
        .sidebar-brand img { display: block; height: 28px; width: auto; max-width: 60%; }
        .sidebar-section {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--subtle);
            font-weight: 600; padding: 14px 10px 6px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 10px;
            color: var(--text); text-decoration: none; font-size: 14px; margin-bottom: 2px;
        }
        .nav-item svg { flex-shrink: 0; width: 18px; height: 18px; }
        .nav-item:hover { background: var(--hover); }
        .nav-item.active { background: var(--active); color: var(--text); font-weight: 600; }
        .nav-item.active svg { color: var(--sage-dark); }

        .main { flex: 1; min-width: 0; }

        /* ===== Topbar ===== */
        .topbar {
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            background: var(--white); border-bottom: 1px solid var(--border);
            padding: 0.85rem 1.75rem; flex-wrap: wrap; position: sticky; top: 0; z-index: 30;
        }
        .topbar h2 { margin: 0; font-size: 18px; }
        .topbar-right { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .search-box { display: flex; align-items: center; }
        .search-box input { padding: 7px 12px; border: 1px solid var(--border); border-radius: 20px; font-size: 13px; width: 190px; background: var(--cream); color: var(--text); }

        .dropdown { position: relative; display: inline-block; }
        .dropdown-menu {
            display: none; position: absolute; right: 0; top: 42px; background: var(--white); border: 1px solid var(--border);
            border-radius: 10px; min-width: 240px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); z-index: 40; overflow: hidden;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-menu .item { padding: 10px 14px; font-size: 13px; border-bottom: 1px solid var(--border); }
        .dropdown-menu .item:last-child { border-bottom: none; }
        .dropdown-menu a.item { display: block; color: var(--text); text-decoration: none; }
        .dropdown-menu a.item:hover { background: var(--cream); }
        .dropdown-menu .item small { display: block; color: var(--subtle); margin-top: 2px; }
        .btn-logout-item {
            display: flex; align-items: center; gap: 8px; width: 100%; text-align: left;
            background: none; border: none; padding: 10px 14px; font-size: 13px; cursor: pointer;
            color: var(--text); transition: background 0.15s ease, color 0.15s ease;
        }
        .btn-logout-item:hover { background: var(--error-bg); color: var(--error-text); }
        .btn-logout-item svg { width: 15px; height: 15px; flex-shrink: 0; }
        .avatar {
            width: 32px; height: 32px; border-radius: 50%; background: var(--sage); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; cursor: pointer;
        }

        .content-wrap { max-width: 960px; margin: 0 auto; padding: 1.5rem 1.75rem; }
        @media (max-width: 1100px) { .content-wrap { max-width: 100%; } }

        .card { background: var(--white); border-radius: 12px; padding: 1.2rem 1.4rem; border: 1px solid var(--border); margin-bottom: 1rem; }
        .btn { display: inline-block; background: var(--sage); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none; }
        .btn-outline { background: var(--white); color: var(--sage-dark); border: 1px solid var(--sage); }
        .btn-accent { background: var(--mustard); color: #fff; }
        .btn-danger { background: #B91C1C; color: #fff; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 500; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 4px; }
        .field input, .field select, .field textarea { width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: var(--white); color: var(--text); }
        .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 1rem; font-size: 14px; }
        .alert-success { background: var(--success-bg); color: var(--sage-dark); }
        .alert-error { background: var(--error-bg); color: var(--error-text); }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
        .filter-bar select, .filter-bar input { padding: 7px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; background: var(--white); color: var(--text); }

        /* ===== Modal konfirmasi (pengganti confirm() bawaan browser) ===== */
        .confirm-backdrop {
            position: fixed; inset: 0; background: rgba(75,85,99,0.35); backdrop-filter: blur(2px);
            display: none; align-items: center; justify-content: center; z-index: 100; padding: 1rem;
        }
        .confirm-backdrop.show { display: flex; }
        .confirm-modal {
            background: var(--white); border-radius: 18px; padding: 1.75rem; max-width: 340px; width: 100%;
            box-shadow: 0 20px 45px rgba(0,0,0,0.15); text-align: center;
        }
        .confirm-modal p { font-size: 14px; color: var(--text); margin: 0 0 1.35rem; line-height: 1.55; }
        .confirm-modal-actions { display: flex; gap: 10px; justify-content: center; }
        .confirm-btn-cancel {
            background: var(--white); border: 1px solid var(--border); color: var(--muted);
            padding: 9px 18px; border-radius: 10px; font-size: 14px; cursor: pointer;
        }
        .confirm-btn-cancel:hover { background: var(--hover); }
        .confirm-btn-ok {
            background: rgba(132,169,140,0.18); border: 1px solid rgba(132,169,140,0.45); color: var(--sage-dark);
            padding: 9px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;
        }
        .confirm-btn-ok:hover { background: rgba(132,169,140,0.3); }

        /* ===== Ikon, link aksi di tabel, dan label status ===== */
        .ikon { flex-shrink: 0; vertical-align: -3px; }
        .btn .ikon { margin-right: 6px; }
        .aksi {
            display: inline-flex; align-items: center; gap: 5px; font-size: 13px;
            color: var(--sage-dark); text-decoration: none; margin-right: 12px;
        }
        .aksi:last-child { margin-right: 0; }
        .aksi:hover { text-decoration: underline; }
        .aksi-bahaya { color: var(--error-text); }
        .status-kunci {
            display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600;
            color: var(--error-text); background: var(--error-bg); padding: 3px 9px; border-radius: 999px;
        }

        /* ===== Tombol ganti tema (di samping avatar profil) ===== */
        .theme-toggle {
            width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border);
            background: var(--white); color: var(--text); cursor: pointer; padding: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .theme-toggle:hover { background: var(--hover); }
        .theme-toggle svg { width: 16px; height: 16px; }
        .theme-toggle .ikon-matahari { display: none; }
        html[data-theme="dark"] .theme-toggle .ikon-bulan { display: none; }
        html[data-theme="dark"] .theme-toggle .ikon-matahari { display: block; }

        /* ===== Mode gelap untuk warna yang ditulis inline di halaman (style="...") =====
           Halaman-halaman memakai warna terang langsung di atribut style, jadi di sini warna itu
           dipetakan ke token tema. !important dibutuhkan karena style inline selalu menang. */
        html[data-theme="dark"] .sidebar-brand img { filter: brightness(1.7) saturate(0.9); }
        :where(html[data-theme="dark"]) a:not([class]) { color: #93c5fd; }
        html[data-theme="dark"] .content-wrap [style*="background:#fff" i] { background: var(--white) !important; }
        html[data-theme="dark"] .content-wrap :is([style*="background:#f7f6f2" i], [style*="background:#f1f5f9" i], [style*="background:#fbf6e9" i]) { background: var(--hover) !important; }
        html[data-theme="dark"] .content-wrap [style*="background:#fee2e2" i] { background: var(--error-bg) !important; }
        html[data-theme="dark"] .content-wrap [style*="background:#e7f0e8" i] { background: var(--success-bg) !important; }
        html[data-theme="dark"] .content-wrap :is([style*="color:#4b5563" i], [style*="color:#475569" i]) { color: var(--text) !important; }
        html[data-theme="dark"] .content-wrap :is([style*="color:#6b7280" i], [style*="color:#64748b" i]) { color: var(--muted) !important; }
        html[data-theme="dark"] .content-wrap :is([style*="color:#94a3b8" i], [style*="color:#9ca3af" i]) { color: var(--subtle) !important; }
        html[data-theme="dark"] .content-wrap :is([style*="color:#b91c1c" i], [style*="color:#991b1b" i], [style*="color:#dc2626" i]) { color: var(--error-text) !important; }
        html[data-theme="dark"] .content-wrap :is([style*="color:#5a7a62" i], [style*="color:#3f6149" i]) { color: var(--success-text) !important; }
        html[data-theme="dark"] .content-wrap [style*="#1e3a8a" i] { color: #93c5fd !important; border-color: #93c5fd !important; }
        html[data-theme="dark"] .content-wrap :is([style*="#e5e4e2" i], [style*="#e2e8f0" i], [style*="#e5e7eb" i], [style*="#d1d5db" i], [style*="#eef0ee" i]) { border-color: var(--border) !important; }
        html[data-theme="dark"] .content-wrap :is(input, select, textarea) { background-color: var(--white); color: var(--text); border-color: var(--border); }

        /* ===== Responsive (HP/tablet) ===== */
        .sidebar-toggle {
            display: none; background: none; border: none; color: var(--text); font-size: 20px;
            cursor: pointer; padding: 4px 6px; border-radius: 6px; margin-right: 4px;
        }
        @media (max-width: 900px) {
            .sidebar-toggle { display: inline-flex; }
            .sidebar {
                position: fixed; left: 0; top: 0; bottom: 0; z-index: 50; transform: translateX(-100%);
                transition: transform 0.2s ease; box-shadow: 0 0 0 9999px rgba(0,0,0,0); width: 240px;
            }
            .sidebar.show { transform: translateX(0); }
            .sidebar-backdrop {
                display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 45;
            }
            .sidebar-backdrop.show { display: block; }
            .topbar { padding: 0.75rem 1rem; }
            .search-box input { width: 130px; }
            .content-wrap { padding: 1.25rem 1rem; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
           <a href="{{ route('dashboard') }}" class="sidebar-brand" aria-label="NADA - Beranda">
              <img src="{{ asset('images/logo-nada-nav.png') }}" alt="NADA">
           </a>
        <div class="sidebar-scroll">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5a1 1 0 0 0 1 1H9.5v-6h5v6H17.5a1 1 0 0 0 1-1V10"/></svg>
                Dashboard
            </a>
            <a href="{{ route('nota.create') }}" class="nav-item {{ request()->routeIs('nota.create') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
                Buat nota
            </a>
            <a href="{{ route('nota.riwayat') }}" class="nav-item {{ request()->routeIs('nota.riwayat') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                Riwayat
            </a>

            @if(auth()->user()?->isAdmin())
                <div class="sidebar-section">Admin</div>
                <a href="{{ route('kategori.index') }}" class="nav-item {{ request()->routeIs('kategori.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 12.3 12.3 20.6a1.5 1.5 0 0 1-2.1 0l-7-7a1.5 1.5 0 0 1 0-2.1L11.5 3.2a1.5 1.5 0 0 1 1.05-.44H19a1.5 1.5 0 0 1 1.5 1.5v6.94c0 .4-.16.78-.44 1.06Z"/><circle cx="15.5" cy="7.5" r="1"/></svg>
                    Kategori
                </a>
                <a href="{{ route('pengguna.index') }}" class="nav-item {{ request()->routeIs('pengguna.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c.4-3.3 2.8-5.2 6-5.2s5.6 1.9 6 5.2"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6M18 14.9c1.6.6 2.7 2.2 3 5.1"/></svg>
                    Pengguna
                </a>
                <a href="{{ route('pengaturan.index') }}" class="nav-item {{ request()->routeIs('pengaturan.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2.5h9l2.5 2.5v16.5H6z"/><path d="M15 2.5V7h4.5"/><path d="M8.3 12.5h7.4M8.3 16h7.4"/></svg>
                    Template Nota
                </a>
                <a href="{{ route('periode.index') }}" class="nav-item {{ request()->routeIs('periode.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9.5" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    Kunci Periode
                </a>
                <a href="{{ route('lognota.index') }}" class="nav-item {{ request()->routeIs('lognota.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3.5" width="14" height="17" rx="1.5"/><path d="M9 3.5v-1h6v1"/><path d="M8.5 9.5h7M8.5 13h7M8.5 16.5h4"/></svg>
                    Log Nota
                </a>
            @endif
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

    <div class="main">
        <div class="topbar">
            <div style="display:flex; align-items:center;">
                <button type="button" class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Buka menu"><x-ikon nama="menu" :ukuran="20" /></button>
                <h2>@yield('title', 'Dashboard')</h2>
            </div>

            <div class="topbar-right">
                <form class="search-box" method="GET" action="{{ route('nota.riwayat') }}">
                    <input type="text" name="cari" placeholder="Cari no. nota / kasir..." value="{{ request('cari') }}">
                </form>

                <button type="button" class="theme-toggle" id="theme-toggle" onclick="gantiTema()" aria-label="Ganti tema terang/gelap" title="Ganti tema terang/gelap">
                    <svg class="ikon-bulan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11Z"/></svg>
                    <svg class="ikon-matahari" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
                </button>

                <div class="dropdown">
                    <div class="avatar" onclick="document.getElementById('profile-menu').classList.toggle('show')">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(strrchr(auth()->user()->name, ' ') ?: '', 1, 1)) }}
                    </div>
                    <div class="dropdown-menu" id="profile-menu">
                        <div class="item" style="color:var(--muted);">{{ ucfirst(auth()->user()->role) }} &middot; {{ auth()->user()->email }}</div>
                        <a href="{{ route('profile.edit') }}" class="item">Edit profil</a>
                        <div class="item" style="padding:0;">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn-logout-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-wrap">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

<div class="confirm-backdrop" id="confirm-backdrop">
    <div class="confirm-modal">
        <p id="confirm-pesan">Apakah Anda yakin ingin melanjutkan?</p>
        <div class="confirm-modal-actions">
            <button type="button" class="confirm-btn-cancel" id="confirm-batal">Batal</button>
            <button type="button" class="confirm-btn-ok" id="confirm-ok">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
    // Ganti tema terang/gelap dan simpan pilihannya di browser ini.
    // Halaman yang menggambar warnanya sendiri (mis. grafik dashboard) mendengarkan event 'temaberubah'.
    function gantiTema() {
        var tema = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', tema);
        try { localStorage.setItem('nada-tema', tema); } catch (e) {}
        window.dispatchEvent(new CustomEvent('temaberubah', { detail: tema }));
    }

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebar-backdrop').classList.toggle('show');
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('show');
        document.getElementById('sidebar-backdrop').classList.remove('show');
    }

    document.getElementById('sidebar-backdrop').addEventListener('click', closeSidebar);

    document.querySelectorAll('.sidebar .nav-item').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

    let formYangDikonfirmasi = null;

    function konfirmasiHapus(form, pesan) {
        formYangDikonfirmasi = form;
        document.getElementById('confirm-pesan').textContent = pesan;
        document.getElementById('confirm-backdrop').classList.add('show');
        return false; // cegah submit langsung, nunggu klik tombol di modal
    }

    document.getElementById('confirm-batal').addEventListener('click', function () {
        formYangDikonfirmasi = null;
        document.getElementById('confirm-backdrop').classList.remove('show');
    });

    document.getElementById('confirm-ok').addEventListener('click', function () {
        if (formYangDikonfirmasi) formYangDikonfirmasi.submit();
        document.getElementById('confirm-backdrop').classList.remove('show');
    });

    document.getElementById('confirm-backdrop').addEventListener('click', function (e) {
        if (e.target === this) {
            formYangDikonfirmasi = null;
            this.classList.remove('show');
        }
    });
</script>
</body>
</html>