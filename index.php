<?php 
require_once 'config/koneksi.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        $_SESSION['user_id'] = $user['id'];
        header("Location: pages/dashboard.php");
        exit;
    } else {
        $error = "Username tidak terdaftar!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Keuangan Kas</title>
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
        .login-card {
            width: 100%;
            max-width: 350px;
            padding: 24px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .login-card h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .login-card input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }
        .login-card button {
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
        .login-card button:hover {
            background: #2c3e50;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Login</h2>
        <?php if(isset($error)) echo "<p style='color:red; font-size:14px; margin-bottom:12px; text-align:center;'>$error</p>"; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required autocomplete="off">
            </div>
            <button type="submit" style="background-color:#2c3e50;">Masuk</button>
        </form>
        <p style="margin-top: 15px; text-align: center; font-size: 14px; color: #555;">
            Belum punya akun? <a href="register.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Daftar</a>
        </p>
    </div>
</body>
</html>