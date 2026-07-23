<?php
session_start();
require_once '../../config/koneksi.php';

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];

    // Menghapus akun hanya jika milik user yang sedang login
    mysqli_query($conn, "DELETE FROM akun_pembayaran WHERE id = $id AND user_id = $user_id");
}

header("Location: profil.php");
exit();
?>