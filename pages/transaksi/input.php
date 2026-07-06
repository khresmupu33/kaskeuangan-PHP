<?php 
session_start();
$base_url = "../../"; 
include '../../includes/header.php'; 
require_once '../../config/koneksi.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$username = $_SESSION['username'] ?? 'guest';

// --- BAGIAN PROSES ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal = $_POST['tanggal'];
    $tipe = $_POST['tipe_transaksi'];
    $akun_id = (int)$_POST['akun_id'];
    $kategori_id = (int)$_POST['kategori_id'];
    $nominal = isset($_POST['nominal']) ? (float)$_POST['nominal'] : 0;
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']); // Mengambil dari input user

    // Cek saldo akun asal terlebih dahulu di database untuk keamanan server-side
    $q_saldo = mysqli_query($conn, "SELECT saldo_akhir, nama_akun FROM akun_pembayaran WHERE id = $akun_id");
    $data_akun_asal = mysqli_fetch_assoc($q_saldo);
    $saldo_sekarang = (float)($data_akun_asal['saldo_akhir'] ?? 0);
    $nama_akun_asal = $data_akun_asal['nama_akun'] ?? 'Akun';

    $jenis_transaksi_keluar = false;
    if ($tipe == 'TRANSFER') {
        $jenis_transaksi_keluar = true;
    } else {
        if ($_POST['jenis'] == 'KELUAR') {
            $jenis_transaksi_keluar = true;
        }
    }

    // Validasi pencegahan jika pengeluaran/transfer melebihi saldo
    if ($jenis_transaksi_keluar && $nominal > $saldo_sekarang) {
        echo "<script>alert('Peringatan: Saldo pada akun \"{$nama_akun_asal}\" tidak mencukupi! Saldo saat ini: Rp " . number_format($saldo_sekarang, 0, ',', '.') . ", Nominal transaksi: Rp " . number_format($nominal, 0, ',', '.') . "'); window.history.back();</script>";
        exit;
    }

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

            // 3. Simpan path yang mencakup username ke database
            $path_bukti = $username . '/' . $zip_name;
        }
    }

    if ($tipe == 'TRANSFER') {
        $akun_tujuan = (int)$_POST['akun_tujuan_id'];
        
        if ($akun_id == $akun_tujuan) {
            echo "<script>alert('Gagal: Akun asal dan akun tujuan tidak boleh sama!'); window.history.back();</script>";
            exit;
        }

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

// Petakan data saldo akun ke JavaScript untuk pengecekan real-time
$array_saldo_akun = [];
$q_map = mysqli_query($conn, "SELECT id, nama_akun, saldo_akhir FROM akun_pembayaran WHERE user_id = $user_id OR user_id IS NULL");
while($m = mysqli_fetch_assoc($q_map)) {
    $array_saldo_akun[$m['id']] = [
        'nama' => $m['nama_akun'],
        'saldo' => (float)$m['saldo_akhir']
    ];
}
?>

<h1>Input Transaksi</h1>
<form method="POST" enctype="multipart/form-data" id="transaksiForm" onsubmit="return validasiSaldoForm(event)">
    <div class="form-group"><label>Tanggal:</label><input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>"></div>
    
    <div class="form-group">
        <label>Tipe Transaksi:</label>
        <select name="tipe_transaksi" id="tipe_transaksi" onchange="toggleTransferField()">
            <option value="NORMAL">Normal</option>
            <option value="TRANSFER">Transfer</option>
        </select>
    </div>
    
    <div class="form-group" id="normal_type"><label>Jenis:</label><select name="jenis" id="jenis_transaksi"><option value="MASUK">Pemasukan</option><option value="KELUAR">Pengeluaran</option></select></div>
    
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
    
    <div class="form-group"><label>Akun:</label><select name="akun_id" id="akun_id" required><?php mysqli_data_seek($akun, 0); while($a = mysqli_fetch_assoc($akun)) echo "<option value='".$a['id']."'>".$a['nama_akun']."</option>"; ?></select></div>
    <div class="form-group" id="akun_tujuan_wrapper" style="display:none;"><label>Akun Tujuan:</label><select name="akun_tujuan_id" id="akun_tujuan_id"><?php mysqli_data_seek($akun, 0); while($a = mysqli_fetch_assoc($akun)) echo "<option value='".$a['id']."'>".$a['nama_akun']."</option>"; ?></select></div>
    
    <div class="form-group">
        <label>Nominal:</label>
        <input type="text" class="format-uang" data-hidden="nominal" required placeholder="1.000">
        <input type="hidden" name="nominal" id="nominal">
    </div>
    
    <div class="form-group"><label>Deskripsi:</label><input type="text" name="deskripsi" placeholder="Masukkan keterangan..." ></div>
    
    <div class="form-group"><label>Bukti (Gambar):</label><input type="file" name="bukti" accept="image/*"></div>
    <button type="submit">Simpan Transaksi</button>
</form>

<script>
// Data saldo akun dari PHP dipindahkan ke JavaScript
const dataSaldoAkun = <?php echo json_encode($array_saldo_akun); ?>;

function validasiSaldoForm(e) {
    const tipe = document.getElementById('tipe_transaksi').value;
    const akunId = document.getElementById('akun_id').value;
    const nominal = parseFloat(document.getElementById('nominal').value) || 0;
    const jenis = document.getElementById('jenis_transaksi').value;

    let isKeluar = false;
    if (tipe === 'TRANSFER') {
        isKeluar = true;
        const akunTujuan = document.getElementById('akun_tujuan_id').value;
        if (akunId === akunTujuan) {
            alert('Gagal: Akun asal dan akun tujuan tidak boleh sama!');
            e.preventDefault();
            return false;
        }
    } else {
        if (jenis === 'KELUAR') {
            isKeluar = true;
        }
    }

    // Peringatan jika saldo kurang saat transaksi keluar/transfer
    if (isKeluar && dataSaldoAkun[akunId]) {
        const infoAkun = dataSaldoAkun[akunId];
        if (nominal > infoAkun.saldo) {
            alert(`Peringatan: Saldo pada akun "${infoAkun.nama}" tidak mencukupi!\nSaldo Anda saat ini: Rp ${infoAkun.saldo.toLocaleString('id-ID')}\nNominal yang dimasukkan: Rp ${nominal.toLocaleString('id-ID')}`);
            e.preventDefault();
            return false;
        }
    }
    return true;
}
</script>

<?php include '../../includes/footer.php'; ?>