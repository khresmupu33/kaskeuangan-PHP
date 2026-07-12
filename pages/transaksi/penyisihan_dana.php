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
$pesan_error = "";
$pesan_sukses = "";

// 1. Proses Simpan Penyisihan Dana Baru (Cancel jika target >= 7/10 dari saldo akun)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_penyisihan'])) {
    $akun_id = (int)$_POST['akun_id'];
    $nama_penyisihan = mysqli_real_escape_string($conn, $_POST['nama_penyisihan']);
    $target_nominal = (float)$_POST['target_nominal'];
    $terkumpul_nominal = (float)$_POST['terkumpul_nominal'];
    $tenggat_waktu = !empty($_POST['tenggat_waktu']) ? "'" . $_POST['tenggat_waktu'] . "'" : "NULL";

    $q_akun = mysqli_query($conn, "SELECT saldo_akhir, nama_akun FROM akun_pembayaran WHERE id = $akun_id AND user_id = $user_id");
    $d_akun = mysqli_fetch_assoc($q_akun);

    if ($d_akun) {
        $saldo_aktif = (float)$d_akun['saldo_akhir'];
        $batas_tujuh_persepuluh = 0.70 * $saldo_aktif;

        // Logika Cancel jika target nominal menghabiskan >= 7/10 saldo akun
        if ($target_nominal >= $batas_tujuh_persepuluh) {
            $pesan_error = "Dibatalkan! Target untuk alokasi <strong>{$nama_penyisihan}</strong> (Rp " . number_format($target_nominal, 0, ',', '.') . ") terlalu tinggi dan bakal menghabiskan ≥ 7/10 isi dompet di akun <strong>" . $d_akun['nama_akun'] . "</strong>. Gak usah mimpi setinggi langit!";
        } elseif ($terkumpul_nominal > $saldo_aktif) {
            $pesan_error = "Gagal! Nominal terkumpul tidak boleh melebihi sisa saldo akun.";
        } else {
            $status_awal = ($terkumpul_nominal >= $target_nominal) ? 'TERCAPAI' : 'AKTIF';
            $query = "INSERT INTO penyisihan_dana (user_id, akun_id, nama_penyisihan, target_nominal, terkumpul_nominal, tenggat_waktu, status) 
                      VALUES ($user_id, $akun_id, '$nama_penyisihan', $target_nominal, $terkumpul_nominal, $tenggat_waktu, '$status_awal')";
            
            mysqli_query($conn, $query);
            echo "<script>alert('Penyisihan dana berhasil disimpan!'); window.location='penyisihan_dana.php';</script>";
            exit;
        }
    }
}

// 2. Proses Tambah Nominal Manual
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_nominal_aksi'])) {
    $penyisihan_id = (int)$_POST['penyisihan_id'];
    $nominal_tambah = (float)$_POST['nominal_tambah'];

    $q_old = mysqli_query($conn, "SELECT p.*, a.saldo_akhir FROM penyisihan_dana p JOIN akun_pembayaran a ON p.akun_id = a.id WHERE p.id = $penyisihan_id AND p.user_id = $user_id");
    $d_old = mysqli_fetch_assoc($q_old);

    if ($d_old) {
        $new_terkumpul = $d_old['terkumpul_nominal'] + $nominal_tambah;
        $target = $d_old['target_nominal'];
        $saldo_aktif = (float)$d_old['saldo_akhir'];

        if ($new_terkumpul > $saldo_aktif || $target >= (0.70 * $saldo_aktif)) {
            echo "<script>alert('Gagal! Penambahan ini membuat alokasi melampaui batas aman saldo akun.'); window.location='penyisihan_dana.php';</script>";
            exit;
        }

        $new_status = ($new_terkumpul >= $target) ? 'TERCAPAI' : 'AKTIF';
        mysqli_query($conn, "UPDATE penyisihan_dana SET terkumpul_nominal = $new_terkumpul, status = '$new_status' WHERE id = $penyisihan_id AND user_id = $user_id");
        echo "<script>alert('Berhasil menambahkan nominal!'); window.location='penyisihan_dana.php';</script>";
        exit;
    }
}

