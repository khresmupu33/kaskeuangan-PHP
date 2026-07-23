<?php 
require_once 'config/koneksi.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Cek apakah username sudah ada
    $check_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    // Cek apakah email sudah ada
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    
    if (mysqli_num_rows($check_user) > 0) {
        $error = "Username sudah terdaftar. Silakan pilih yang lain.";
    } elseif (mysqli_num_rows($check_email) > 0) {
        $error = "Email sudah terdaftar. Gunakan email lain atau lakukan pemulihan password.";
    } else {
        // Simpan user baru beserta email dan password_hash
        // (Pastikan kolom 'email' dan 'password_hash' sudah ada di tabel users Anda)
        mysqli_query($conn, "INSERT INTO users (username, email, password_hash) VALUES ('$username', '$email', '$password')");
        
        $_SESSION['user_id'] = mysqli_insert_id($conn);
        $_SESSION['username'] = $username;
        
        // Arahkan paksa ke setup awal
        header("Location: setup_awal.php");
        exit();
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

    .register-card input[type="text"],
    .register-card input[type="email"],
    .register-card input[type="password"] {
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
        background: #2c3e50;
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

    /* Overlay & Lingkaran Loading Spinner */
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: #f4f7f6;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2147483647;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.4s ease-in-out;
    }

    #page-loader.show {
        opacity: 1;
        pointer-events: auto;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(44, 62, 80, 0.15);
        border-top: 5px solid #2c3e50;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        position: relative;
        z-index: 2147483647;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
    </style>
</head>

<body>
    <div id="page-loader" class="show">
        <div class="spinner"></div>
    </div>
    <div class="register-card">
        <h2>Registrasi</h2>
        <?php if(isset($error)) echo "<p style='color:red; font-size:14px; margin-bottom:12px; text-align:center;'>$error</p>"; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Buat Username Baru" required autocomplete="off">
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Masukkan Email Aktif" required autocomplete="off">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Buat Password" required>
            </div>
            <button type="submit">Daftar & Setup Akun</button>
        </form>
        <p style="margin-top: 15px; text-align: center; font-size: 14px;">
            <a href="index.php" style="color: #3498db; text-decoration: none; font-weight: bold;">Kembali ke Login</a>
        </p>
    </div>
    <script>
    window.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('page-loader');
        document.body.classList.add('fade-in');
        setTimeout(() => {
            loader.classList.remove('show');
        }, 50);
    });

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.startsWith('#') && link.target !== '_blank' && !link.hasAttribute('onclick')) {
            const targetUrl = link.href;
            if (targetUrl.includes(window.location.hostname) || targetUrl.startsWith('/')) {
                e.preventDefault();
                const loader = document.getElementById('page-loader');
                loader.classList.add('show');
                document.body.classList.remove('fade-in');
                document.body.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 400);
            }
        }
    });
    </script>
</body>

</html>