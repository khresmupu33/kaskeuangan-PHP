<?php
session_start();
require_once '../config/koneksi.php';

if(isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 1. Ambil data transaksi sebelum dihapus
    $query = mysqli_query($conn, "SELECT * FROM transaksi WHERE id = $id AND user_id = $user_id");
    $tr = mysqli_fetch_assoc($query);

    if($tr) {
        $akun_id = $tr['akun_id'];
        $nominal_masuk = $tr['pemasukan'];
        $nominal_keluar = $tr['pengeluaran'];

        // 2. Kembalikan saldo akun
        if($nominal_masuk > 0) {
            // Jika dulunya pemasukan, maka kurangi saldo
            mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir - $nominal_masuk WHERE id = $akun_id");
        } elseif($nominal_keluar > 0) {
            // Jika dulunya pengeluaran, maka tambah kembali saldo
            mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir + $nominal_keluar WHERE id = $akun_id");
        }

        // 3. Hapus data transaksi
        mysqli_query($conn, "DELETE FROM transaksi WHERE id = $id AND user_id = $user_id");
    }
}

// Kembali ke dashboard
header("Location: riwayat_lengkap.php");
exit();
?>