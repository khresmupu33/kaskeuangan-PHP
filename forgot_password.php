<?php
require_once 'config/koneksi.php';
session_start();

$error = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Cek apakah email terdaftar di database
    $stmt = mysqli_prepare($conn, "SELECT id, username FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $user_id = $user['id'];
        
        // Buat kode OTP acak 6 digit
        $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Hapus OTP lama milik user
        $del_stmt = mysqli_prepare($conn, "DELETE FROM otp_verification WHERE user_id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $user_id);
        mysqli_stmt_execute($del_stmt);
        
        // Simpan OTP baru ke tabel dengan status 'pending'
        $ins_stmt = mysqli_prepare($conn, "INSERT INTO otp_verification (user_id, otp_code, status) VALUES (?, ?, 'pending')");
        mysqli_stmt_bind_param($ins_stmt, "is", $user_id, $otp_code);
        
        if (mysqli_stmt_execute($ins_stmt)) {
            $success_msg = "Permintaan reset password berhasil dikirim! Silakan tunggu hingga admin memproses dan mengirimkan kode OTP ke Anda.";
        } else {
            $error = "Gagal memproses database!";
        }
    } else {
        $error = "Email tidak terdaftar di sistem!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - KasKeuangan Khresmupu</title>
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #2c3e50; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        .card { width: 100%; max-width: 420px; padding: 24px; background: #fff; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); text-align: left; }
        .card h2 { margin-bottom: 20px; color: #2c3e50; font-size: 22px; text-align: center; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; margin-bottom: 15px; box-sizing: border-box; }
        button { width: 100%; padding: 11px; background: #2c3e50; color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; }
        button:hover { background: #219150; }
        .error { color: red; font-size: 13px; margin-bottom: 10px; text-align: center; }
        .success-box { background: #e8f8f5; color: #16a085; padding: 15px; border-radius: 6px; font-size: 14px; margin-bottom: 15px; border: 1px solid #a3e4d7; text-align: center; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Lupa Password</h2>
        
        <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>

        <?php if(!empty($success_msg)): ?>
            <div class="success-box">
                <p style="margin-top:0; font-weight:bold;">✅ Berhasil Dikirim!</p>
                <p style="margin-bottom:0;"><?php echo $success_msg; ?></p>
            </div>
            <a href="index.php" style="display:block; text-align:center; background: #2980b9; color: white; padding: 11px; border-radius: 6px; text-decoration: none; font-weight: bold;">Kembali ke Login</a>
        <?php else: ?>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px; text-align: center;">Masukkan email akun Anda yang terdaftar untuk meminta reset password.</p>
            <form method="POST">
                <input type="email" name="email" placeholder="Email Anda..." required autocomplete="off">
                <button type="submit">Kirim Permintaan</button>
            </form>
            <p style="margin-top: 15px; font-size: 14px; text-align: center;"><a href="index.php" style="color: #3498db; text-decoration: none;">Kembali ke Login</a></p>
        <?php endif; ?>
    </div>
</body>
</html>