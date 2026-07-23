<?php
// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path koneksi database sesuai struktur proyek Anda
require_once 'config/koneksi.php';

// Batasi akses hanya untuk user_id = 1 (Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    header("Location: pages/dashboard.php");
    exit;
}

$admin_id = 1;
$error_pin = "";

// Ambil data PIN admin dari tabel users
$stmt_admin = mysqli_prepare($conn, "SELECT pin FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_admin, "i", $admin_id);
mysqli_stmt_execute($stmt_admin);
$res_admin = mysqli_stmt_get_result($stmt_admin);
$data_admin = mysqli_fetch_assoc($res_admin);

$db_pin = $data_admin['pin'] ?? null;

// Generate PIN acak campur (kombinasi huruf besar, kecil, angka, dan simbol) untuk rekomendasi awal
$random_pin_suggestion = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$*!'), 0, 10);

// Proses form PIN (pembuatan baru atau verifikasi login PIN)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_pin'])) {
    $input_pin = trim($_POST['pin_value']);

    if (empty($input_pin)) {
        $error_pin = "PIN tidak boleh kosong!";
    } else {
        if (empty($db_pin)) {
            // Jika PIN masih kosong (NULL), simpan PIN baru (di-hash menggunakan password_hash)
            $hashed_pin = password_hash($input_pin, PASSWORD_DEFAULT);
            $update_pin = mysqli_prepare($conn, "UPDATE users SET pin = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_pin, "si", $hashed_pin, $admin_id);
            
            if (mysqli_stmt_execute($update_pin)) {
                // Sengaja TIDAK menset session agar setiap kali akses/refresh selalu diminta PIN kembali
                header("Location: otplist.php");
                exit;
            } else {
                $error_pin = "Gagal menyimpan PIN ke database!";
            }
        } else {
            // Jika PIN sudah ada, verifikasi kecocokannya
            if (password_verify($input_pin, $db_pin)) {
                // Set penanda khusus untuk sesi request ini saja (menggunakan variabel lokal, bukan session permanen)
                $pin_just_verified = true;
            } else {
                $error_pin = "PIN yang Anda masukkan salah!";
            }
        }
    }
}

// Karena session pin sengaja tidak disimpan secara permanen, 
// form PIN akan selalu muncul setiap halaman ini dibuka ulang atau direfresh.
$show_pin_form = empty($db_pin) || !isset($pin_just_verified);

if ($show_pin_form):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keamanan Admin - KasKeuangan Khresmupu</title>
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #2c3e50; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        .card { width: 100%; max-width: 400px; padding: 24px; background: #fff; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); text-align: center; }
        .card h2 { margin-bottom: 10px; color: #2c3e50; font-size: 22px; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px; margin-bottom: 15px; box-sizing: border-box; text-align: center; }
        button { width: 100%; padding: 11px; background: #2c3e50; color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; }
        button:hover { background: #219150; }
        .error { color: #e74c3c; font-size: 13px; margin-bottom: 15px; background: #fadbd8; padding: 8px; border-radius: 4px; text-align: left; }
        .suggestion-box { background: #e8f8f5; border: 1px solid #a3e4d7; padding: 10px; border-radius: 6px; font-size: 13px; color: #16a085; margin-bottom: 15px; text-align: left; word-break: break-all; }
    </style>
</head>
<body>
    <div class="card">
        <h2><?php echo empty($db_pin) ? 'Buat PIN Keamanan Admin' : 'Masukkan PIN Admin'; ?></h2>
        
        <?php if(!empty($error_pin)) echo "<div class='error'>$error_pin</div>"; ?>

        <?php if(empty($db_pin)): ?>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Akun Admin Anda belum memiliki PIN pengaman. Silakan buat PIN (disarankan menggunakan kombinasi acak campur agar aman).</p>
            <div class="suggestion-box">
                <b>Saran PIN Otomatis:</b><br><span id="saranPin" style="font-family: monospace; font-size: 14px; color: #2c3e50;"><?php echo $random_pin_suggestion; ?></span>
                <button type="button" onclick="salinSaran()" style="margin-top: 8px; padding: 5px; font-size: 12px; background: #16a085;">Gunakan Saran Ini</button>
            </div>
        <?php else: ?>
            <p style="font-size: 13px; color: #666; margin-bottom: 20px;">Masukkan PIN Admin Anda untuk mengakses daftar permintaan OTP.</p>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="pin_value" id="pinInput" placeholder="<?php echo empty($db_pin) ? 'Buat PIN baru...' : 'Masukkan PIN...'; ?>" required autocomplete="off" autofocus>
            <button type="submit" name="submit_pin"><?php echo empty($db_pin) ? 'Simpan & Lanjutkan' : 'Verifikasi PIN'; ?></button>
        </form>

        <div style="margin-top: 20px;">
            <a href="pages/dashboard.php" style="color: #3498db; text-decoration: none; font-size: 13px;">← Kembali ke Beranda</a>
        </div>
    </div>

    <script>
        function salinSaran() {
            var saran = document.getElementById('saranPin').innerText;
            document.getElementById('pinInput').value = saran;
        }
    </script>
</body>
</html>
<?php 
    exit; // Hentikan eksekusi jika form PIN sedang ditampilkan
endif;

// ==========================================
// KODE UTAMA HALAMAN OTPLIST (JIKA PIN SUDAH VALID)
// ==========================================

// Ambil data OTP pending digabung dengan tabel users (username & email)
$query = "SELECT o.*, u.username, u.email 
          FROM otp_verification o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.status = 'pending' 
          ORDER BY o.id DESC";
$result = mysqli_query($conn, $query);

// Base URL otomatis untuk link verifikasi
$base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['PHP_SELF']))) . "/";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Permintaan OTP - KasKeuangan Khresmupu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .content-wrap {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            padding: 12px 16px;
            border-bottom: 1px solid #edf2f7;
            text-align: left;
            font-size: 14px;
        }
        table th {
            background-color: #2c3e50;
            color: white;
        }
        .otp-badge {
            font-size: 15px;
            color: #e74c3c;
            font-weight: bold;
            background: #fadbd8;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .btn-kirim {
            background-color: #2c3e50;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }
        .btn-kirim:hover {
            background-color: #219150;
        }
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        /* Styling untuk Modal Pop-up */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 24px;
            border-radius: 8px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .close-btn {
            float: right;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .close-btn:hover { color: #000; }
        textarea {
            width: 100%;
            height: 140px;
            padding: 10px;
            font-family: monospace;
            font-size: 13px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            margin-top: 10px;
            resize: vertical;
        }
    </style>
</head>
<body>

    <div class="content-wrap">
        <h2>Daftar Permintaan OTP / Reset Password Pending</h2>
        <p style="color: #64748b; margin-bottom: 20px;">Berikut adalah daftar pengguna yang sedang menunggu verifikasi kode OTP.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>User (Username / Email)</th>
                        <th>Kode OTP</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php 
                                $link_verify = $base_url . "verify_otp.php";
                                // Templat pesan omongan untuk email
                                $template_pesan = "Halo " . $row['username'] . ",\n\nKami menerima permintaan untuk mereset password akun KasKeuangan Khresmupu Anda.\n\nBerikut adalah Kode OTP Anda:\nOTP: " . $row['otp_code'] . "\n\nSilakan masukkan kode tersebut melalui tautan berikut:\n" . $link_verify . "\n\nTerima kasih,\nAdmin KasKeuangan Khresmupu";
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong><br>
                                    <span style="color: #666; font-size: 12px;"><?php echo htmlspecialchars($row['email']); ?></span>
                                </td>
                                <td>
                                    <span class="otp-badge"><?php echo htmlspecialchars($row['otp_code']); ?></span>
                                </td>
                                <td><span style="color: #f39c12; font-weight: bold;">Pending</span></td>
                                <td>
                                    <button class="btn-kirim" onclick="bukaModal('<?php echo htmlspecialchars($row['email']); ?>', `<?php echo $template_pesan; ?>`)">Siap Kirim</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">Tidak ada permintaan OTP yang pending saat ini. 🎉</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="pages/dashboard.php" style="color: #2c3e50; text-decoration: none; font-weight: 600;">← Kembali ke Beranda</a>
        </div>
    </div>

    <!-- Modal Pop-up Templat Pesan -->
    <div id="modalTemplate" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="tutupModal()">&times;</span>
            <h3 style="margin-top: 0; color: #2c3e50;">Templat Pesan untuk Email</h3>
            <p style="font-size: 13px; color: #666;">Kirimkan email ke: <b id="emailTarget" style="color: #2980b9;"></b></p>
            <textarea id="textTemplate" readonly></textarea>
            <button onclick="salinPesan()" style="margin-top: 10px; width: 100%; padding: 10px; background: #2980b9; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Salin Teks Pesan</button>
        </div>
    </div>

    <script>
        function bukaModal(email, pesan) {
            document.getElementById('emailTarget').innerText = email;
            document.getElementById('textTemplate').value = pesan;
            document.getElementById('modalTemplate').style.display = 'flex';
        }

        function tutupModal() {
            document.getElementById('modalTemplate').style.display = 'none';
        }

        function salinPesan() {
            var copyText = document.getElementById("textTemplate");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            alert("Templat pesan berhasil disalin! Silakan tempel (paste) ke email tujuan.");
        }

        window.onclick = function(event) {
            var modal = document.getElementById('modalTemplate');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>
</html>