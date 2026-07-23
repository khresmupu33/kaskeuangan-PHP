<?php
// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path koneksi database (sesuaikan dengan struktur folder Anda)
require_once 'config/koneksi.php';

$error = "";
$success = "";

// Proses ketika form verifikasi disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $input_email = trim($_POST['email']);
    $input_otp = trim($_POST['otp_code']);

    if (empty($input_email) || empty($input_otp)) {
        $error = "Email dan kode OTP tidak boleh kosong!";
    } else {
        // Cek data user berdasarkan email
        $stmt_user = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt_user, "s", $input_email);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);

        if ($row_user = mysqli_fetch_assoc($result_user)) {
            $user_id = $row_user['id'];

            // Cek apakah kode OTP cocok dengan user tersebut dan statusnya 'pending'
            $stmt = mysqli_prepare($conn, "SELECT * FROM otp_verification WHERE user_id = ? AND otp_code = ? AND status = 'pending'");
            mysqli_stmt_bind_param($stmt, "is", $user_id, $input_otp);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                // Update status OTP menjadi 'verified'
                $update_stmt = mysqli_prepare($conn, "UPDATE otp_verification SET status = 'verified' WHERE id = ?");
                mysqli_stmt_bind_param($update_stmt, "i", $row['id']);
                mysqli_stmt_execute($update_stmt);

                // Simpan session atau arahkan ke halaman berikutnya
                $_SESSION['verified_user_id'] = $user_id;
                
                $success = "Verifikasi OTP berhasil! Mengalihkan...";
                header("refresh:2;url=reset_password.php");
            } else {
                $error = "Email atau Kode OTP salah / sudah kedaluwarsa!";
            }
        } else {
            $error = "Email tidak ditemukan di sistem!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - KasKeuangan Khresmupu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            text-align: center;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 16px;
            text-align: center;
            box-sizing: border-box;
            margin-bottom: 15px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover {
            background-color: #219150;
        }
        .error {
            background-color: #fadbd8;
            color: #c0392b;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
            text-align: left;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
            text-align: left;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="card">
        <h2>Verifikasi Kode OTP</h2>
        <p>Silakan masukkan email dan kode OTP Anda untuk melanjutkan.</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="email" name="email" placeholder="Masukkan Email Anda" required autocomplete="off">
            <input type="text" name="otp_code" placeholder="Masukkan Kode OTP" required autocomplete="off" style="letter-spacing: 2px;">
            <button type="submit" name="verify_otp">Verifikasi OTP</button>
        </form>

        <a href="index.php" class="back-link">← Kembali ke ke login</a>
    </div>

</body>
</html>