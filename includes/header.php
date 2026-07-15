<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasKeuangan Khresmupu</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>includes/KasKeuanganKhresmupu.png">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f7f6;
        color: #333;
        line-height: 1.6;
        padding-top: 90px;
        /* Ruang aman agar konten tidak tertutup fixed header di atas (Desktop) */
        padding-bottom: 70px;
        /* Ruang aman agar konten tidak tertutup bottom tab bar di HP */
    }

    /* Overlay Paling Depan Menutup Segala Layar Putih */
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: #f4f7f6;
        /* Gunakan warna background halaman agar menyatu, atau #ffffff untuk putih total */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 21474;
        /* Nilai z-index maksimum di browser */
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.4s ease-in-out;
    }

    /* Saat aktif, pastikan mutlak menutupi semua elemen lain */
    #page-loader.show {
        opacity: 1;
        pointer-events: auto;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(44, 62, 80, 0.15);
        border-top: 5px solid #2c3e50;
        /* Warna lingkaran loading #2c3e50 */
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        position: relative;
        z-index: 2147483647;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Layout Container */
    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Navbar Fixed di Atas */
    header {
        background: #2c3e50;
        color: #fff;
        padding: 1rem 0;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    header nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Logo / Judul Brand di Navbar */
    .nav-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.2rem;
        font-weight: bold;
        color: #fff;
        text-decoration: none;
    }

    /* Styling Gambar Logo Bulet di Navbar */
    .nav-brand img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.8);
    }

    header nav ul {
        list-style: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    header nav ul li a,
    header nav ul li .drop-btn {
        color: #fff;
        text-decoration: none;
        font-weight: bold;
        padding: 8px 12px;
        border-radius: 4px;
        transition: background 0.2s;
        display: block;
        background: transparent;
        border: none;
        cursor: pointer;
        font-family: inherit;
        font-size: 14px;
    }

    header nav ul li a:hover,
    header nav ul li .drop-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    /* Sembunyikan ikon khusus mobile di mode desktop */
    .mobile-icon {
        display: none;
    }

    /* Dropdown Container */
    .dropdown {
        position: relative;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: #34495e;
        min-width: 200px;
        box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        border-radius: 4px;
        list-style: none;
        flex-direction: column;
        padding: 5px 0;
        z-index: 100;
    }

    .dropdown-content li {
        width: 100%;
    }

    .dropdown-content li a {
        padding: 10px 15px;
        font-size: 14px;
        border-radius: 0;
        color: #fff;
        text-align: left;
    }

    .dropdown-content li a:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    /* Tampilkan dropdown saat aktif via JS */
    .dropdown.active .dropdown-content {
        display: flex;
    }

    /* Tombol Hamburger 3 Bar disembunyikan total */
    .hamburger {
        display: none;
    }

    .table-wrap {
        overflow-x: auto;
        max-width: 100%;
    }

    /* Tabel Styling */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        background: #fff;
    }

    table th,
    table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        vertical-align: top;
        white-space: nowrap;
    }

    table th {
        background-color: #2c3e50;
        color: white;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    button {
        background: #27ae60;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    button:hover {
        background: #219150;
    }

    .dashboard-cards {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .info-card {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 10px;
        min-width: 200px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .info-card.total {
        border: 2px solid #333;
        background: #f5f5f5;
    }

    .info-card h3,
    .info-card h4 {
        margin: 0 0 10px 0;
    }

    .info-card p {
        margin: 5px 0;
    }

    .target-wrapper {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        overflow-x: auto;
        padding-bottom: 10px;
    }

    .target-card {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 10px;
        width: 280px;
        flex-shrink: 0;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .target-card.over {
        border-color: #ff4d4d;
        background: #fff1f1;
    }

    .target-card.normal {
        border-color: #bfe8d7;
        background: #2c3e50;
        color: #fff;
    }

    .progress-box {
        width: 100%;
        height: 12px;
        background: #e0e0e0;
        border-radius: 999px;
        overflow: hidden;
        margin: 8px 0 10px 0;
    }

    .progress-bar {
        height: 100%;
        background: #27ae60;
    }

    .progress-bar.over {
        background: #e74c3c;
    }

    .small-text {
        font-size: 0.85em;
        color: #fff;
    }

    .warning-text {
        color: #d63031;
        font-weight: bold;
        font-size: 0.9em;
    }

    .edit-cell {
        background: #fff8dc;
        outline: none;
        cursor: pointer;
    }

    .edit-cell:hover {
        background: #fff1b8;
    }

    .inline-input,
    .inline-select {
        width: 100%;
        min-width: 120px;
        box-sizing: border-box;
        padding: 6px 8px;
        border: 1px solid #999;
        border-radius: 6px;
        font-size: 14px;
    }

    .locked-text {
        color: #bbb;
    }

    .cell-saving {
        background: #dff9fb !important;
    }

    .cell-success {
        background: #c7f7d4 !important;
    }

    .cell-error {
        background: #ffd6d6 !important;
    }

    /* Mengatur kotak card agar bisa scroll Y dengan max-height di Desktop */
    .dashboard-cards.dashboard-scroll {
        max-height: 250px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 5px;
    }

    /* Styling Utama Overview Riwayat */
    .overview-riwayat-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fdfefe;
        padding: 15px 20px;
        border: 1px solid #e5e8e8;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .overview-riwayat-box h3 {
        margin: 0;
        color: #2c3e50;
        font-size: 16px;
    }

    .overview-riwayat-box a {
        background: #2c3e50;
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
        white-space: nowrap;
    }

    .overview-riwayat-box a:hover {
        background: #1a252f;
    }

    /* =========================================
   MEDIA QUERY & BOTTOM TAB BAR UNTUK HP (<= 768px)
   ========================================= */
    @media screen and (max-width: 768px) {
        body {
            padding-top: 85px;
            /* Jarak aman atas di mobile agar konten tidak mepet/ketimpa */
        }

        .container {
            width: 100%;
            padding: 12px;
        }

        /* Sembunyikan default menu desktop header */
        header nav ul {
            display: none !important;
        }

        /* Ubah navigasi bawah menjadi Bottom Tab Bar Fixed */
        header nav ul#nav-menu {
            display: flex !important;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #2c3e50;
            flex-direction: row;
            justify-content: space-around;
            align-items: center;
            height: 60px;
            margin: 0;
            padding: 0;
            border-radius: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 9999;
        }

        header nav ul#nav-menu li {
            width: auto;
            border: none;
            flex: 1;
            text-align: center;
            position: relative;
        }

        header nav ul#nav-menu li a,
        header nav ul#nav-menu li .drop-btn {
            padding: 6px 2px;
            font-size: 10px;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            color: #fff;
        }

        .mobile-icon {
            display: block;
            font-size: 18px;
            line-height: 1;
        }

        /* Dropdown di HP diubah menjadi Dropup (muncul ke atas dari Tab Bar) */
        .dropdown.active .dropdown-content {
            display: flex !important;
        }

        .dropdown-content {
            position: fixed !important;
            bottom: 60px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            top: auto !important;
            width: 90% !important;
            max-width: 320px;
            background: #34495e !important;
            border-radius: 10px 10px 0 0 !important;
            box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.3) !important;
            padding: 8px 0 !important;
            z-index: 10000 !important;
        }

        .dropdown-content li {
            width: 100%;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dropdown-content li:last-child {
            border-bottom: none;
        }

        .dropdown-content li a {
            padding: 12px 15px !important;
            font-size: 13px !important;
            text-align: center !important;
            justify-content: center !important;
            display: flex !important;
        }

        .dashboard-cards {
            flex-direction: column;
        }

        .info-card {
            min-width: 100%;
        }

        table th,
        table td {
            padding: 8px;
            font-size: 13px;
        }

        .dashboard-cards.dashboard-scroll {
            max-height: 220px;
            flex-direction: column;
            flex-wrap: nowrap;
        }

        .dashboard-cards.dashboard-scroll .info-card {
            width: 100%;
            min-width: 100%;
        }

        .overview-riwayat-box {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 15px;
        }

        .overview-riwayat-box h3 {
            font-size: 14px;
            line-height: 1.4;
        }

        .overview-riwayat-box a {
            width: 100%;
            justify-content: center;
            padding: 10px 16px;
        }
    }
    </style>
