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
        padding-bottom: 70px;
    }

    /* Overlay Halaman / Loader */
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(244, 247, 246, 0.85);
        backdrop-filter: blur(4px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 21474;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease-in-out;
    }

    #page-loader.show {
        opacity: 1;
        pointer-events: auto;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(44, 62, 80, 0.1);
        border-top: 4px solid #2c3e50;
        border-radius: 50%;
        animation: spin 0.7s cubic-bezier(0.5, 0.1, 0.4, 0.9) infinite;
        position: relative;
        z-index: 2147483647;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Layout Container */
    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    /* Navbar Fixed di Atas */
    header {
        background: #2c3e50;
        color: #fff;
        padding: 0.75rem 0;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    header nav {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Logo / Judul Brand di Navbar */
    .nav-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.15rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        color: #fff;
        text-decoration: none;
        transition: opacity 0.2s;
    }

    .nav-brand:hover {
        opacity: 0.9;
    }

    /* Styling Gambar Logo Bulat di Navbar */
    .nav-brand img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.85);
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    header nav ul {
        list-style: none;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    header nav ul li a,
    header nav ul li .drop-btn {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        font-weight: 500;
        padding: 8px 14px;
        border-radius: 6px;
        transition: all 0.2s ease;
        display: block;
        background: transparent;
        border: none;
        cursor: pointer;
        font-family: inherit;
        font-size: 14px;
    }

    header nav ul li a:hover,
    header nav ul li .drop-btn:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
    }

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
        top: calc(100% + 6px);
        left: 0;
        background: #34495e;
        min-width: 210px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        list-style: none;
        flex-direction: column;
        padding: 6px;
        z-index: 100;
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-content li {
        width: 100%;
    }

    .dropdown-content li a {
        padding: 10px 14px;
        font-size: 14px;
        border-radius: 6px;
        color: rgba(255, 255, 255, 0.85);
        text-align: left;
    }

    .dropdown-content li a:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .dropdown.active .dropdown-content {
        display: flex;
    }

    .hamburger {
        display: none;
    }

    /* Tabel Styling Modern */
    .table-wrap {
        overflow-x: auto;
        max-width: 100%;
        border-radius: 8px;
        box-shadow: 0 0 0 1px #eee;
        margin-top: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        text-align: left;
    }

    table th,
    table td {
        border-bottom: 1px solid #edf2f7;
        border-top: none;
        border-left: none;
        border-right: none;
        padding: 12px 16px;
        vertical-align: middle;
        white-space: nowrap;
        font-size: 14px;
    }

    table th {
        background-color: #2c3e50;
        color: white;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    table tbody tr {
        transition: background-color 0.15s ease;
    }

    table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 14px;
        color: #2c3e50;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        transition: all 0.2s;
        background: #fff;
    }

    input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #2c3e50;
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.12);
    }

    button {
        background: #27ae60;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s, transform 0.1s;
    }

    button:hover {
        background: #219150;
    }

    button:active {
        transform: scale(0.98);
    }

    /* Dashboard Cards */
    .dashboard-cards {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .info-card {
        border: 1px solid #e2e8f0;
        padding: 20px;
        border-radius: 12px;
        min-width: 220px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }

    .info-card.total {
        border: 2px solid #2c3e50;
        background: #f8fafc;
    }

    .info-card h3,
    .info-card h4 {
        margin: 0 0 8px 0;
        color: #2c3e50;
    }

    .info-card p {
        margin: 4px 0;
        color: #4a5568;
    }

    .target-wrapper {
        display: flex;
        gap: 16px;
        margin-bottom: 30px;
        overflow-x: auto;
        padding-bottom: 12px;
    }

    .target-wrapper::-webkit-scrollbar {
        height: 6px;
    }
    .target-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .target-card {
        border: 1px solid #e2e8f0;
        padding: 18px;
        border-radius: 12px;
        width: 280px;
        flex-shrink: 0;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease;
    }

    .target-card:hover {
        transform: translateY(-2px);
    }

    .target-card.over {
        border-color: #feb2b2;
        background: #fff5f5;
    }

    .target-card.normal {
        border-color: #cbd5e1;
        background: #2c3e50;
        color: #fff;
    }

    .progress-box {
        width: 100%;
        height: 10px;
        background: rgba(0,0,0,0.08);
        border-radius: 999px;
        overflow: hidden;
        margin: 10px 0 12px 0;
    }

    .progress-bar {
        height: 100%;
        background: #27ae60;
        border-radius: 999px;
        transition: width 0.4s ease;
    }

    .progress-bar.over {
        background: #e74c3c;
    }

    .small-text {
        font-size: 0.85em;
        opacity: 0.9;
    }

    .warning-text {
        color: #e74c3c;
        font-weight: 600;
        font-size: 0.9em;
    }

    .edit-cell {
        background: #fffdf5;
        outline: none;
        cursor: pointer;
        transition: background 0.2s;
    }

    .edit-cell:hover {
        background: #fffbeb;
    }

    .inline-input,
    .inline-select {
        width: 100%;
        min-width: 120px;
        box-sizing: border-box;
        padding: 6px 10px;
        border: 1px solid #2c3e50;
        border-radius: 6px;
        font-size: 13px;
    }

    .locked-text {
        color: #a0aec0;
    }

    .cell-saving { background: #e0f2fe !important; }
    .cell-success { background: #dcfce7 !important; }
    .cell-error { background: #fee2e2 !important; }

    .dashboard-cards.dashboard-scroll {
        max-height: 260px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 6px;
    }

    .dashboard-cards.dashboard-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .dashboard-cards.dashboard-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    /* Overview Riwayat */
    .overview-riwayat-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        padding: 16px 20px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    }

    .overview-riwayat-box h3 {
        margin: 0;
        color: #2c3e50;
        font-size: 15px;
        font-weight: 600;
    }

    .overview-riwayat-box a {
        background: #2c3e50;
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
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
            padding-top: 75px;
            padding-bottom: 80px;
        }

        .container {
            width: 95%;
            padding: 14px;
            border-radius: 10px;
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
            height: 65px;
            margin: 0;
            padding: 0 4px;
            border-radius: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1);
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
            font-size: 11px;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            color: rgba(255, 255, 255, 0.9);
            border-radius: 0;
        }

        header nav ul#nav-menu li a:hover,
        header nav ul#nav-menu li .drop-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .mobile-icon {
            display: block;
            font-size: 19px;
            line-height: 1;
        }

        /* Dropdown di HP diubah menjadi Dropup (muncul ke atas dari Tab Bar) */
        .dropdown.active .dropdown-content {
            display: flex !important;
        }

        .dropdown-content {
            position: fixed !important;
            bottom: 65px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            top: auto !important;
            width: 92% !important;
            max-width: 340px;
            background: #34495e !important;
            border-radius: 12px 12px 0 0 !important;
            box-shadow: 0 -6px 20px rgba(0, 0, 0, 0.25) !important;
            padding: 8px 0 !important;
            z-index: 10000 !important;
        }

        .dropdown-content li {
            width: 100%;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .dropdown-content li:last-child {
            border-bottom: none;
        }

        .dropdown-content li a {
            padding: 12px 16px !important;
            font-size: 13.5px !important;
            text-align: center !important;
            justify-content: center !important;
            display: flex !important;
        }

        .dashboard-cards {
            flex-direction: column;
            gap: 12px;
        }

        .info-card {
            min-width: 100%;
        }

        table th,
        table td {
            padding: 10px 12px;
            font-size: 13px;
        }

        .dashboard-cards.dashboard-scroll {
            max-height: 240px;
            flex-direction: column;
            flex-wrap: nowrap;
        }

        .dashboard-cards.dashboard-scroll .info-card {
            width: 100%;
            min-width: 100%;
        }

        .overview-riwayat-box {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            padding: 14px;
            text-align: center;
        }

        .overview-riwayat-box h3 {
            font-size: 14px;
        }

        .overview-riwayat-box a {
            width: 100%;
            justify-content: center;
            padding: 10px 16px;
        }
    }
/* Styling Khusus untuk Card Saldo Bersih */
.saldo-card {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: #ffffff;
    padding: 25px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 10px 25px rgba(44, 62, 80, 0.2);
    position: relative;
    overflow: hidden;
}

.saldo-card h3 {
    color: #fff;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.saldo-card .nominal {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

/* Sedikit hiasan dekoratif di pojok kanan */
.saldo-card::after {
    content: "💳";
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 3rem;
    opacity: 0.1;
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