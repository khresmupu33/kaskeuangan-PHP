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
$username = $_SESSION['username'] ?? 'guest';
$today_dt = new DateTime();
$today_dt->setTime(0, 0, 0);
$today = $today_dt->format('Y-m-d'); // Tanggal hari ini

// Tentukan direktori penyimpanan berdasarkan username
$folder_user = '../../assets/img/' . $username;
if (!is_dir($folder_user)) {
    @mkdir($folder_user, 0777, true);
}

// Ambil data kategori (Kecuali ID 1)
$kategori_query = mysqli_query($conn, "SELECT * FROM kategori WHERE (user_id = '$user_id' OR user_id IS NULL) AND id != 1");
$kategori_list = [];
while($k = mysqli_fetch_assoc($kategori_query)) {
    $kategori_list[] = $k;
}

// Ambil daftar akun pembayaran
$akun_query = mysqli_query($conn, "SELECT * FROM akun_pembayaran WHERE user_id = '$user_id' OR user_id IS NULL");
$akun_list = [];
while($a = mysqli_fetch_assoc($akun_query)) {
    $akun_list[] = $a;
}

// Proses Hapus Tagihan
if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];
    mysqli_query($conn, "DELETE FROM tagihan WHERE id = $hapus_id AND user_id = $user_id");
    echo "<script>alert('Tagihan berhasil dihapus!'); window.location='tagihan.php';</script>";
    exit;
}

// Proses Pelunasan / Pembayaran Tagihan dari Tabel Aksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_lunas'])) {
    $tagihan_id = (int)$_POST['tagihan_id'];
    $akun_id = (int)$_POST['akun_id'];
    $kategori_id = (int)$_POST['kategori_id'];
    $nominal_bayar = (float)$_POST['nominal_bayar'];
    $tanggal_trx = $today; 

    // Cek Saldo Akun Pembayaran saat Pelunasan
    $q_akun_cek = mysqli_query($conn, "SELECT saldo_akhir FROM akun_pembayaran WHERE id = $akun_id AND user_id = $user_id");
    $d_akun_cek = mysqli_fetch_assoc($q_akun_cek);
    if ($d_akun_cek && (float)$d_akun_cek['saldo_akhir'] < $nominal_bayar) {
        echo "<script>alert('Gagal: Saldo akun pembayaran tidak cukup untuk melakukan pelunasan ini!'); window.location='tagihan.php';</script>";
        exit;
    }

    // Handle Upload Bukti ZIP Pelunasan
    $path_bukti = null;
    if (isset($_FILES['bukti_bayar_lunas']) && $_FILES['bukti_bayar_lunas']['error'] == 0) {
        if (!is_dir($folder_user)) {
            mkdir($folder_user, 0777, true);
        }

        $zip = new ZipArchive();
        $zip_name = 'bukti_lunas_' . time() . '.zip';
        $zip_path = $folder_user . '/' . $zip_name; 

        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($_FILES['bukti_bayar_lunas']['tmp_name'], $_FILES['bukti_bayar_lunas']['name']);
            $zip->close();
            $path_bukti = $username . '/' . $zip_name; 
        }
    }
    $path_bukti_sql = $path_bukti ? "'$path_bukti'" : "NULL";

    $q_tagihan = mysqli_query($conn, "SELECT * FROM tagihan WHERE id = $tagihan_id AND user_id = $user_id");
    $d_tagihan = mysqli_fetch_assoc($q_tagihan);

    if ($d_tagihan) {
        $sisa_sekarang = (float)$d_tagihan['sisa_nominal'];
        $bayar_aktual = min($nominal_bayar, $sisa_sekarang);
        $sisa_baru = $sisa_sekarang - $bayar_aktual;
        
        $status_baru = ($sisa_baru <= 0 || $d_tagihan['jenis'] === 'RUTIN') ? 'LUNAS' : 'AKTIF';
        $nama_tagihan = $d_tagihan['nama_tagihan'];
        $deskripsi_trx = "Pembayaran/Pelunasan Tagihan: " . $nama_tagihan;

        // Catat uang keluar pada saat dibayar + path_bukti
        mysqli_query($conn, "INSERT INTO transaksi (user_id, tanggal, kategori_id, akun_id, tipe_transaksi, pemasukan, pengeluaran, path_bukti, deskripsi) 
                             VALUES ($user_id, '$tanggal_trx', $kategori_id, $akun_id, 'NORMAL', 0, $bayar_aktual, $path_bukti_sql, '$deskripsi_trx')");
        
        // Update saldo akun berkurang
        mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir - $bayar_aktual WHERE id = $akun_id");

        // Update tagihan
        mysqli_query($conn, "UPDATE tagihan SET sisa_nominal = $sisa_baru, status = '$status_baru' WHERE id = $tagihan_id");
    }

    echo "<script>alert('Pembayaran/pelunasan berhasil dicatat!'); window.location='tagihan.php';</script>";
    exit;
}

