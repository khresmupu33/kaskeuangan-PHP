<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$base_url = "../";

include '../includes/header.php';
require_once '../config/koneksi.php';

$today_dt = new DateTime();
$today_dt->setTime(0, 0, 0);
$today = $today_dt->format('Y-m-d'); // 2026-07-06

/*
|--------------------------------------------------------------------------
| Ambil daftar akun user
|--------------------------------------------------------------------------
*/
$daftar_akun = [];
$query_akun = mysqli_query($conn, "SELECT id, nama_akun, saldo_akhir FROM akun_pembayaran WHERE user_id = $user_id ORDER BY id ASC");
while ($akun = mysqli_fetch_assoc($query_akun)) {
    $daftar_akun[] = $akun;
}

/*
|--------------------------------------------------------------------------
| Ambil daftar kategori user
|--------------------------------------------------------------------------
*/
$daftar_kategori = [];
$query_kategori = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori WHERE user_id = $user_id ORDER BY nama_kategori ASC");
while ($kategori = mysqli_fetch_assoc($query_kategori)) {
    $daftar_kategori[] = $kategori;
}

/*
|--------------------------------------------------------------------------
| Hitung saldo awal akun dari saldo_akhir - mutasi transaksi
| Agar running total di tabel konsisten dan total terakhir = saldo card
|--------------------------------------------------------------------------
*/
$saldo_awal_map = [];
$saldo_akhir_map = [];

