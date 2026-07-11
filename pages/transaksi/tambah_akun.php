<?php
session_start();
// Cek sesi login agar tidak muncul error Undefined index
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$base_url = "../../";
include '../../includes/header.php'; 
require_once '../../config/koneksi.php';

$user_id = $_SESSION['user_id'];

// Proses Simpan Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_akun']);
    $saldo = (float)$_POST['saldo_awal']; // Mengambil nilai dari input hidden
    
    $query = "INSERT INTO akun_pembayaran (nama_akun, saldo_akhir, user_id) VALUES ('$nama', $saldo, $user_id)";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Akun berhasil ditambahkan!'); window.location='tambah_akun.php';</script>";
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($conn) . "');</script>";
    }
}

// Ambil data untuk tabel
$query_akun_list = mysqli_query($conn, "SELECT * FROM akun_pembayaran WHERE user_id = $user_id");
?>

<h1>Tambah Akun Pembayaran</h1>
<form method="POST">
    <div class="form-group">
        <label>Nama Akun:</label>
        <input type="text" name="nama_akun" required>
    </div>
    
    <div class="form-group">
        <label>Saldo Awal:</label>
        <input type="text" class="format-uang" data-hidden="saldo_awal" required placeholder="0">
        <input type="hidden" name="saldo_awal">
    </div>
    
    <button type="submit" style="background: #2c3e50;">Simpan</button>
</form>

<h2>Daftar Akun Pembayaran</h2>
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
                   style="color: red; text-decoration: none;">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
// Pastikan script ini ada di footer atau di sini untuk menangani format uang
document.querySelectorAll('input.format-uang').forEach(input => {
    input.addEventListener('input', function(e) {
        // Hapus karakter selain angka
        let value = this.value.replace(/[^0-9]/g, "");
        
        // Isi input hidden yang namanya sesuai dengan data-hidden
        let hiddenName = this.getAttribute('data-hidden');
        let hiddenInput = this.parentElement.querySelector('input[name="' + hiddenName + '"]');
        if (hiddenInput) {
            hiddenInput.value = value;
        }
        
        // Format tampilan ke user
        this.value = value ? value.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
    });
});
</script>

<?php include '../../includes/footer.php'; ?>