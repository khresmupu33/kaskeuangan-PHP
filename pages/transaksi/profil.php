<?php
session_start();
// Cek sesi login agar tidak muncul error Undefined index
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$base_url = "../../"; 
require_once '../../config/koneksi.php';

$user_id = $_SESSION['user_id'];
$error = "";
$success_msg = "";
$otp_success_msg = "";
$otp_error = "";

// 1. Ambil Informasi User (Username, Email, dll) dari database
$stmt_user = mysqli_prepare($conn, "SELECT username, email, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($result_user);

// 2. Proses Form Request Reset Password (OTP) - Tanpa Input Email Manual (Mengambil dari sesi/user yang login)
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'request_otp') {
    $email = $user_data['email'];
    
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
        $otp_success_msg = "Permintaan reset password berhasil dikirim! Silakan tunggu hingga admin memproses dan mengirimkan kode OTP ke email Anda (" . htmlspecialchars($email) . ").";
    } else {
        $otp_error = "Gagal memproses database untuk permintaan OTP!";
    }
}

// 3. Proses Tambah Kategori
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_kategori') {
    $nama_kat = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    $query_kat = "INSERT INTO kategori (nama_kategori, user_id) VALUES ('$nama_kat', $user_id)";
    
    if (mysqli_query($conn, $query_kat)) {
        echo "<script>alert('Kategori berhasil ditambahkan!'); window.location='profil.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal tambah kategori: " . mysqli_error($conn) . "');</script>";
    }
}

// 4. Proses Tambah Akun Pembayaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_akun') {
    $nama_akun = mysqli_real_escape_string($conn, $_POST['nama_akun']);
    $saldo = (float)$_POST['saldo_awal']; 
    
    $query_akun = "INSERT INTO akun_pembayaran (nama_akun, saldo_akhir, user_id) VALUES ('$nama_akun', $saldo, $user_id)";
    
    if (mysqli_query($conn, $query_akun)) {
        echo "<script>alert('Akun pembayaran berhasil ditambahkan!'); window.location='profil.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal tambah akun: " . mysqli_error($conn) . "');</script>";
    }
}

// Ambil data untuk tabel Kategori
$query_kategori_list = mysqli_query($conn, "SELECT * FROM kategori WHERE user_id = $user_id OR user_id IS NULL");

// Ambil data untuk tabel Akun Pembayaran
$query_akun_list = mysqli_query($conn, "SELECT * FROM akun_pembayaran WHERE user_id = $user_id");

// Panggil header global
include '../../includes/header.php'; 
?>

<!-- Custom CSS Sesuai Permintaan Anda -->
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Layout Container */


    h1 {
        color: #2c3e50;
        font-size: 24px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #edf2f7;
    }

    h2 {
        color: #2c3e50;
        font-size: 20px;
        margin: 30px 0 15px 0;
    }

    /* Tabel Styling Modern */
    .table-wrap {
        overflow-x: auto;
        max-width: 100%;
        border-radius: 8px;
        box-shadow: 0 0 0 1px #eee;
        margin-top: 15px;
        margin-bottom: 25px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        text-align: left;
    }

    table th,
    table td {
        border-bottom: 1px solid #edf2f7;
        padding: 12px 16px;
        vertical-align: middle;
        white-space: nowrap;
        font-size: 14px;
    }

    table th {
        background-color: #2c3e50;
        color: white;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 14px;
        color: #2c3e50;
    }

    input, select, textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        transition: all 0.2s;
        background: #fff;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #2c3e50;
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.12);
    }

    button {
        background: #27ae60;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background 0.2s, transform 0.1s;
    }

    button:hover {
        background: #219150;
    }

    /* Grid Section untuk Profil & Form */
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 30px;
    }

    @media screen and (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }

    .card-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
    }

    .success-box { 
        background: #e8f8f5; 
        color: #16a085; 
        padding: 15px; 
        border-radius: 6px; 
        font-size: 14px; 
        margin-bottom: 15px; 
        border: 1px solid #a3e4d7; 
        line-height: 1.5; 
    }

    .error-box {
        color: #e74c3c; 
        background: #fdf2f2;
        padding: 12px;
        border-radius: 6px;
        font-size: 13px; 
        margin-bottom: 15px; 
        border: 1px solid #f5c6cb;
    }
</style>