</head>

<body>
    <div id="page-loader" class="show">
        <div class="spinner"></div>
    </div>

    <header>
        <div class="container" style="padding-top: 10px; padding-bottom: 10px;">
            <nav>
                <a href="<?php echo $base_url; ?>pages/dashboard.php" class="nav-brand">
                    <img src="<?php echo $base_url; ?>includes/KasKeuanganKhresmupu.png"
                        alt="Logo Kas Keuangan Khresmupu">
                    <span>KasKeuangan Khresmupu</span>
                </a>

                <ul id="nav-menu">
                    <li>
                        <a href="<?php echo $base_url; ?>pages/dashboard.php">
                            <span class="mobile-icon">🏠</span>
                            <span>Beranda</span>
                        </a>
                    </li>

                    <li class="dropdown" id="dropdown-pencatatan">
                        <button class="drop-btn" onclick="toggleDropdown(event, 'dropdown-pencatatan')">
                            <span class="mobile-icon">📝</span>
                            <span>Catat ▾</span>
                        </button>
                        <ul class="dropdown-content">
                            <li><a href="<?php echo $base_url; ?>pages/transaksi/input.php">Catat Transaksi</a></li>
                            <li><a href="<?php echo $base_url; ?>pages/transaksi/tagihan.php">Daftar Tagihan</a></li>
                        </ul>
                    </li>

                    <li class="dropdown" id="dropdown-batasan">
                        <button class="drop-btn" onclick="toggleDropdown(event, 'dropdown-batasan')">
                            <span class="mobile-icon">🎯</span>
                            <span>Alokasi ▾</span>
                        </button>
                        <ul class="dropdown-content">
                            <li><a href="<?php echo $base_url; ?>pages/transaksi/target.php">Target Pengeluaran</a></li>
                            <li><a href="<?php echo $base_url; ?>pages/transaksi/penyisihan_dana.php">Alokasi &
                                    Celengan</a></li>
                        </ul>
                    </li>

                    <li class="dropdown" id="dropdown-pengaturan">
                        <button class="drop-btn" onclick="toggleDropdown(event, 'dropdown-pengaturan')">
                            <span class="mobile-icon">⚙️</span>
                            <span>Atur ▾</span>
                        </button>
                        <ul class="dropdown-content">
                            <li><a href="<?php echo $base_url; ?>pages/transaksi/tambah_kategori.php">Kategori</a></li>
                            <li><a href="<?php echo $base_url; ?>pages/transaksi/tambah_akun.php">Dompet / Rekening</a>
                            </li>
                        </ul>
                    </li>

                    <li>
                        <a href="<?php echo $base_url; ?>pages/tentang.php">
                            <span class="mobile-icon">ℹ️</span>
                            <span>Tentang</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo $base_url; ?>index.php" style="color: #ffcccc;">
                            <span class="mobile-icon">🚪</span>
                            <span>Keluar</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <script>
    // 1. Tangani saat halaman dimuat (Cek apakah dari Refresh / Back-Forward Cache)
    window.addEventListener('pageshow', (event) => {
        const loader = document.getElementById('page-loader');

        // event.persisted bernilai true jika halaman dimuat dari cache (tombol Back/Forward)
        // performance.navigation.type === 1 mendeteksi tombol Refresh
        const isRefresh = performance.getEntriesByType("navigation")[0]?.type === "reload";

        if (event.persisted || isRefresh) {
            // Jika dari Refresh atau Back/Forward: Matikan animasi/loader langsung tanpa jeda
            if (loader) loader.classList.remove('show');
            document.body.classList.add('fade-in');
            document.body.classList.remove('fade-out');
        } else {
            // Jika benar-benar navigasi baru dari halaman lain: Jalankan animasi masuk
            document.body.classList.add('fade-in');
            setTimeout(() => {
                if (loader) loader.classList.remove('show');
            }, 50);
        }
    });

    // 2. Tangkap klik link navigasi untuk jalankan Fade Out dan munculkan loader bersamaan
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.startsWith('#') && link.target !== '_blank' && !link.hasAttribute(
                'onclick')) {
            const targetUrl = link.href;

            // Abaikan link modifier (seperti Ctrl+Klik untuk buka tab baru)
            if (e.ctrlKey || e.shiftKey || e.metaKey || e.altKey) return;

            if (targetUrl.includes(window.location.hostname) || targetUrl.startsWith('/')) {
                e.preventDefault();
                const loader = document.getElementById('page-loader');
                if (loader) loader.classList.add('show');
                document.body.classList.remove('fade-in');
                document.body.classList.add('fade-out');

                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 400); // Cocokkan durasi dengan transition CSS
            }
        }
    });

    // 3. Fungsi Toggle Dropdown / Dropup
    function toggleDropdown(event, dropdownId) {
        event.stopPropagation();
        const dropdown = document.getElementById(dropdownId);
        if (!dropdown) return;

        document.querySelectorAll('.dropdown').forEach(item => {
            if (item.id !== dropdownId) {
                item.classList.remove('active');
            }
        });

        dropdown.classList.toggle('active');
    }

    // 4. Klik di luar dropdown untuk menutupnya kembali
    window.addEventListener('click', () => {
        document.querySelectorAll('.dropdown').forEach(item => {
            item.classList.remove('active');
        });
    });
    </script>
    <main class="container">