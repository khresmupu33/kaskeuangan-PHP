<?php
// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path koneksi database (sesuaikan jika file ini berada di dalam folder tertentu)
require_once 'config/koneksi.php';

// Validasi sesi verifikasi OTP/reset
if (!isset($_SESSION['verified_user_id'])) {
    echo "<script>alert('Sesi tidak valid. Silakan ulangi proses dari awal.'); window.location.href='index.php';</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['verified_user_id'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Update password baru ke atribut password_hash pada tabel users
    $stmt = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);
    mysqli_stmt_execute($stmt);
    
    // Hapus data OTP yang sudah digunakan
    $stmt_del = mysqli_prepare($conn, "DELETE FROM otp_verification WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_del, "i", $user_id);
    mysqli_stmt_execute($stmt_del);
    
    // Hapus sesi verifikasi
    unset($_SESSION['verified_user_id']);

    // Arahkan kembali ke index.php
    echo "<script>alert('Password berhasil diubah! Silakan login.'); window.location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - KasKeuangan</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #2c3e50; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        .card { width: 100%; max-width: 350px; padding: 24px; background: #fff; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); text-align: center; }
        .card h2 { margin-bottom: 20px; color: #2c3e50; font-size: 22px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; margin-bottom: 15px; box-sizing: border-box; }
        button { width: 100%; padding: 11px; background: #2c3e50; color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; }
        button:hover { background: #2c3e50; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Password Baru</h2>
        <form method="POST">
            <input type="password" name="new_password" placeholder="Masukkan Password Baru" required>
            <button type="submit">Simpan Password Baru</button>
        </form>
    </div>
</body>
</html>