<div class="container">
    <h1>Pengaturan Akun & Data Master</h1>

    <!-- SECTION 1: INFORMASI USER & RESET PASSWORD VIA OTP -->
    <div class="profile-grid">
        <div class="card-box">
            <h2>Informasi Pengguna</h2>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" readonly style="background: #e9ecef;">
            </div>
            <div class="form-group">
                <label>Email Terdaftar:</label>
                <input type="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" readonly style="background: #e9ecef;">
            </div>
            <div class="form-group">
                <label>Bergabung Sejak:</label>
                <input type="text" value="<?php echo htmlspecialchars($user_data['created_at'] ?? ''); ?>" readonly style="background: #e9ecef;">
            </div>
        </div>

        <div class="card-box">
            <h2>Keamanan & Reset Password</h2>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                Butuh mengganti password? Kirim permintaan kode OTP reset password ke email terdaftar Anda secara instan.
            </p>

            <?php if(!empty($otp_error)): ?>
                <div class="error-box"><?php echo $otp_error; ?></div>
            <?php endif; ?>

            <?php if(!empty($otp_success_msg)): ?>
                <div class="success-box">
                    <p style="margin-top:0; font-weight:bold;">✅ Berhasil Dikirim!</p>
                    <p style="margin-bottom:0;"><?php echo $otp_success_msg; ?></p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="request_otp">
                    <button type="submit" style="background: #2980b9; width: 100%;">Kirim Permintaan OTP Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid #edf2f7; margin: 30px 0;">

    <!-- SECTION 2: TAMBAH & DAFTAR AKUN PEMBAYARAN (DOMPET/REKENING) -->
    <div style="margin-bottom: 40px;">
        <h2>Tambah Akun Pembayaran (Dompet/Rekening)</h2>
        <form method="POST">
            <input type="hidden" name="action" value="tambah_akun">
            <div class="form-group">
                <label>Nama Akun:</label>
                <input type="text" name="nama_akun" required placeholder="Contoh: BCA, Dompet Tunai, OVO">
            </div>
            
            <div class="form-group">
                <label>Saldo Awal:</label>
                <input type="text" class="format-uang" data-hidden="saldo_awal" required placeholder="0">
                <input type="hidden" name="saldo_awal">
            </div>
            
            <button type="submit" style="background: #2c3e50;">Simpan Akun</button>
        </form>

        <h2>Daftar Akun Pembayaran</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama Akun</th>
                        <th>Saldo Akhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($ak = mysqli_fetch_assoc($query_akun_list)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ak['nama_akun']); ?></td>
                        <td>Rp <?php echo number_format($ak['saldo_akhir'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="hapus_akun.php?id=<?php echo $ak['id']; ?>" 
                               onclick="return confirm('Yakin ingin menghapus akun ini? Semua riwayat transaksi yang terkait dengan akun ini mungkin akan terpengaruh/gagal.')" 
                               style="color: red; text-decoration: none; font-weight: 500;">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid #edf2f7; margin: 30px 0;">

    <!-- SECTION 3: TAMBAH & DAFTAR KATEGORI -->
    <div>
        <h2>Tambah Kategori Baru</h2>
        <form method="POST">
            <input type="hidden" name="action" value="tambah_kategori">
            <div class="form-group">
                <label>Nama Kategori:</label>
                <input type="text" name="nama_kategori" required placeholder="Contoh: Makanan, Transportasi">
            </div>
            <button type="submit" style="background: #2c3e50;">Simpan Kategori</button>
        </form>

        <h2>Daftar Kategori</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($kt = mysqli_fetch_assoc($query_kategori_list)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($kt['nama_kategori']); ?></td>
                        <td>
                            <?php if ($kt['user_id'] !== null): ?>
                                <a href="hapus_kategori.php?id=<?php echo $kt['id']; ?>" 
                                   onclick="return confirm('Yakin ingin menghapus kategori ini?')" 
                                   style="color: red; text-decoration: none; font-weight: 500;">Hapus</a>
                            <?php else: ?>
                                <span style="color: #a0aec0;">Locked (Default)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Script untuk menangani format uang otomatis pada input Saldo Awal
document.querySelectorAll('input.format-uang').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, "");
        
        let hiddenName = this.getAttribute('data-hidden');
        let hiddenInput = this.parentElement.querySelector('input[name="' + hiddenName + '"]');
        if (hiddenInput) {
            hiddenInput.value = value;
        }
        
        this.value = value ? value.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
    });
});
</script>

<?php 
// Panggil footer global
include '../../includes/footer.php'; 
?>