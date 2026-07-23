<?php
session_start();
require_once '../../config/koneksi.php';

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];

    // Hanya hapus jika kategori milik user (mencegah user menghapus kategori default/milik orang lain)
    // Pastikan user_id pada tabel kategori tidak null agar query ini aman
    mysqli_query($conn, "DELETE FROM kategori WHERE id = $id AND user_id = $user_id");
}

header("Location:profil.php");
exit();
?>