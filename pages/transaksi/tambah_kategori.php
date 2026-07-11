<?php
session_start();
// Cek sesi login
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
    $nama = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    
    // Perbaikan: Tambahkan tanda petik tunggal di sekitar $nama agar query valid
    $query = "INSERT INTO kategori (nama_kategori, user_id) VALUES ('$nama', $user_id)";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Kategori berhasil ditambahkan!'); window.location='tambah_kategori.php';</script>";
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($conn) . "');</script>";
    }
}

// Ambil data untuk tabel
$query_kategori_list = mysqli_query($conn, "SELECT * FROM kategori WHERE user_id = $user_id OR user_id IS NULL");
?>

<h1>Tambah Kategori Baru</h1>
<form method="POST">
    <div class="form-group">
        <label>Nama Kategori:</label>
        <input type="text" name="nama_kategori" required>
    </div>
    <button type="submit" style="background: #2c3e50;">Simpan</button>
</form>



<h2>Daftar Kategori</h2>
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
                       style="color: red; text-decoration: none;">Hapus</a>
                <?php else: ?>
                    <span style="color: #ccc;">Locked</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include '../../includes/footer.php'; ?>