// 3. Proses Kurangi Nominal Manual
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kurang_nominal_aksi'])) {
    $penyisihan_id = (int)$_POST['penyisihan_id'];
    $nominal_kurang = (float)$_POST['nominal_kurang'];

    $q_old = mysqli_query($conn, "SELECT * FROM penyisihan_dana WHERE id = $penyisihan_id AND user_id = $user_id");
    $d_old = mysqli_fetch_assoc($q_old);

    if ($d_old) {
        $new_terkumpul = max(0, $d_old['terkumpul_nominal'] - $nominal_kurang);
        $target = $d_old['target_nominal'];
        $new_status = ($new_terkumpul >= $target) ? 'TERCAPAI' : 'AKTIF';

        mysqli_query($conn, "UPDATE penyisihan_dana SET terkumpul_nominal = $new_terkumpul, status = '$new_status' WHERE id = $penyisihan_id AND user_id = $user_id");
        echo "<script>alert('Berhasil mengurangi nominal alokasi!'); window.location='penyisihan_dana.php';</script>";
        exit;
    }
}

// 4. Logika Koreksi/Pengurangan Otomatis & Pemberitahuan Dampak Buruk
$notif_koreksi_otomatis = [];
$q_autokoreksi = mysqli_query($conn, "SELECT p.id, p.nama_penyisihan, p.target_nominal, p.terkumpul_nominal, a.saldo_akhir, a.nama_akun 
                                      FROM penyisihan_dana p 
                                      JOIN akun_pembayaran a ON p.akun_id = a.id 
                                      WHERE p.user_id = '$user_id' AND p.status = 'AKTIF'");
while($row_k = mysqli_fetch_assoc($q_autokoreksi)) {
    $id_item = (int)$row_k['id'];
    $target_lama = (float)$row_k['target_nominal'];
    $terkumpul_lama = (float)$row_k['terkumpul_nominal'];
    $saldo_a = (float)$row_k['saldo_akhir'];

    // Jika terkumpul > 1/2 dari saldo akun atau target menguras >= 7/10 saldo
    if ($saldo_a > 0 && ($terkumpul_lama > (0.50 * $saldo_a) || $target_lama >= (0.70 * $saldo_a))) {
        // Pangkas otomatis terkumpul ke 1/4 (25%) dan target ke 50% dari sisa saldo agar tidak berdampak buruk
        $terkumpul_baru = min($terkumpul_lama, 0.25 * $saldo_a);
        $target_baru = min($target_lama, 0.50 * $saldo_a);

        if ($terkumpul_baru < $terkumpul_lama || $target_baru < $target_lama) {
            mysqli_query($conn, "UPDATE penyisihan_dana SET terkumpul_nominal = $terkumpul_baru, target_nominal = $target_baru WHERE id = $id_item AND user_id = $user_id");
            
            $notif_koreksi_otomatis[] = "Alokasi <strong>{$row_k['nama_penyisihan']}</strong> dikurangi otomatis oleh sistem! Dana terkumpul dipangkas dari Rp " . number_format($terkumpul_lama, 0, ',', '.') . " menjadi <strong>Rp " . number_format($terkumpul_baru, 0, ',', '.') . "</strong> (Target baru: Rp " . number_format($target_baru, 0, ',', '.') . ") karena nominal sebelumnya terdeteksi menguras habis saldo di <strong>{$row_k['nama_akun']}</strong>.";
        }
    }
}
?>

<div class="container">
    <h2>Penyisihan & Alokasi Dana (Celengan Virtual)</h2>

    <?php if (!empty($pesan_error)): ?>
        <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 5px;">
            <strong>⚠️ Perhatian:</strong> <?= $pesan_error ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($notif_koreksi_otomatis)): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <strong>🚨 Pemberitahuan Penyesuaian Saldo Otomatis:</strong>
            <p style="margin: 5px 0 8px 0; font-size: 13px;">Nominal alokasi di bawah ini telah disesuaikan demi mencegah boncos dan amblasnya sisa rekeningmu:</p>
            <ul style="margin: 0 0 0 20px; padding: 0;">
                <?php foreach($notif_koreksi_otomatis as $nk): ?>
                    <li style="margin-bottom: 5px; font-size: 12px;"><?= $nk ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px;">
        <h3>Buat Rencana Penyisihan Baru</h3>
        <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Target otomatis dibatalkan jika mencoba menghabiskan ≥ 7/10 dari isi saldo akun sumber.</p>
        <form method="POST" id="penyisihanForm">
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Akun Pembayaran (Sumber)</label>
                <select name="akun_id" required>
                    <option value="">-- Pilih Akun Pembayaran --</option>
                    <?php 
                    $akun = mysqli_query($conn, "SELECT * FROM akun_pembayaran WHERE user_id = '$user_id'");
                    while($a = mysqli_fetch_assoc($akun)) {
                        echo "<option value='".$a['id']."'>".$a['nama_akun']." (Sisa Saldo: Rp ".number_format($a['saldo_akhir'], 0, ',', '.').")</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Nama Rencana / Tujuan</label>
                <input type="text" name="nama_penyisihan" required placeholder="Misal: Dana Darurat, Beli Laptop" style="width:100%; padding:8px;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Target Nominal</label>
                <input type="text" class="format-uang" data-hidden="target_nominal" required placeholder="0" style="width:100%; padding:8px;">
                <input type="hidden" name="target_nominal" id="target_nominal">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Sudah Terkumpul (Awal)</label>
                <input type="text" class="format-uang" data-hidden="terkumpul_nominal" placeholder="0" style="width:100%; padding:8px;">
                <input type="hidden" name="terkumpul_nominal" id="terkumpul_nominal" value="0">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Tenggat Waktu (Opsional)</label>
                <input type="date" name="tenggat_waktu" style="padding:6px;">
            </div>
            <button type="submit" name="tambah_penyisihan" style="background: #2c3e50; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer;">Simpan Rencana</button>
        </form>
    </div>

    <div class="table-wrap" style="max-width: 100%; max-height: 350px; overflow-y: auto; overflow-x: auto; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="position: sticky; top: 0; background: #3498db; color: #fff; z-index: 5;">
                <tr>
                    <th style="padding: 10px;">Rencana</th>
                    <th style="padding: 10px;">Akun Sumber</th>
                    <th style="padding: 10px;">Target</th>
                    <th style="padding: 10px;">Terkumpul</th>
                
                    <th style="padding: 10px;">Tenggat</th>
                    <th style="padding: 10px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $query = mysqli_query($conn, "SELECT p.*, a.nama_akun, a.saldo_akhir FROM penyisihan_dana p JOIN akun_pembayaran a ON p.akun_id = a.id WHERE p.user_id = '$user_id'");
                while($row = mysqli_fetch_assoc($query)): 
                    $target = (float)$row['target_nominal'];
                    $terkumpul = (float)$row['terkumpul_nominal'];
                    $sisa_jarak = $target - $terkumpul;
                    $saldo_akun = (float)$row['saldo_akhir'];
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= htmlspecialchars($row['nama_penyisihan']) ?></td>
                    <td style="padding: 10px;"><?= htmlspecialchars($row['nama_akun']) ?> (Sisa: Rp <?= number_format($saldo_akun, 0, ',', '.') ?>)</td>
                    <td style="padding: 10px;">Rp <?= number_format($target, 0, ',', '.') ?></td>
                    <td style="padding: 10px;">
                        Rp <?= number_format($terkumpul, 0, ',', '.') ?>
                        <div style="font-size:10px; color:#666; background:#f9f9f9; padding:2px; margin-top:2px;">
                            Kurang: Rp <?= number_format($sisa_jarak, 0, ',', '.') ?>
                        </div>
                    </td>
                    <td style="padding: 10px;"><?= htmlspecialchars($row['tenggat_waktu'] ?? '-') ?></td>
                    <td style="padding: 10px;">
                        <?php if ($row['status'] !== 'TERCAPAI'): ?>
                            <button onclick="bukaFormTambah(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_penyisihan'], ENT_QUOTES) ?>')" style="background: #27ae60; padding: 4px 8px; font-size: 11px; margin-right: 3px; color: #fff; border: none; border-radius: 3px; cursor: pointer;">+ Nominal</button>
                            <button onclick="bukaFormKurang(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_penyisihan'], ENT_QUOTES) ?>')" style="background: #e67e22; padding: 4px 8px; font-size: 11px; margin-right: 3px; color: #fff; border: none; border-radius: 3px; cursor: pointer;">- Nominal</button>
                        <?php endif; ?>
                        <a href="hapus_penyisihan.php?id=<?= (int)$row['id'] ?>" style="color:red; font-size: 12px;" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalTambahNominal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; width:350px; border-radius:6px; position:relative;">
        <h3 id="modalTitleTambah">Tambah Nominal</h3>
        <form method="POST">
            <input type="hidden" name="penyisihan_id" id="modal_penyisihan_id_tambah">
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Nominal Tambahan (Rp)</label>
                <input type="text" class="format-uang" data-hidden="nominal_tambah" required placeholder="0" style="width:100%; padding:6px;">
                <input type="hidden" name="nominal_tambah" id="nominal_tambah">
            </div>
            <button type="submit" name="tambah_nominal_aksi" style="background: #27ae60; color:#fff; padding: 6px 12px; border:none; border-radius:4px; cursor:pointer;">Simpan</button>
            <button type="button" onclick="tutupFormTambah()" style="background: #7f8c8d; color:#fff; padding: 6px 12px; border:none; border-radius:4px; margin-left: 5px; cursor:pointer;">Batal</button>
        </form>
    </div>
</div>

<div id="modalKurangNominal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; width:350px; border-radius:6px; position:relative;">
        <h3 id="modalTitleKurang">Kurangi Nominal</h3>
        <form method="POST">
            <input type="hidden" name="penyisihan_id" id="modal_penyisihan_id_kurang">
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Nominal Pengurangan (Rp)</label>
                <input type="text" class="format-uang" data-hidden="nominal_kurang" required placeholder="0" style="width:100%; padding:6px;">
                <input type="hidden" name="nominal_kurang" id="nominal_kurang">
            </div>
            <button type="submit" name="kurang_nominal_aksi" style="background: #e67e22; color:#fff; padding: 6px 12px; border:none; border-radius:4px; cursor:pointer;">Kurangi</button>
            <button type="button" onclick="tutupFormKurang()" style="background: #7f8c8d; color:#fff; padding: 6px 12px; border:none; border-radius:4px; margin-left: 5px; cursor:pointer;">Batal</button>
        </form>
    </div>
</div>

<script>
function bukaFormTambah(id, nama) {
    document.getElementById('modal_penyisihan_id_tambah').value = id;
    document.getElementById('modalTitleTambah').innerText = 'Tambah Nominal: ' + nama;
    document.getElementById('modalTambahNominal').style.display = 'flex';
}
function tutupFormTambah() {
    document.getElementById('modalTambahNominal').style.display = 'none';
}

function bukaFormKurang(id, nama) {
    document.getElementById('modal_penyisihan_id_kurang').value = id;
    document.getElementById('modalTitleKurang').innerText = 'Kurangi Nominal: ' + nama;
    document.getElementById('modalKurangNominal').style.display = 'flex';
}
function tutupFormKurang() {
    document.getElementById('modalKurangNominal').style.display = 'none';
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