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
    <meta name="google-site-verification" content="C4gu43MsteoRCMp1eWGojm0ejzM9sjGQQ1nv-HKSj_E" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasKeuangan Khresmupu</title>
    <link rel="icon" type="image/png" href="includes/KasKeuanganKhresmupu.png">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #2c3e50;
            padding: 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .outer-container {
            width: 100%;
            max-width: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        /* Desain disamakan dengan .nav-brand dari navbar */
        .login-brand-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            margin-bottom: 20px;
        }
        /* Desain logo bulat disamakan dengan .nav-brand img */
        .login-brand-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }
        .login-card {
            width: 100%;
            padding: 24px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            text-align: center;
        }
        .login-card h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 22px;
        }
        .login-card input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            text-align: left;
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
            text-align: left;
        }
        /* Overlay & Lingkaran Loading Spinner (Benar-benar di paling depan) */
#page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: #f4f7f6; /* Warna latar belakang loading */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2147483647; /* Nilai z-index maksimal agar tidak tertutup apa pun */
    opacity: 0; 
    pointer-events: none;
    transition: opacity 0.4s ease-in-out;
}

/* Saat aktif, layar tertutup penuh oleh loader */
#page-loader.show {
    opacity: 1;
    pointer-events: auto;
}

/* Desain Lingkaran Spinner #2c3e50 */
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
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
    </style>
</head>
<body>
    <div id="page-loader" class="show">
        <div class="spinner"></div>
    </div>
    <div class="outer-container">
        <!-- Logo dan teks di luar kotak login, desainnya disamakan dengan navbar -->
        <a href="index.php" class="login-brand-header">
            <img src="includes/KasKeuanganKhresmupu.png" alt="Logo Kas Keuangan Khresmupu">
        </a>
        
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
    </div>
    <script>
        // Hilangkan loader dan jalankan fade-in saat halaman selesai dimuat
window.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('page-loader');
    document.body.classList.add('fade-in');
    setTimeout(() => {
        loader.classList.remove('show');
    }, 50);
});

// Tampilkan loader dan jalankan fade-out saat pindah halaman
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