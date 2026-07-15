<?php
session_start();
require_once 'config/koneksi.php';

if (!isset($conn)) {
    die("Koneksi database tidak terhubung.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Proses Form POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Tambah Akun Pembayaran (Menggunakan tabel akun_pembayaran & kolom saldo_akhir)
    if (isset($_POST['tambah_akun'])) {
        $nama_akun = mysqli_real_escape_string($conn, $_POST['nama_akun']);
        $saldo_awal = isset($_POST['saldo_awal']) ? preg_replace('/[^0-9]/', '', $_POST['saldo_awal']) : 0;
        $saldo_awal = $saldo_awal === '' ? 0 : (float)$saldo_awal;
        
        if (!empty($nama_akun)) {
            $q = "INSERT INTO akun_pembayaran (user_id, nama_akun, saldo_akhir) VALUES ('$user_id', '$nama_akun', '$saldo_awal')";
            if (mysqli_query($conn, $q)) {
                $success_msg = "Akun pembayaran berhasil ditambahkan!";
            } else {
                $error_msg = "Gagal menambah akun: " . mysqli_error($conn);
            }
        }
    }

    // 2. Tambah Kategori (Menggunakan tabel kategori)
    if (isset($_POST['tambah_kategori'])) {
        $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
        
        if (!empty($nama_kategori)) {
            $q = "INSERT INTO kategori (user_id, nama_kategori) VALUES ('$user_id', '$nama_kategori')";
            if (mysqli_query($conn, $q)) {
                $success_msg = "Kategori berhasil ditambahkan!";
            } else {
                $error_msg = "Gagal menambah kategori: " . mysqli_error($conn);
            }
        }
    }
}

// Ambil jumlah data dari tabel yang benar
$jml_akun = 0;
$res_akun = @mysqli_query($conn, "SELECT COUNT(*) FROM akun_pembayaran WHERE user_id = '$user_id'");
if ($res_akun) { 
    $jml_akun = mysqli_fetch_array($res_akun)[0]; 
}

$jml_kat = 0;
$res_kat = @mysqli_query($conn, "SELECT COUNT(*) FROM kategori WHERE user_id = '$user_id'");
if ($res_kat) { 
    $jml_kat = mysqli_fetch_array($res_kat)[0]; 
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Awal - Aplikasi Keuangan Kas</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #2c3e50;
        color: #333;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 600px;
        margin: 0 auto;
    }

    .setup-card {
        background: #fff;
        padding: 24px;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    h1 {
        font-size: 1.5rem;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        font-size: 14px;
    }

    input,
    select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    button {
        background: #27ae60;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
        font-weight: bold;
    }

    button:hover {
        background: #219150;
    }

    .alert-success {
        background: #eefaf5;
        border: 1px solid #bfe8d7;
        color: #27ae60;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .alert-error {
        background: #fff1f1;
        border: 1px solid #ff4d4d;
        color: #e74c3c;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .list-group {
        margin-top: 10px;
        padding-left: 20px;
        font-size: 14px;
        color: #555;
    }

    .btn-selesai {
        display: block;
        text-align: center;
        background: #3498db;
        color: #fff;
        padding: 12px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
        margin-top: 20px;
    }

    .btn-selesai:hover {
        background: #2980b9;
    }

    @media screen and (max-width: 768px) {
        body {
            padding: 10px;
        }

        .setup-card {
            padding: 16px;
        }
    }

    /* Overlay & Lingkaran Loading Spinner (Benar-benar di paling depan) */
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: #f4f7f6;
        /* Warna latar belakang loading */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2147483647;
        /* Nilai z-index maksimal agar tidak tertutup apa pun */
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.4s ease-in-out;
    }

    /* Saat aktif, layar tertutup penuh oleh loader */
    #page-loader.show {
        opacity: 1;
        pointer-events: auto;
    }

    /* Desain Lingkaran Spinner #2c3e50 */
    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(44, 62, 80, 0.15);
        border-top: 5px solid #2c3e50;
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
    </style>
</head>