foreach ($daftar_akun as $akun) {
    $akun_id = (int) $akun['id'];
    $saldo_akhir = (float) $akun['saldo_akhir'];
    $saldo_akhir_map[$akun_id] = $saldo_akhir;

    $q_mutasi = mysqli_query($conn, "
        SELECT  
            COALESCE(SUM(pemasukan), 0) AS total_masuk,
            COALESCE(SUM(pengeluaran), 0) AS total_keluar
        FROM transaksi
        WHERE user_id = $user_id AND akun_id = $akun_id
    ");
    $d_mutasi = mysqli_fetch_assoc($q_mutasi);

    $total_masuk = (float) $d_mutasi['total_masuk'];
    $total_keluar = (float) $d_mutasi['total_keluar'];

    $saldo_awal_map[$akun_id] = $saldo_akhir - ($total_masuk - $total_keluar);
}

/*
|--------------------------------------------------------------------------
| Total saldo bersih card = total saldo seluruh akun
|--------------------------------------------------------------------------
*/
$query_total_saldo = mysqli_query($conn, "
    SELECT COALESCE(SUM(saldo_akhir), 0) AS total_semua_akun
    FROM akun_pembayaran
    WHERE user_id = $user_id
");
$data_total_saldo = mysqli_fetch_assoc($query_total_saldo);
$total_saldo_bersih = (float) ($data_total_saldo['total_semua_akun'] ?? 0);

/*
|--------------------------------------------------------------------------
| Ambil semua transaksi user (untuk hitung running total)
|--------------------------------------------------------------------------
*/
$semua_transaksi = [];
$query_transaksi = mysqli_query($conn, "
    SELECT tr.*, k.nama_kategori, ak.nama_akun
    FROM transaksi tr
    JOIN kategori k ON tr.kategori_id = k.id
    JOIN akun_pembayaran ak ON tr.akun_id = ak.id
    WHERE tr.user_id = $user_id
    ORDER BY tr.tanggal ASC, tr.id ASC
");
while ($row = mysqli_fetch_assoc($query_transaksi)) {
    $semua_transaksi[] = $row;
}

$total_data = count($semua_transaksi);
$batas_edit = max(0, $total_data - 7);

/*
|--------------------------------------------------------------------------
| Ambil target aktif + progress realisasi (Disamakan dengan target.php)
|--------------------------------------------------------------------------
*/
$targets = [];
$query_target = mysqli_query($conn, "
    SELECT t.*, k.nama_kategori
    FROM target t
    JOIN kategori k ON t.kategori_id = k.id
    WHERE t.user_id = $user_id AND t.status = 'AKTIF'
    ORDER BY t.tenggat_waktu ASC, t.id ASC
");

while ($target = mysqli_fetch_assoc($query_target)) {
    $id_target = (int) $target['id'];
    $kategori_id = (int) $target['kategori_id'];
    $nominal_maksimal = (float) $target['nominal_maksimal'];
    $original_tenggat = $target['tenggat_waktu'];

    // Tentukan awal rentang tanggal ($start_date) mundur 1 bulan/tahun dari tenggat asli
    if ($target['periode_target'] == 'BULANAN') {
        $start_date = date('Y-m-d', strtotime($original_tenggat . ' -1 month'));
    } elseif ($target['periode_target'] == 'TAHUNAN') {
        $start_date = date('Y-m-d', strtotime($original_tenggat . ' -1 year'));
    } else {
        $start_date = date('Y-m-d', strtotime($original_tenggat . ' -1 month')); 
    }

    $end_date = $original_tenggat;

    // Ambil pengeluaran dalam rentang waktu yang presisi
    $sql_realisasi = "
        SELECT COALESCE(SUM(pengeluaran), 0) AS realisasi
        FROM transaksi
        WHERE user_id = $user_id
          AND kategori_id = $kategori_id
          AND tanggal > '$start_date' 
          AND tanggal <= '$end_date'
    ";

    $res_realisasi = mysqli_query($conn, $sql_realisasi);
    $data_realisasi = mysqli_fetch_assoc($res_realisasi);
    $realisasi = (float) ($data_realisasi['realisasi'] ?? 0);

    $sisa = $nominal_maksimal - $realisasi;
    $persen = $nominal_maksimal > 0 ? min(($realisasi / $nominal_maksimal) * 100, 100) : 0;
    $is_over = $realisasi > $nominal_maksimal;

    $target['realisasi'] = $realisasi;
    $target['sisa'] = $sisa;
    $target['persen'] = $persen;
    $target['is_over'] = $is_over;
    $targets[] = $target;

    // Logika Update / Hapus otomatis tenggat waktu jika sudah lewat hari ini
    $tenggat_db = new DateTime($original_tenggat);
    $tenggat_db->setTime(0, 0, 0);

    if ($tenggat_db < $today_dt) {
        if ($target['tipe_target'] === 'SEKALI') {
            mysqli_query($conn, "DELETE FROM target WHERE id = $id_target AND user_id = $user_id");
        } elseif ($target['tipe_target'] === 'RUTIN') {
            $new_tenggat = clone $tenggat_db;
            while ($new_tenggat < $today_dt) {
                if ($target['periode_target'] === 'BULANAN') {
                    $new_tenggat->modify('+1 month');
                } elseif ($target['periode_target'] === 'TAHUNAN') {
                    $new_tenggat->modify('+1 year');
                } else {
                    break; 
                }
            }
            $new_date_str = $new_tenggat->format('Y-m-d');
            mysqli_query($conn, "UPDATE target SET tenggat_waktu = '$new_date_str' WHERE id = $id_target AND user_id = $user_id");
        }
    }
}

/*
|--------------------------------------------------------------------------
| Ambil transaksi dengan filter
|--------------------------------------------------------------------------
*/
$where_clauses = ["tr.user_id = $user_id"];

if (!empty($_GET['f_akun'])) $where_clauses[] = "tr.akun_id = " . (int)$_GET['f_akun'];
if (!empty($_GET['f_kategori'])) $where_clauses[] = "tr.kategori_id = " . (int)$_GET['f_kategori'];
if (!empty($_GET['f_tipe'])) {
    if ($_GET['f_tipe'] == 'MASUK') $where_clauses[] = "tr.pemasukan > 0";
    if ($_GET['f_tipe'] == 'KELUAR') $where_clauses[] = "tr.pengeluaran > 0";
}
if (!empty($_GET['f_tgl_awal'])) $where_clauses[] = "tr.tanggal >= '" . mysqli_real_escape_string($conn, $_GET['f_tgl_awal']) . "'";
if (!empty($_GET['f_tgl_akhir'])) $where_clauses[] = "tr.tanggal <= '" . mysqli_real_escape_string($conn, $_GET['f_tgl_akhir']) . "'";

$sql_transaksi = "SELECT tr.*, k.nama_kategori, ak.nama_akun 
                  FROM transaksi tr 
                  JOIN kategori k ON tr.kategori_id = k.id 
                  JOIN akun_pembayaran ak ON tr.akun_id = ak.id 
                  WHERE " . implode(' AND ', $where_clauses) . " 
                  ORDER BY tr.tanggal ASC, tr.id ASC";

$semua_transaksi = [];
$query_transaksi = mysqli_query($conn, $sql_transaksi);
while ($row = mysqli_fetch_assoc($query_transaksi)) {
    $semua_transaksi[] = $row;
}
?>

<div class="dashboard-cards dashboard-scroll">
    <div class="info-card total">
        <h3>Saldo Bersih</h3>
        <p><strong>Rp <?php echo number_format($total_saldo_bersih, 0, ',', '.'); ?></strong></p>
    </div>

    <?php foreach ($daftar_akun as $akun): ?>
        <div class="info-card">
            <h4><?php echo htmlspecialchars($akun['nama_akun']); ?></h4>
            <p>Saldo: <strong>Rp <?php echo number_format($akun['saldo_akhir'], 0, ',', '.'); ?></strong></p>
            <p class="small-text">Saldo awal: Rp <?php echo number_format($saldo_awal_map[$akun['id']] ?? 0, 0, ',', '.'); ?></p>
        </div>
    <?php endforeach; ?>
</div>

<h2>Target Anda</h2>
<div class="target-wrapper">
    <?php if (count($targets) > 0): ?>
        <?php foreach ($targets as $target): ?>
            <div class="target-card <?php echo $target['is_over'] ? 'over' : 'normal'; ?>">
                <h4><?php echo htmlspecialchars($target['nama_kategori']); ?></h4>
                <p class="small-text"><?php echo htmlspecialchars($target['deskripsi']); ?></p>

                <p><strong>Target:</strong> Rp <?php echo number_format($target['nominal_maksimal'], 0, ',', '.'); ?></p>
                <p><strong>Terpakai:</strong> Rp <?php echo number_format($target['realisasi'], 0, ',', '.'); ?></p>
                <p><strong>Sisa:</strong> Rp <?php echo number_format(max(0, $target['sisa']), 0, ',', '.'); ?></p>

                <div class="progress-box">
                    <div class="progress-bar <?php echo $target['is_over'] ? 'over' : ''; ?>" style="width: <?php echo min($target['persen'], 100); ?>%;"></div>
                </div>

                <p class="small-text"><?php echo number_format($target['persen'], 1, ',', '.'); ?>% terpakai</p>

                <?php if ($target['is_over']): ?>
                    <p class="warning-text">WARNING: Pengeluaran melebihi target.</p>
                    <p class="small-text">Lebih: Rp <?php echo number_format(abs($target['sisa']), 0, ',', '.'); ?></p>
                <?php endif; ?>

                <p class="small-text">Tipe: <strong><?php echo htmlspecialchars($target['tipe_target']); ?></strong></p>
                <p class="small-text">Tenggat: <strong><?php echo htmlspecialchars($target['tenggat_waktu']); ?></strong></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="info-card">
            <p>Belum ada target aktif.</p>
        </div>
    <?php endif; ?>
</div>

<div class="filter-box" style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
        <div>
            <label>Akun:</label><br>
            <select name="f_akun" class="inline-select">
                <option value="">Semua</option>
                <?php foreach ($daftar_akun as $a): ?>
                    <option value="<?=$a['id']?>" <?=($_GET['f_akun'] ?? '') == $a['id'] ? 'selected' : ''?>><?=$a['nama_akun']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Kategori:</label><br>
            <select name="f_kategori" class="inline-select">
                <option value="">Semua</option>
                <?php foreach ($daftar_kategori as $k): ?>
                    <option value="<?=$k['id']?>" <?=($_GET['f_kategori'] ?? '') == $k['id'] ? 'selected' : ''?>><?=$k['nama_kategori']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Tipe:</label><br>
            <select name="f_tipe" class="inline-select">
                <option value="">Semua</option>
                <option value="MASUK" <?=($_GET['f_tipe'] ?? '') == 'MASUK' ? 'selected' : ''?>>Masuk</option>
                <option value="KELUAR" <?=($_GET['f_tipe'] ?? '') == 'KELUAR' ? 'selected' : ''?>>Keluar</option>
            </select>
        </div>
        <div>
            <label>Dari:</label><br>
            <input type="date" name="f_tgl_awal" class="inline-input" value="<?=$_GET['f_tgl_awal'] ?? ''?>">
        </div>
        <div>
            <label>Sampai:</label><br>
            <input type="date" name="f_tgl_akhir" class="inline-input" value="<?=$_GET['f_tgl_akhir'] ?? ''?>">
        </div>
        <button type="submit" style="padding: 6px 15px; cursor: pointer;">Filter</button>
        <a href="?" style="padding: 6px 15px; background: #eee; text-decoration: none; color: #333; border-radius: 4px;">Reset</a>
    </form>
    <a href="transaksi/cetak_laporan.php?<?php echo http_build_query($_GET); ?>"  
   style="padding: 6px 15px; background: #27ae60; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">
    Cetak PDF
</a>
</div>

<h2>Riwayat Transaksi</h2>
<?php
$transaksi_per_bulan = [];
foreach ($semua_transaksi as $tr) {
    $bulan_tahun = date('F Y', strtotime($tr['tanggal']));
    $transaksi_per_bulan[$bulan_tahun][] = $tr;
}

$saldo_saat_ini = $saldo_awal_map;
?>

<?php if (count($semua_transaksi) === 0): ?>
    <div class="info-card"><p>Belum ada transaksi.</p></div>
<?php else: ?>
    <div class="table-scroll-container" style="max-width: 100%; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; background: #fff; padding: 10px;">
        <?php foreach ($transaksi_per_bulan as $bulan => $transaksi_grup): ?>
            <h3 style="margin-top: 15px; margin-bottom: 10px; color: #333; position: sticky; left: 0;"><?php echo $bulan; ?></h3>
            <div class="table-wrap" style="overflow-x: auto; margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f4f6f7;">
                            <th><input type="checkbox" id="select-all"></th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Kategori</th>
                            <th>Akun</th>
                            <th>Tipe</th>
                            <?php foreach ($daftar_akun as $akun): ?>
                                <th>Saldo <?php echo htmlspecialchars($akun['nama_akun']); ?></th>
                            <?php endforeach; ?>
                            <th>Masuk</th>
                            <th>Keluar</th>
                            <th>Total Saldo</th>
                            <th>Bukti</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaksi_grup as $tr): 
                            $id_akun_tr = (int) $tr['akun_id'];
                            $pemasukan = (float) $tr['pemasukan'];
                            $pengeluaran = (float) $tr['pengeluaran'];

                            $saldo_saat_ini[$id_akun_tr] = ($saldo_saat_ini[$id_akun_tr] ?? 0) + ($pemasukan - $pengeluaran);
                            $running_total = array_sum($saldo_saat_ini);
                            
                            $key = array_search($tr, $semua_transaksi);
                            $is_editable = ($key >= $batas_edit);
                        ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" data-masuk="<?php echo (float)$tr['pemasukan']; ?>" data-keluar="<?php echo (float)$tr['pengeluaran']; ?>"></td>
                                <td class="<?php echo $is_editable ? 'edit-cell' : ''; ?>" data-field="tanggal" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo htmlspecialchars($tr['tanggal'], ENT_QUOTES); ?>" data-type="date">
                                    <?php echo htmlspecialchars($tr['tanggal']); ?>
                                </td>
                                <td class="<?php echo $is_editable ? 'edit-cell' : ''; ?>" data-field="deskripsi" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo htmlspecialchars($tr['deskripsi'], ENT_QUOTES); ?>" data-type="text">
                                    <?php echo htmlspecialchars($tr['deskripsi']); ?>
                                </td>
                                <td class="<?php echo $is_editable ? 'edit-cell' : ''; ?>" data-field="kategori_id" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo (int) $tr['kategori_id']; ?>" data-type="select-kategori">
                                    <?php echo htmlspecialchars($tr['nama_kategori']); ?>
                                </td>
                                <td data-field="akun_id" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo (int) $tr['akun_id']; ?>" data-type="select-akun">
                                    <?php echo htmlspecialchars($tr['nama_akun']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($tr['tipe_transaksi']); ?></td>
                                <?php foreach ($daftar_akun as $akun): ?>
                                    <td>Rp <?php echo number_format($saldo_saat_ini[$akun['id']] ?? 0, 0, ',', '.'); ?></td>
                                <?php endforeach; ?>
                                <td data-field="pemasukan" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo (float) $pemasukan; ?>" data-type="number">
                                    <?php echo number_format($pemasukan, 0, ',', '.'); ?>
                                </td>
                                <td data-field="pengeluaran" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo (float) $pengeluaran; ?>" data-type="number">
                                    <?php echo number_format($pengeluaran, 0, ',', '.'); ?>
                                </td>
                                <td><strong>Rp <?php echo number_format($running_total, 0, ',', '.'); ?></strong></td>
                                <td>
                                    <?php if (!empty($tr['path_bukti'])): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($tr['path_bukti']); ?>" target="_blank">Lihat</a>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_editable): ?>
                                        <a href="hapus_transaksi.php?id=<?php echo $tr['id']; ?>" onclick="return confirm('Yakin hapus? Saldo akan terkoreksi.')" style="color:red;">Hapus</a>
                                    <?php else: ?> <span class="locked-text">Locked</span> <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
const daftarAkun = <?php echo json_encode($daftar_akun, JSON_UNESCAPED_UNICODE); ?>;
const daftarKategori = <?php echo json_encode($daftar_kategori, JSON_UNESCAPED_UNICODE); ?>;
function formatRupiah(angka) {
    angka = parseFloat(angka || 0);
    return angka.toLocaleString('id-ID');
}
function activateInlineEdit(td) {
    if (!td.classList.contains('edit-cell')) return;
    if (td.querySelector('input, select')) return;
    const field = td.dataset.field;
    const id = td.dataset.id;
    const value = td.dataset.value || '';
    const type = td.dataset.type || 'text';
    const oldText = td.textContent.trim();
    td.dataset.oldText = oldText;
    let el;
    if (type === 'date') {
        el = document.createElement('input');
        el.type = 'date';
        el.value = value;
        el.className = 'inline-input';
    } else if (type === 'number') {
        el = document.createElement('input');
        el.type = 'number';
        el.step = '0.01';
        el.min = '0';
        el.value = value;
        el.className = 'inline-input';
    } else if (type === 'select-akun') {
        el = document.createElement('select');
        el.className = 'inline-select';
        daftarAkun.forEach(function(akun) {
            const option = document.createElement('option');
            option.value = akun.id;
            option.textContent = akun.nama_akun;
            if (String(akun.id) === String(value)) {
                option.selected = true;
            }
            el.appendChild(option);
        });
    } else if (type === 'select-kategori') {
        el = document.createElement('select');
        el.className = 'inline-select';
        daftarKategori.forEach(function(kategori) {
            const option = document.createElement('option');
            option.value = kategori.id;
            option.textContent = kategori.nama_kategori;
            if (String(kategori.id) === String(value)) {
                option.selected = true;
            }
            el.appendChild(option);
        });
    } else {
        el = document.createElement('input');
        el.type = 'text';
        el.value = value;
        el.className = 'inline-input';
    }
    td.innerHTML = '';
    td.appendChild(el);
    el.focus();
    if (el.tagName === 'INPUT') {
        el.select();
    }
    el.addEventListener('blur', function() {
        saveInlineEdit(td, el, id, field, type);
    });
    el.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            el.blur();
        } else if (e.key === 'Escape') {
            td.innerHTML = td.dataset.oldText || '';
        }
    });
    if (el.tagName === 'SELECT') {
        el.addEventListener('change', function() {
            saveInlineEdit(td, el, id, field, type);
        });
    }
}
function saveInlineEdit(td, el, id, field, type) {
    let value = el.value;
    if (type === 'text') {
        value = value.trim();
    }
    const formData = new FormData();
    formData.append('id', id);
    formData.append('field', field);
    formData.append('value', value);
    td.classList.add('cell-saving');
    fetch('update_transaksi_inline.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        td.classList.remove('cell-saving');
        if (!res.success) {
            td.classList.add('cell-error');
            td.innerHTML = td.dataset.oldText || '';
            alert(res.message || 'Gagal menyimpan perubahan.');
            setTimeout(() => td.classList.remove('cell-error'), 1200);
            return;
        }
        if (type === 'select-akun' || type === 'select-kategori') {
            td.dataset.value = res.value_id ?? value;
            td.innerHTML = res.display ?? value;
        } else if (type === 'number') {
            td.dataset.value = res.raw_value ?? value;
            td.innerHTML = res.display ?? formatRupiah(value);
        } else {
            td.dataset.value = res.raw_value ?? value;
            td.innerHTML = res.display ?? value;
        }
        td.classList.add('cell-success');
        setTimeout(() => {
            td.classList.remove('cell-success');
            window.location.reload();
        }, 500);
    })
    .catch(error => {
        td.classList.remove('cell-saving');
        td.classList.add('cell-error');
        td.innerHTML = td.dataset.oldText || '';
        alert('Terjadi kesalahan saat menyimpan data.');
        setTimeout(() => td.classList.remove('cell-error'), 1200);
        console.error(error);
    });
}
document.addEventListener('click', function(e) {
    const td = e.target.closest('td.edit-cell');
    if (td) {
        activateInlineEdit(td);
    }
});
</script>

<div id="calculator-box" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #2c3e50; color: white; padding: 15px 25px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 1000; font-weight: bold; transition: all 0.3s ease;">
    Terpilih: Masuk: <span id="total-masuk">0</span> | Keluar: <span id="total-keluar">0</span>
</div>
<script>
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAll = document.getElementById('select-all');
    const calcBox = document.getElementById('calculator-box');
    const totalMasukEl = document.getElementById('total-masuk');
    const totalKeluarEl = document.getElementById('total-keluar');

    function hitungTotal() {
        let totalM = 0;
        let totalK = 0;
        let checkedCount = 0;

        document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
            totalM += parseFloat(cb.dataset.masuk);
            totalK += parseFloat(cb.dataset.keluar);
            checkedCount++;
        });

        totalMasukEl.textContent = 'Rp ' + totalM.toLocaleString('id-ID');
        totalKeluarEl.textContent = 'Rp ' + totalK.toLocaleString('id-ID');

        if (checkedCount > 0) {
            calcBox.style.display = 'block';
        } else {
            calcBox.style.display = 'none';
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', hitungTotal);
    });

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            hitungTotal();
        });
    }
</script>

<?php include '../includes/footer.php'; ?>