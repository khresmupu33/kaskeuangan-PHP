<?php
session_start();
require_once 'config/koneksi.php';
if (!isset($_SESSION['user_id'])) header("Location: index.php");

// Logika simpan akun & kategori secara berurutan...
?>
<h1>Setup Akun Pertama</h1>
<p>Silakan buat akun pembayaran pertama Anda untuk melanjutkan.</p>
<a href="pages/transaksi/tambah_akun.php">Selesai, Mari Kita Mulai</a>