<body>
    <div id="page-loader" class="show">
        <div class="spinner"></div>
    </div>
    <div class="container">
        <h1>Setup Akun Pertama</h1>
        <p style="color: #fff; margin-bottom: 20px;">Selamat datang,
            <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong>! Mari buat akun pembayaran dan
            kategori pertama Anda.</p>

        <?php if(!empty($success_msg)): ?>
        <div class="alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
        <div class="alert-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="setup-card">
            <h3>Langkah 1: Buat Akun Pembayaran</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Akun</label>
                    <input type="text" name="nama_akun" placeholder="Misal: Dompet / BCA" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Saldo Awal (Rp)</label>
                    <input type="text" id="saldo_awal" name="saldo_awal" placeholder="0" required value="">
                </div>
                <button type="submit" name="tambah_akun" style="background: #2c3e50;">Simpan Akun</button>
            </form>
            <div style="margin-top: 15px; font-size: 13px;">
                <strong>Akun terdaftar (<?= $jml_akun ?>):</strong>
                <ul class="list-group">
                    <?php 
                    $q_akun = @mysqli_query($conn, "SELECT * FROM akun_pembayaran WHERE user_id = '$user_id'");
                    if ($q_akun) {
                        while($a = mysqli_fetch_assoc($q_akun)) {
                            echo "<li>" . htmlspecialchars($a['nama_akun']) . " (Rp " . number_format($a['saldo_akhir'], 0, ',', '.') . ")</li>";
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>

        <div class="setup-card">
            <h3>Langkah 2: Buat Kategori Transaksi</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" placeholder="Misal: Makan / Gaji" required
                        autocomplete="off">
                </div>
                <button type="submit" name="tambah_kategori" style="background: #2c3e50;">Simpan Kategori</button>
            </form>
            <div style="margin-top: 15px; font-size: 13px;">
                <strong>Kategori terdaftar (<?= $jml_kat ?>):</strong>
                <ul class="list-group">
                    <?php 
                    $q_kat = @mysqli_query($conn, "SELECT * FROM kategori WHERE user_id = '$user_id'");
                    if ($q_kat) {
                        while($kt = mysqli_fetch_assoc($q_kat)) {
                            echo "<li>" . htmlspecialchars($kt['nama_kategori']) . "</li>";
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>

        <?php if($jml_akun > 0 && $jml_kat > 0): ?>
        <a href="pages/dashboard.php" class="btn-selesai">Selesai, Mari Kita Mulai ke Dashboard &rarr;</a>
        <?php else: ?>
        <div
            style="text-align: center; color: #e67e22; font-size: 13px; font-weight: bold; background: #fef5e7; padding: 10px; border-radius: 4px; border: 1px solid #f9d7b5;">
            Harap buat minimal 1 Akun dan 1 Kategori di atas untuk melanjutkan ke aplikasi!
        </div>
        <?php endif; ?>
    </div>

    <script>
    const inputSaldo = document.getElementById('saldo_awal');

    function formatRupiah(angka, prefix) {
        let number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix === undefined ? rupiah : (rupiah ? prefix + rupiah : '');
    }

    inputSaldo.addEventListener('keyup', function(e) {
        inputSaldo.value = formatRupiah(this.value);
    });
    </script>
    <script>
    // Hilangkan loader dan jalankan fade-in saat halaman selesai dimuat
    window.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('page-loader');
        document.body.classList.add('fade-in');
        setTimeout(() => {
            loader.classList.remove('show');
        }, 50);
    });

    // Tampilkan loader dan jalankan fade-out saat pindah halaman
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.startsWith('#') && link.target !== '_blank' && !link.hasAttribute(
                'onclick')) {
            const targetUrl = link.href;
            if (targetUrl.includes(window.location.hostname) || targetUrl.startsWith('/')) {
                e.preventDefault();
                const loader = document.getElementById('page-loader');
                loader.classList.add('show');
                document.body.classList.remove('fade-in');
                document.body.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 400);
            }
        }
    });
    </script>
</body>

</html>