// Proses Simpan Tagihan Baru (Dengan Input Bukti Awal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_tagihan'])) {
    $nama_tagihan = mysqli_real_escape_string($conn, $_POST['nama_tagihan']);
    $jenis = $_POST['jenis']; // HUTANG, PIUTANG, RUTIN
    $total_nominal_murni = (float)$_POST['total_nominal']; // Nominal awal tanpa bunga
    $bunga_persen = (isset($_POST['bunga_persen']) && $_POST['bunga_persen'] !== '' && $jenis !== 'RUTIN') ? (float)$_POST['bunga_persen'] : 0;
    $tenggat_waktu = $_POST['tenggat_waktu']; 
    $tanggal_trx = $today; 

    // Validasi Tanggal (Tidak boleh kurang dari hari ini)
    if ($tenggat_waktu < $today) {
        echo "<script>alert('Gagal: Tenggat waktu tidak boleh kurang dari tanggal hari ini!'); window.location='tagihan.php';</script>";
        exit;
    }

    // Validasi Bunga (Maksimal 100%)
    if ($bunga_persen < 0 || $bunga_persen > 100) {
        echo "<script>alert('Gagal: Persentase bunga harus berada di antara 0% sampai 100%!'); window.location='tagihan.php';</script>";
        exit;
    }

    // Jika PIUTANG, cek apakah saldo akun mencukupi nominal murni
    if ($jenis === 'PIUTANG') {
        $akun_id_cek = (int)$_POST['akun_id'];
        $q_akun_piutang = mysqli_query($conn, "SELECT saldo_akhir, nama_akun FROM akun_pembayaran WHERE id = $akun_id_cek AND user_id = $user_id");
        $d_akun_piutang = mysqli_fetch_assoc($q_akun_piutang);
        if ($d_akun_piutang && (float)$d_akun_piutang['saldo_akhir'] < $total_nominal_murni) {
            echo "<script>alert('Peringatan: Saldo akun \"" . htmlspecialchars($d_akun_piutang['nama_akun']) . "\" gak cukup untuk membuat transaksi piutang ini (Saldo: Rp " . number_format($d_akun_piutang['saldo_akhir'], 0, ',', '.') . ")!'); window.location='tagihan.php';</script>";
            exit;
        }
    }

    $frekuensi = ($jenis === 'RUTIN') ? $_POST['frekuensi'] : 'SEKALI';

    // Hitung total nominal setelah bunga
    $nominal_final_tagihan = $total_nominal_murni;
    if ($bunga_persen > 0 && $jenis !== 'RUTIN') {
        $nominal_final_tagihan = $total_nominal_murni + ($total_nominal_murni * ($bunga_persen / 100));
    }
    $sisa_nominal = $nominal_final_tagihan;
    
    // Penanganan aman untuk tempat bayar (link pembayaran)
    $tempat_bayar_sql = !empty($_POST['tempat_bayar']) 
        ? "'" . mysqli_real_escape_string($conn, $_POST['tempat_bayar']) . "'" 
        : "NULL";

    // Handle Upload Bukti ZIP Tagihan Awal
    $path_bukti_awal = null;
    if (isset($_FILES['bukti_awal']) && $_FILES['bukti_awal']['error'] == 0) {
        if (!is_dir($folder_user)) {
            mkdir($folder_user, 0777, true);
        }

        $zip = new ZipArchive();
        $zip_name = 'bukti_awal_' . time() . '.zip';
        $zip_path = $folder_user . '/' . $zip_name; 

        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($_FILES['bukti_awal']['tmp_name'], $_FILES['bukti_awal']['name']);
            $zip->close();
            $path_bukti_awal = $username . '/' . $zip_name; 
        }
    }
    $path_bukti_awal_sql = $path_bukti_awal ? "'$path_bukti_awal'" : "NULL";

    // Simpan ke tabel tagihan
    $bunga_db = ($bunga_persen > 0 && $jenis !== 'RUTIN') ? $bunga_persen : "NULL";
    $query_tagihan = "INSERT INTO tagihan (user_id, nama_tagihan, jenis, total_nominal, sisa_nominal, bunga_persen, tenggat_waktu, status, tempat_bayar, frekuensi) 
                      VALUES ($user_id, '$nama_tagihan', '$jenis', $nominal_final_tagihan, $sisa_nominal, $bunga_db, '$tenggat_waktu', 'AKTIF', $tempat_bayar_sql, '$frekuensi')";
    
    if (mysqli_query($conn, $query_tagihan)) {
        // Jika BUKAN RUTIN (HUTANG / PIUTANG), catat transaksi awal menggunakan nominal murni + bukti ZIP
        if ($jenis !== 'RUTIN') {
            $akun_id = (int)$_POST['akun_id'];
            $kategori_id = (int)$_POST['kategori_id'];
            $deskripsi_trx = "Tagihan Awal (Pokok): " . $nama_tagihan . " (" . $jenis . ")";

            if ($jenis == 'HUTANG') {
                mysqli_query($conn, "INSERT INTO transaksi (user_id, tanggal, kategori_id, akun_id, tipe_transaksi, pemasukan, pengeluaran, path_bukti, deskripsi) 
                                     VALUES ($user_id, '$tanggal_trx', $kategori_id, $akun_id, 'NORMAL', $total_nominal_murni, 0, $path_bukti_awal_sql, '$deskripsi_trx')");
                mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir + $total_nominal_murni WHERE id = $akun_id");
            } else {
                mysqli_query($conn, "INSERT INTO transaksi (user_id, tanggal, kategori_id, akun_id, tipe_transaksi, pemasukan, pengeluaran, path_bukti, deskripsi) 
                                     VALUES ($user_id, '$tanggal_trx', $kategori_id, $akun_id, 'NORMAL', 0, $total_nominal_murni, $path_bukti_awal_sql, '$deskripsi_trx')");
                mysqli_query($conn, "UPDATE akun_pembayaran SET saldo_akhir = saldo_akhir - $total_nominal_murni WHERE id = $akun_id");
            }
        }
    }

    echo "<script>alert('Tagihan berhasil disimpan!'); window.location='tagihan.php';</script>";
    exit;
}
?>

<div class="container">
    <h2>Manajemen Tagihan, Hutang, & Piutang</h2>

    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px; border-radius: 6px;">
        <h3>Tambah Tagihan Baru</h3>
        <form method="POST" enctype="multipart/form-data" id="tagihanForm">
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Nama Tagihan / Pihak Terkait</label>
                <input type="text" name="nama_tagihan" required placeholder="Contoh: Budi / Listrik PLN / PDAM" style="width: 100%; padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Jenis Tagihan</label>
                <select name="jenis" id="jenis_tagihan" onchange="toggleFormJenis()" style="width: 100%; padding: 6px;">
                    <option value="HUTANG">Hutang (Pemasukan Awal)</option>
                    <option value="PIUTANG">Piutang (Pengeluaran Awal)</option>
                    <option value="RUTIN">Rutin / Utilitas (Listrik/Air/Pajak)</option>
                </select>
            </div>

            <div id="konfirmasi_awal_wrapper">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Akun Pembayaran / Penerima</label>
                    <select name="akun_id" id="input_akun_id" style="width: 100%; padding: 6px;">
                        <?php foreach($akun_list as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format($a['saldo_akhir'], 0, ',', '.') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Kategori (Kecuali Umum)</label>
                    <select name="kategori_id" id="input_kategori_id" style="width: 100%; padding: 6px;">
                        <?php foreach($kategori_list as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Bukti Awal (Akan dikemas ke format .zip otomatis):</label>
                    <input type="file" name="bukti_awal" style="width: 100%;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label>Total Nominal Murni (Pokok Awal)</label>
                <input type="text" class="format-uang" data-hidden="total_nominal" required placeholder="0" style="width: 100%; padding: 6px;">
                <input type="hidden" name="total_nominal" id="total_nominal">
            </div>
            
            <div class="form-group" id="bunga_wrapper" style="margin-bottom: 12px;">
                <label>Bunga (%) - Opsional (Maksimal 100)</label>
                <input type="number" step="0.01" min="0" max="100" name="bunga_persen" id="bunga_persen" placeholder="0.00" style="width: 100%; padding: 6px;">
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label>Tenggat Waktu / Tanggal Jatuh Tempo</label>
                <input type="date" name="tenggat_waktu" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Tempat Bayar / Link Pembayaran (Opsional)</label>
                <input type="url" name="tempat_bayar" placeholder="Contoh: https://app.midtrans.com/..." style="width: 100%; padding: 6px;">
            </div>
            <div class="form-group" id="frekuensi_wrapper" style="display:none; margin-bottom: 12px;">
                <label>Frekuensi Rutin</label>
                <select name="frekuensi" style="width: 100%; padding: 6px;">
                    <option value="BULANAN">Bulanan</option>
                    <option value="TAHUNAN">Tahunan</option>
                </select>
            </div>

            <button type="submit" name="tambah_tagihan" style="background: #2c3e50; color: #fff; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Simpan Tagihan</button>
        </form>
    </div>

    <div class="table-wrap" style="max-width: 100%; max-height: 450px; overflow-y: auto; overflow-x: auto; border: 1px solid #e0e0e0; border-radius: 6px; background: #fff;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="position: sticky; top: 0; background: #3498db; color: #fff; z-index: 5;">
                <tr>
                    <th style="padding: 10px;">Nama Tagihan</th>
                    <th>Jenis</th>
                    <th>Total+Bunga</th>
                    <th>Sisa</th>
                    <th>Bunga</th>
                    <th>Frekuensi</th>
                    <th>Tenggat</th>
                    <th>Tempat Bayar</th>
                    <th>Status</th>
                    <th>Aksi / Pelunasan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $query_get = mysqli_query($conn, "SELECT * FROM tagihan WHERE user_id = '$user_id'");
                while($row = mysqli_fetch_assoc($query_get)):
                    $id_tagihan = (int)$row['id'];
                    $sisa_nominal = (float)$row['sisa_nominal'];
                    $status = $row['status'];
                    $frekuensi = $row['frekuensi'];
                    $original_tenggat = $row['tenggat_waktu'];
                    $tenggat_db = new DateTime($original_tenggat);
                    $tenggat_db->setTime(0, 0, 0);

                    if ($status === 'LUNAS' && $frekuensi !== 'SEKALI' && $tenggat_db < $today_dt) {
                        $new_tenggat = clone $tenggat_db;
                        while ($new_tenggat < $today_dt) {
                            if ($frekuensi === 'BULANAN') {
                                $new_tenggat->modify('+1 month');
                            } elseif ($frekuensi === 'TAHUNAN') {
                                $new_tenggat->modify('+1 year');
                            } else {
                                break;
                            }
                        }
                        $new_date_str = $new_tenggat->format('Y-m-d');
                        $total_awal = (float)$row['total_nominal'];
                        mysqli_query($conn, "UPDATE tagihan SET tenggat_waktu = '$new_date_str', sisa_nominal = $total_awal, status = 'AKTIF' WHERE id = $id_tagihan AND user_id = $user_id");
                        $row['status'] = 'AKTIF';
                        $row['sisa_nominal'] = $total_awal;
                        $row['tenggat_waktu'] = $new_date_str;
                    }

                    if ($status === 'LUNAS' && $frekuensi === 'SEKALI' && $sisa_nominal <= 0) {
                        mysqli_query($conn, "DELETE FROM tagihan WHERE id = $id_tagihan AND user_id = $user_id");
                        continue;
                    }
                ?>
                <tr style="border-bottom: 1px solid #eee; text-align: center;">
                    <td style="padding: 10px;"><?= htmlspecialchars($row['nama_tagihan']) ?></td>
                    <td><?= htmlspecialchars($row['jenis']) ?></td>
                    <td>Rp <?= number_format($row['total_nominal'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['sisa_nominal'], 0, ',', '.') ?></td>
                    <td><?= $row['bunga_persen'] ? $row['bunga_persen'] . '%' : '-' ?></td>
                    <td><?= htmlspecialchars($row['frekuensi']) ?></td>
                    <td><?= htmlspecialchars($row['tenggat_waktu']) ?></td>
                    <td style="padding: 8px;">
                        <?php if (!empty($row['tempat_bayar'])): ?>
                            <a href="<?= htmlspecialchars($row['tempat_bayar']) ?>" target="_blank" style="color: #3498db; text-decoration: underline; font-size: 12px;">Buka Link</a>
                        <?php else: ?>
                            <span style="color: #999; font-size: 12px;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><span style="padding: 3px 8px; border-radius: 3px; background: <?= $row['status'] == 'AKTIF' ? '#f39c12' : '#2ecc71' ?>; color: #fff;"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td style="padding: 8px;">
                        <?php if ($row['status'] === 'AKTIF' && $row['sisa_nominal'] > 0): ?>
                            <button onclick="bukaModalPelunasan(<?= $id_tagihan ?>, '<?= htmlspecialchars($row['nama_tagihan'], ENT_QUOTES) ?>', <?= $row['sisa_nominal'] ?>)" style="background: #27ae60; color: #fff; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; margin-right: 5px;">Bayar / Lunas</button>
                        <?php endif; ?>
                        <a href="tagihan.php?hapus_id=<?= $id_tagihan ?>" style="color:red; font-size: 12px;" onclick="return confirm('Yakin ingin menghapus tagihan ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalPelunasan" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
    <div style="background: #fff; padding: 25px; width: 400px; border-radius: 6px; position: relative;">
        <h3>Konfirmasi Pelunasan Tagihan</h3>
        <p id="infoNamaTagihan" style="font-weight: bold; margin-bottom: 15px;"></p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="tagihan_id" id="modal_tagihan_id">
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Pilih Akun Pembayaran</label>
                <select name="akun_id" required style="width: 100%; padding: 6px;">
                    <?php foreach($akun_list as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format($a['saldo_akhir'], 0, ',', '.') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Pilih Kategori Pembayaran</label>
                <select name="kategori_id" required style="width: 100%; padding: 6px;">
                    <?php foreach($kategori_list as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Nominal Pembayaran</label>
                <input type="number" step="any" name="nominal_bayar" id="modal_nominal_bayar" required style="width: 100%; padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Bukti Pembayaran (Akan dikemas ke format .zip otomatis):</label>
                <input type="file" name="bukti_bayar_lunas" style="width: 100%;">
            </div>
            <button type="submit" name="proses_lunas" style="background: #27ae60; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Konfirmasi Bayar</button>
            <button type="button" onclick="tutupModalPelunasan()" style="background: #7f8c8d; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">Batal</button>
        </form>
    </div>
</div>

<script>
function toggleFormJenis() {
    const jenis = document.getElementById('jenis_tagihan').value;
    const frekuensiWrapper = document.getElementById('frekuensi_wrapper');
    const konfirmasiAwal = document.getElementById('konfirmasi_awal_wrapper');
    const bungaWrapper = document.getElementById('bunga_wrapper');
    const inputAkun = document.getElementById('input_akun_id');
    const inputKat = document.getElementById('input_kategori_id');
    const inputBunga = document.getElementById('bunga_persen');

    if (jenis === 'RUTIN') {
        frekuensiWrapper.style.display = 'block';
        konfirmasiAwal.style.display = 'none'; 
        bungaWrapper.style.display = 'none'; 
        inputAkun.removeAttribute('required');
        inputKat.removeAttribute('required');
        inputBunga.value = ''; 
    } else {
        frekuensiWrapper.style.display = 'none';
        konfirmasiAwal.style.display = 'block';
        bungaWrapper.style.display = 'block'; 
        inputAkun.setAttribute('required', 'true');
        inputKat.setAttribute('required', 'true');
    }
}

function bukaModalPelunasan(id, nama, sisa) {
    document.getElementById('modal_tagihan_id').value = id;
    document.getElementById('infoNamaTagihan').innerText = "Tagihan: " + nama + " (Sisa: Rp " + sisa.toLocaleString('id-ID') + ")";
    document.getElementById('modal_nominal_bayar').value = sisa;
    document.getElementById('modalPelunasan').style.display = 'flex';
}

function tutupModalPelunasan() {
    document.getElementById('modalPelunasan').style.display = 'none';
}

document.querySelectorAll('input.format-uang').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, "");
        let hiddenInput = document.querySelector('input[name="' + this.getAttribute('data-hidden') + '"]');
        if (hiddenInput) hiddenInput.value = value;
        this.value = value ? value.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
    });
});

// Validasi input bunga real-time di form
document.getElementById('bunga_persen').addEventListener('input', function() {
    if (this.value > 100) this.value = 100;
    if (this.value < 0) this.value = 0;
});

window.onload = function() {
    toggleFormJenis();
};
</script>

<?php include '../../includes/footer.php'; ?>