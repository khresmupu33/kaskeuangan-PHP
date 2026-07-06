<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Keuangan Kas</title>
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
}

/* Layout Container */
.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Navbar */
header {
    background: #2c3e50;
    color: #fff;
    padding: 1rem 0;
    position: relative;
}

header nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Logo / Judul Brand di Navbar */
.nav-brand {
    font-size: 1.2rem;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
}

header nav ul {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 15px;
}

header nav ul li a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background 0.2s;
    display: block;
}

header nav ul li a:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Tombol Hamburger 3 Bar (Default disembunyikan di Desktop) */
.hamburger {
    display: none;
    flex-direction: column;
    justify-content: space-between;
    width: 30px;
    height: 22px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    z-index: 10;
}

.hamburger span {
    display: block;
    width: 100%;
    height: 3px;
    background: #fff;
    border-radius: 3px;
    transition: all 0.3s ease-in-out;
}

/* Animasi Hamburger ke Huruf 'X' saat Aktif */
.hamburger.active span:nth-child(1) {
    transform: translateY(9.5px) rotate(45deg);
}

.hamburger.active span:nth-child(2) {
    opacity: 0;
}

.hamburger.active span:nth-child(3) {
    transform: translateY(-9.5px) rotate(-45deg);
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
    background-color: #3498db;
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

input, select, textarea {
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.target-card.over {
    border-color: #ff4d4d;
    background: #fff1f1;
}

.target-card.normal {
    border-color: #bfe8d7;
    background: #eefaf5;
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
    color: #555;
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

/* =========================================
   MEDIA QUERY & HAMBURGER MENU UNTUK HP (<= 768px)
   ========================================= */
@media screen and (max-width: 768px) {
    .container {
        width: 100%;
        padding: 10px;
    }

    .hamburger {
        display: flex; /* Tampilkan tombol hamburger di HP */
    }

    header nav {
        flex-wrap: wrap;
    }

    /* Menu turun ke bawah & disembunyikan secara default */
    header nav ul {
        display: none;
        flex-direction: column;
        width: 100%;
        background: #34495e;
        margin-top: 15px;
        padding: 10px 0;
        border-radius: 6px;
        gap: 0;
    }

    /* Kelas aktif saat diklik via Javascript */
    header nav ul.show {
        display: flex;
    }

    header nav ul li {
        width: 100%;
        margin-right: 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    header nav ul li:last-child {
        border-bottom: none;
    }

    /* Desain tombol agar enak dipencet/luas di layar HP */
    header nav ul li a {
        padding: 14px 20px;
        font-size: 15px;
        width: 100%;
        border-radius: 0;
    }

    header nav ul li a:active {
        background: rgba(255, 255, 255, 0.2);
    }

    .dashboard-cards {
        flex-direction: column;
    }

    .info-card {
        min-width: 100%;
    }

    table th, table td {
        padding: 8px;
        font-size: 13px;
    }
}
/* Mengatur kotak card agar bisa scroll Y dengan max-height di Desktop */
.dashboard-cards.dashboard-scroll {
    max-height: 250px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 5px;
}

/* Media Query untuk HP (Layar <= 768px) */
@media screen and (max-width: 768px) {
    .dashboard-cards.dashboard-scroll {
        max-height: 220px; /* Tinggi maksimal di HP */
        flex-direction: column; /* Berjajar ke bawah di HP agar mudah di-scroll */
        flex-wrap: nowrap;
    }
    
    .dashboard-cards.dashboard-scroll .info-card {
        width: 100%;
        min-width: 100%;
    }
}
</style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="<?php echo $base_url; ?>pages/dashboard.php" class="nav-brand">Keuangan Kas</a>
                
                <button class="hamburger" id="hamburger-btn" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <ul id="nav-menu">
                    <li><a href="<?php echo $base_url; ?>pages/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $base_url; ?>pages/transaksi/input.php">Input Transaksi</a></li>
                    <li><a href="<?php echo $base_url; ?>pages/transaksi/tambah_kategori.php">Input kategori</a></li>
                    <li><a href="<?php echo $base_url; ?>pages/transaksi/tambah_akun.php">Input akun</a></li>
                    <li><a href="<?php echo $base_url; ?>pages/hutang_piutang/list.php">Hutang/Piutang</a></li>
                    <li><a href="<?php echo $base_url; ?>pages/transaksi/target.php">Target</a></li>
                    <li><a href="<?php echo $base_url; ?>index.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <script>
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const navMenu = document.getElementById('nav-menu');

        hamburgerBtn.addEventListener('click', () => {
            hamburgerBtn.classList.toggle('active');
            navMenu.classList.toggle('show');
        });
    </script>
    <main class="container" style="padding-top: 20px;">