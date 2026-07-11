<?php 
session_start();
$base_url = "../../"; 
include '../../includes/header.php'; 
require_once '../../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$today_dt = new DateTime();
$today_dt->setTime(0, 0, 0);
$today = $today_dt->format('Y-m-d'); // 2026-07-06

// Proses Simpan Target Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_target'])) {
    $kategori_id = (int)$_POST['kategori_id'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $nominal = (float)$_POST['nominal_maksimal'];
    $tenggat = $_POST['tenggat_waktu'];
    $tipe = $_POST['tipe_target'];
    $periode = ($tipe == 'RUTIN') ? $_POST['periode_target'] : NULL;

    $query = "INSERT INTO target (user_id, kategori_id, deskripsi, nominal_maksimal, tenggat_waktu, tipe_target, status, periode_target) 
              VALUES ($user_id, $kategori_id, '$deskripsi', $nominal, '$tenggat', '$tipe', 'AKTIF', " . ($periode ? "'$periode'" : "NULL") . ")";
    
    mysqli_query($conn, $query);
    echo "<script>alert('Target berhasil disimpan!'); window.location='target.php';</script>";
}
?>

<div class="container">
    <h2>Pengaturan Target Keuangan</h2>

   <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px;">
    <form method="POST" id="targetForm">
        <div class="form-group">
            <label>Kategori</label>
            <select name="kategori_id" required>
                <?php 
                $kat = mysqli_query($conn, "SELECT * FROM kategori WHERE (user_id = '$user_id' OR user_id IS NULL) AND id != 1");
                while($k = mysqli_fetch_assoc($kat)) echo "<option value='".$k['id']."'>".$k['nama_kategori']."</option>";
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <input type="text" name="deskripsi" required>
        </div>
        <div class="form-group">
            <label>Nominal Maksimal</label>
            <input type="text" class="format-uang" data-hidden="nominal_maksimal" required placeholder="0">
            <input type="hidden" name="nominal_maksimal" id="nominal_maksimal">
        </div>
        <div class="form-group">
            <label>Tenggat Waktu</label>
            <input type="date" name="tenggat_waktu" required>
        </div>
        <div class="form-group">
            <label>Tipe Target</label>
            <select name="tipe_target" id="tipe_target" onchange="togglePeriode()">
                <option value="SEKALI">Sekali</option>
                <option value="RUTIN">Rutin</option>
            </select>
        </div>
        <div class="form-group" id="periode_wrapper" style="display:none;">
            <label>Periode Reset</label>
            <select name="periode_target">
                <option value="BULANAN">Bulanan</option>
                <option value="TAHUNAN">Tahunan</option>
            </select>
        </div>
        <button type="submit" name="tambah_target" style="background: #2c3e50;">Simpan Target</button>
    </form>
</div>

<div class="table-wrap" style="max-width: 100%; max-height: 350px; overflow-y: auto; overflow-x: auto; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="position: sticky; top: 0; background: #3498db; z-index: 5;">
            <tr>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Budget</th>
                <th>Realisasi</th>
                <th>Tipe</th>
                <th>Periode</th>
                <th>Tenggat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $query = mysqli_query($conn, "SELECT t.*, k.nama_kategori FROM target t JOIN kategori k ON t.kategori_id = k.id WHERE t.user_id = '$user_id'");
            while($row = mysqli_fetch_assoc($query)): 
                // Simpan tenggat asli sebelum di-update
                $original_tenggat = $row['tenggat_waktu'];

                // 1. ATUR START_DATE DIKURANGI 1 BULAN (ATAU 1 TAHUN) DARI TENGGAT ASLI
                if($row['periode_target'] == 'BULANAN') {
                    $start_date = date('Y-m-d', strtotime($original_tenggat . ' -1 month'));
                } elseif($row['periode_target'] == 'TAHUNAN') {
                    $start_date = date('Y-m-d', strtotime($original_tenggat . ' -1 year'));
                } else {
                    $start_date = date('Y-m-d', strtotime($original_tenggat . ' -1 month')); 
                }

                // 2. END_DATE MENGGUNAKAN TANGGAL TENGGAT ASLI
                $end_date = $original_tenggat;

                // Query menjumlahkan pengeluaran berdasarkan rentang tersebut
                $sql_real = "SELECT SUM(pengeluaran) FROM transaksi 
                             WHERE kategori_id = '{$row['kategori_id']}' 
                             AND user_id = '$user_id' 
                             AND tanggal > '$start_date' 
                             AND tanggal <= '$end_date'";
                
                $result_real = mysqli_query($conn, $sql_real);
                $realisasi = mysqli_fetch_array($result_real)[0] ?? 0;

                // 3. LOGIKA UPDATE / HAPUS OTOMATIS TENGGAT WAKTU JIKA SUDAH LEWAT HARI INI
                $id_target = (int)$row['id'];
                $tenggat_db = new DateTime($original_tenggat);
                $tenggat_db->setTime(0, 0, 0);

                if ($tenggat_db < $today_dt) {
                    if ($row['tipe_target'] === 'SEKALI') {
                        mysqli_query($conn, "DELETE FROM target WHERE id = $id_target AND user_id = $user_id");
                    } elseif ($row['tipe_target'] === 'RUTIN') {
                        $new_tenggat = clone $tenggat_db;
                        while ($new_tenggat < $today_dt) {
                            if ($row['periode_target'] === 'BULANAN') {
                                $new_tenggat->modify('+1 month');
                            } elseif ($row['periode_target'] === 'TAHUNAN') {
                                $new_tenggat->modify('+1 year');
                            } else {
                                break; 
                            }
                        }
                        $new_date_str = $new_tenggat->format('Y-m-d');
                        mysqli_query($conn, "UPDATE target SET tenggat_waktu = '$new_date_str' WHERE id = $id_target AND user_id = $user_id");
                    }
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                <td>Rp <?= number_format($row['nominal_maksimal'], 0, ',', '.') ?></td>
                <td>
                    Rp <?= number_format($realisasi, 0, ',', '.') ?>
                    <div style="font-size:10px; color:#666; background:#f9f9f9; padding:2px; margin-top:2px;">
                        Range: [<?= $start_date ?> s/d <?= $end_date ?>]
                    </div>
                </td>
                <td><?= htmlspecialchars($row['tipe_target']) ?></td>
                <td><?= htmlspecialchars($row['periode_target'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['tenggat_waktu']) ?></td>
                <td><a href="hapus_target.php?id=<?= (int)$row['id'] ?>" style="color:red;" onclick="return confirm('Yakin?')">Hapus</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</div>

<script>
function togglePeriode() {
    const tipe = document.getElementById('tipe_target').value;
    const wrapper = document.getElementById('periode_wrapper');
    wrapper.style.display = (tipe === 'RUTIN') ? 'block' : 'none';
}

document.querySelectorAll('input.format-uang').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, "");
        let hiddenInput = document.querySelector('input[name="' + this.getAttribute('data-hidden') + '"]');
        if (hiddenInput) hiddenInput.value = value;
        this.value = value ? value.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
    });
});
</script>

<?php include '../../includes/footer.php'; ?>