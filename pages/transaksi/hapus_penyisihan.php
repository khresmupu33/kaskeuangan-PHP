<?php
session_start();
require_once '../../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];
    
    mysqli_query($conn, "DELETE FROM penyisihan_dana WHERE id = $id AND user_id = $user_id");
}

header("Location: penyisihan_dana.php");
exit;
?>