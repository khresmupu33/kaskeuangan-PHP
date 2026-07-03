<?php 
session_start();
$base_url = "../../"; 
include '../../includes/header.php'; 
require_once '../../config/koneksi.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// --- BAGIAN PROSES ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal = $_POST['tanggal'];
    $tipe = $_POST['tipe_transaksi'];
    $akun_id = (int)$_POST['akun_id'];
    $kategori_id = (int)$_POST['kategori_id'];
    $nominal = isset($_POST['nominal']) ? (float)$_POST['nominal'] : 0;
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']); // Mengambil dari input user
    
	$username = $_SESSION['username'] ?? 'guest'; // Ambil username dari session

    $path_bukti = null;

    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
        // 1. Tentukan path folder berdasarkan username
        $folder_user = '../../assets/img/' . $username;

        // 2. Buat folder jika belum ada (rekursif)
        if (!is_dir($folder_user)) {
            mkdir($folder_user, 0777, true);
        }

        $zip = new ZipArchive();
        $zip_name = 'bukti_' . time() . '.zip';
        $zip_path = $folder_user . '/' . $zip_name; // Path lengkap untuk disimpan

        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($_FILES['bukti']['tmp_name'], $_FILES['bukti']['name']);
            $zip->close();

            // 3. Simpan path yang mencakup username ke database (contoh: user1/bukti_123.zip)
            $path_bukti = $username . '/' . $zip_name;
        }
    }

    if ($tipe == 'TRANSFER') {
        $akun_tujuan = (int)$_POST['akun_tujuan_id'];
        // Menggunakan $deskripsi dari input user
        mysqli_query($conn, "INSERT INTO transaksi (user_id, tanggal, kategori_id, akun_id, tipe_transaksi, pengeluaran, path_bukti, deskripsi) VALUES ($user_id, '$tanggal', $kategori_id, $akun_id, 'TRANSFER', $nominal, '$path_bukti', '$deskripsi (Keluar)')");
        mysqli_query($conn, "INSERT INTO transaksi (user_id, tanggal, kategori_id, akun_id, tipe_transaksi, pemasukan, path_bukti, deskripsi) VALUES ($user_id, '$tanggal', $kategori_id, $akun_tujuan, 'TRANSFER', $nominal, '$path_bukti', '$deskripsi (Masuk)')");
        
        mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir - $nominal WHERE id = $akun_id");
        mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir + $nominal WHERE id = $akun_tujuan");
    } else {
        $masuk = ($_POST['jenis'] == 'MASUK') ? $nominal : 0;
        $keluar = ($_POST['jenis'] == 'KELUAR') ? $nominal : 0;
        mysqli_query($conn, "INSERT INTO transaksi (user_id, tanggal, kategori_id, akun_id, tipe_transaksi, pemasukan, pengeluaran, path_bukti, deskripsi) VALUES ($user_id, '$tanggal', $kategori_id, $akun_id, 'NORMAL', $masuk, $keluar, '$path_bukti', '$deskripsi')");
        
        if ($masuk > 0) mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir + $nominal WHERE id = $akun_id");
        else mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir - $nominal WHERE id = $akun_id");
    }
    
    echo "<script>alert('Berhasil!'); window.location='../dashboard.php';</script>";
}

$kategori = mysqli_query($conn, "SELECT * FROM kategori WHERE user_id = $user_id OR user_id IS NULL");
$akun = mysqli_query($conn, "SELECT * FROM akun_pembayaran WHERE user_id = $user_id OR user_id IS NULL");
?>

<h1>Input Transaksi</h1>
<form method="POST" enctype="multipart/form-data" id="transaksiForm">
    <div class="form-group"><label>Tanggal:</label><input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>"></div>
    
    <div class="form-group">
        <label>Tipe Transaksi:</label>
        <select name="tipe_transaksi" id="tipe_transaksi" onchange="toggleTransferField()">
            <option value="NORMAL">Normal</option>
            <option value="TRANSFER">Transfer</option>
        </select>
    </div>
    
    <div class="form-group" id="normal_type"><label>Jenis:</label><select name="jenis"><option value="MASUK">Pemasukan</option><option value="KELUAR">Pengeluaran</option></select></div>
    
    <div class="form-group" id="kategori_wrapper">
        <label>Kategori:</label>
        <select id="kategori_select" onchange="document.getElementById('hidden_kategori').value = this.value;">
            <?php 
            mysqli_data_seek($kategori, 0); 
            while($k = mysqli_fetch_assoc($kategori)) echo "<option value='".$k['id']."'>".$k['nama_kategori']."</option>"; 
            ?>
        </select>
        <input type="hidden" name="kategori_id" id="hidden_kategori" value="">
    </div>
    
    <div class="form-group"><label>Akun:</label><select name="akun_id" required><?php mysqli_data_seek($akun, 0); while($a = mysqli_fetch_assoc($akun)) echo "<option value='".$a['id']."'>".$a['nama_akun']."</option>"; ?></select></div>
    <div class="form-group" id="akun_tujuan_wrapper" style="display:none;"><label>Akun Tujuan:</label><select name="akun_tujuan_id"><?php mysqli_data_seek($akun, 0); while($a = mysqli_fetch_assoc($akun)) echo "<option value='".$a['id']."'>".$a['nama_akun']."</option>"; ?></select></div>
    
    <div class="form-group">
        <label>Nominal:</label>
        <input type="text" class="format-uang" data-hidden="nominal" required placeholder="1.000">
        <input type="hidden" name="nominal" id="nominal">
    </div>
    
    <div class="form-group"><label>Deskripsi:</label><input type="text" name="deskripsi" placeholder="Masukkan keterangan..." required></div>
    
    <div class="form-group"><label>Bukti (Gambar):</label><input type="file" name="bukti" accept="image/*"></div>
    <button type="submit">Simpan Transaksi</button>
</form>


<?php include '../../includes/footer.php'; ?>