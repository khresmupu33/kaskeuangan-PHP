<?php 
require_once 'config/koneksi.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Cek apakah username sudah ada
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($check) == 0) {
        // Simpan user baru
        mysqli_query($conn, "INSERT INTO users (username) VALUES ('$username')");
        $_SESSION['user_id'] = mysqli_insert_id($conn);
        $_SESSION['username'] = $username;
        
        // Arahkan paksa ke setup awal
        header("Location: setup_awal.php");
        exit();
    } else {
        $error = "Username sudah terdaftar. Silakan pilih yang lain.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Aplikasi Keuangan Kas</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #2c3e50;
            margin: 0;
            padding: 15px;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-card {
            width: 100%;
            max-width: 350px;
            padding: 24px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .register-card h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .register-card input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }
        .register-card button {
            width: 100%;
            padding: 11px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .register-card button:hover {
            background: #219150;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <h2>Registrasi</h2>
        <?php if(isset($error)) echo "<p style='color:red; font-size:14px; margin-bottom:12px; text-align:center;'>$error</p>"; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Buat Username Baru" required autocomplete="off">
            </div>
            <button type="submit">Daftar & Setup Akun</button>
        </form>
        <p style="margin-top: 15px; text-align: center; font-size: 14px;">
            <a href="index.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Kembali ke Login</a>
        </p>
    </div>
</body>
</html>