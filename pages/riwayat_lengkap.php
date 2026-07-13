<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'guest';
$base_url = "../";

require_once '../config/koneksi.php';

$today_dt = new DateTime();
$today_dt->setTime(0, 0, 0);
$today = $today_dt->format('Y-m-d');

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
| Ambil transaksi dengan filter
|--------------------------------------------------------------------------
*/
$where_clauses = ["tr.user_id = $user_id"];

$filter_akun_id = !empty($_GET['f_akun']) ? (int)$_GET['f_akun'] : 0;

if ($filter_akun_id > 0) $where_clauses[] = "tr.akun_id = " . $filter_akun_id;
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

$tampil_akun = $daftar_akun;
if ($filter_akun_id > 0) {
    $tampil_akun = array_filter($daftar_akun, function($a) use ($filter_akun_id) {
        return (int)$a['id'] === $filter_akun_id;
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasKeuangan Khresmupu</title>
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>includes/KasKeuanganKhresmupu.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
        }
        .container {
            width: 95%;
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .month-group-container {
            margin-bottom: 30px;
        }
        .month-section-title {
            position: sticky;
            top: 0;
            background-color: #eaeded;
            color: #2c3e50;
            z-index: 12;
            padding: 10px 20px;
            margin-top: 25px;
            margin-bottom: 0;
            border-bottom: 2px solid #2c3e50;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .table-wrap {
            overflow: auto;
            max-height: 65vh;
            max-width: 100%;
            margin-bottom: 20px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            background: #fff;
        }
        table thead {
            position: sticky;
            top: 0;
            z-index: 11;
        }
        table thead th {
            position: sticky;
            top: 0;
            background-color: #2c3e50;
            color: white;
            z-index: 11;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
            white-space: nowrap;
        }
        .inline-input, .inline-select {
            width: 100%;
            min-width: 120px;
            box-sizing: border-box;
            padding: 6px 8px;
            border: 1px solid #999;
            border-radius: 6px;
            font-size: 14px;
        }
        .edit-cell {
            background: #fff8dc;
            outline: none;
            cursor: pointer;
        }
        .edit-cell:hover {
            background: #fff1b8;
        }
        .cell-saving { background: #dff9fb !important; }
        .cell-success { background: #c7f7d4 !important; }
        .cell-error { background: #ffd6d6 !important; }
        .info-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-top: 10px;
        }

        /* =========================================
            RESPONSIF UNTUK HP (Layar <= 768px)
            ========================================= */
        @media screen and (max-width: 768px) {
            .container { 
                width: 100%; 
                padding: 10px; 
            }
            .header-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            .filter-box form {
                flex-direction: column;
                align-items: stretch !important;
            }
            .filter-box form > div {
                width: 100%;
            }
            .filter-box button, .filter-box a {
                width: 100%;
                text-align: center;
            }
            table th, table td { 
                padding: 8px; 
                font-size: 13px; 
            }
            #calculator-box {
                left: 10px;
                right: 10px;
                bottom: 10px;
                text-align: center;
                border-radius: 12px !important;
                padding: 12px 15px !important;
                font-size: 12px;
            }
        }
        /* Overlay & Lingkaran Loading Spinner (Benar-benar di paling depan) */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2147483647;
            opacity: 0; 
            pointer-events: none;
            transition: opacity 0.4s ease-in-out;
        }

        #page-loader.show {
            opacity: 1;
            pointer-events: auto;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(44, 62, 80, 0.15);
            border-top: 5px solid #2c3e50; 
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            position: relative;
            z-index: 2147483647;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="page-loader" class="show">
        <div class="spinner"></div>
    </div>
    <main class="container">
        <div class="header-flex" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Semua Riwayat Transaksi</h2>
            <a href="dashboard.php" style="background: #2c3e50; color: #fff; padding: 6px 14px; border-radius: 4px; text-decoration: none; font-size: 13px;">&larr; Kembali ke Dashboard</a>
        </div>

        <div class="filter-box" style="background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <div>
                    <label>Akun:</label>
                    <select name="f_akun" class="inline-select">
                        <option value="">Semua</option>
                        <?php foreach ($daftar_akun as $a): ?>
                            <option value="<?=$a['id']?>" <?=($filter_akun_id) == $a['id'] ? 'selected' : ''?>><?=$a['nama_akun']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Kategori:</label>
                    <select name="f_kategori" class="inline-select">
                        <option value="">Semua</option>
                        <?php foreach ($daftar_kategori as $k): ?>
                            <option value="<?=$k['id']?>" <?=($_GET['f_kategori'] ?? '') == $k['id'] ? 'selected' : ''?>><?=$k['nama_kategori']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Tipe:</label>
                    <select name="f_tipe" class="inline-select">
                        <option value="">Semua</option>
                        <option value="MASUK" <?=($_GET['f_tipe'] ?? '') == 'MASUK' ? 'selected' : ''?>>Masuk</option>
                        <option value="KELUAR" <?=($_GET['f_tipe'] ?? '') == 'KELUAR' ? 'selected' : ''?>>Keluar</option>
                    </select>
                </div>
                <div>
                    <label>Dari:</label>
                    <input type="date" name="f_tgl_awal" class="inline-input" value="<?=$_GET['f_tgl_awal'] ?? ''?>">
                </div>
                <div>
                    <label>Sampai:</label>
                    <input type="date" name="f_tgl_akhir" class="inline-input" value="<?=$_GET['f_tgl_akhir'] ?? ''?>">
                </div>
                <button type="submit" style="padding: 8px 15px; cursor: pointer; background: #2c3e50; color: white; border: none; border-radius: 4px;">Filter</button>
                <a href="riwayat_lengkap.php" style="padding: 8px 15px; background: #eee; text-decoration: none; color: #333; border-radius: 4px; display: inline-block;">Reset</a>
            </form>
            <a href="transaksi/cetak_laporan.php?<?php echo http_build_query($_GET); ?>"  
               style="padding: 8px 15px; background: #2c3e50; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 12px;">
                Cetak PDF
            </a>
        </div>

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
            <div>
                <?php foreach ($transaksi_per_bulan as $bulan => $transaksi_grup): ?>
                    <div class="month-group-container">
                        <h3 class="month-section-title" data-bulan="<?php echo $bulan; ?>">
                            <?php echo $bulan; ?>
                        </h3>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>Tanggal</th>
                                        <th>Deskripsi</th>
                                        <th>Kategori</th>
                                        <th>Akun</th>
                                        <th>Tipe</th>
                                        <?php foreach ($tampil_akun as $akun): ?>
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
                                    ?>
                                        <tr>
                                            <td><input type="checkbox" class="row-checkbox" data-masuk="<?php echo (float)$tr['pemasukan']; ?>" data-keluar="<?php echo (float)$tr['pengeluaran']; ?>"></td>
                                            <td class="edit-cell" data-field="tanggal" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo htmlspecialchars($tr['tanggal'], ENT_QUOTES); ?>" data-type="date">
                                                <?php echo htmlspecialchars($tr['tanggal']); ?>
                                            </td>
                                            <td class="edit-cell" data-field="deskripsi" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo htmlspecialchars($tr['deskripsi'], ENT_QUOTES); ?>" data-type="text">
                                                <?php echo htmlspecialchars($tr['deskripsi']); ?>
                                            </td>
                                            <td class="edit-cell" data-field="kategori_id" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo (int) $tr['kategori_id']; ?>" data-type="select-kategori">
                                                <?php echo htmlspecialchars($tr['nama_kategori']); ?>
                                            </td>
                                            <td data-field="akun_id" data-id="<?php echo (int) $tr['id']; ?>" data-value="<?php echo (int) $tr['akun_id']; ?>" data-type="select-akun">
                                                <?php echo htmlspecialchars($tr['nama_akun']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($tr['tipe_transaksi']); ?></td>
                                            <?php foreach ($tampil_akun as $akun): ?>
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
                                                    <?php  
                                                        $ext = strtolower(pathinfo($tr['path_bukti'], PATHINFO_EXTENSION));
                                                        $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                                        $is_zip = ($ext === 'zip');
                                                        $file_path = '../assets/img/' . $tr['path_bukti'];
                                                    ?>
                                                    <?php if ($is_image): ?>
                                                        <a href="javascript:void(0);" onclick="bukaModal(this)">Lihat</a>
                                                        <div class="overlay-bukti" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.85); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
                                                            <div style="position: relative; max-width: 95%; max-height: 95%; text-align: center;">
                                                                <span onclick="tutupModal(this)" style="position: absolute; top: -45px; right: 0; font-size: 35px; color: white; cursor: pointer; font-weight: bold; background: rgba(0,0,0,0.6); width: 42px; height: 42px; text-align: center; line-height: 40px; border-radius: 50%;">&times;</span>
                                                                <img src="<?php echo htmlspecialchars($file_path); ?>" alt="Bukti" style="max-width: 100%; max-height: 85vh; border-radius: 8px;">
                                                            </div>
                                                        </div>
                                                    <?php elseif ($is_zip): ?>
                                                        <a href="javascript:void(0);" onclick="bukaModalZip(this, '<?php echo htmlspecialchars($tr['path_bukti'], ENT_QUOTES); ?>')">Lihat Isi ZIP</a>
                                                        <div class="overlay-bukti" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.85); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
                                                            <div style="position: relative; width: 600px; max-width: 95%; background: white; padding: 25px; border-radius: 8px; max-height: 85vh; overflow-y: auto;">
                                                                <span onclick="tutupModal(this)" style="position: absolute; top: 12px; right: 18px; font-size: 28px; cursor: pointer;">&times;</span>
                                                                <h4>Daftar File & Pratinjau dalam ZIP:</h4>
                                                                <div class="zip-content-container">Memuat isi...</div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <a href="<?php echo htmlspecialchars($file_path); ?>" target="_blank">Lihat File</a>
                                                    <?php endif; ?>
                                                <?php else: ?> - <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="hapus_transaksi.php?id=<?php echo $tr['id']; ?>" onclick="return confirm('Yakin hapus? Saldo akan terkoreksi.')" style="color:red;">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="calculator-box" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #2c3e50; color: white; padding: 15px 25px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 1000; font-weight: bold;">
        Terpilih: Masuk: <span id="total-masuk">0</span> | Keluar: <span id="total-keluar">0</span>
    </div>

    <script>
    function bukaModal(el) { var ov = el.nextElementSibling; if (ov) ov.style.display = "flex"; }
    function tutupModal(el) { var ov = el.closest('.overlay-bukti'); if (ov) ov.style.display = "none"; }
    function bukaModalZip(el, pathBukti) {
        var ov = el.nextElementSibling;
        if (ov) {
            ov.style.display = "flex";
            var container = ov.querySelector('.zip-content-container');
            container.innerHTML = "Memuat isi...";
            fetch('get_zip_content.php?file=' + encodeURIComponent(pathBukti))
                .then(res => res.text())
                .then(html => { container.innerHTML = html; })
                .catch(() => { container.innerHTML = "Gagal memuat isi ZIP."; });
        }
    }

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
        if (el.tagName === 'INPUT') el.select();
        el.addEventListener('blur', function() { saveInlineEdit(td, el, id, field, type); });
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); el.blur(); }
            else if (e.key === 'Escape') { td.innerHTML = td.dataset.oldText || ''; }
        });
        if (el.tagName === 'SELECT') {
            el.addEventListener('change', function() { saveInlineEdit(td, el, id, field, type); });
        }
    }

    function saveInlineEdit(td, el, id, field, type) {
        let value = el.value;
        if (type === 'text') value = value.trim();
        const formData = new FormData();
        formData.append('id', id);
        formData.append('field', field);
        formData.append('value', value);
        td.classList.add('cell-saving');
        fetch('update_transaksi_inline.php', { method: 'POST', body: formData })
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
            if (type === 'select-kategori') {
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
            setTimeout(() => { td.classList.remove('cell-success'); window.location.reload(); }, 500);
        })
        .catch(() => {
            td.classList.remove('cell-saving');
            td.classList.add('cell-error');
            td.innerHTML = td.dataset.oldText || '';
            alert('Terjadi kesalahan saat menyimpan data.');
            setTimeout(() => td.classList.remove('cell-error'), 1200);
        });
    }

    document.addEventListener('click', function(e) {
        const td = e.target.closest('td.edit-cell');
        if (td) activateInlineEdit(td);
    });

    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAll = document.getElementById('select-all');
    const calcBox = document.getElementById('calculator-box');
    const totalMasukEl = document.getElementById('total-masuk');
    const totalKeluarEl = document.getElementById('total-keluar');

    function hitungTotal() {
        let totalM = 0, totalK = 0, checkedCount = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                totalM += parseFloat(cb.dataset.masuk);
                totalK += parseFloat(cb.dataset.keluar);
                checkedCount++;
            }
        });
        totalMasukEl.textContent = 'Rp ' + totalM.toLocaleString('id-ID');
        totalKeluarEl.textContent = 'Rp ' + totalK.toLocaleString('id-ID');
        calcBox.style.display = checkedCount > 0 ? 'block' : 'none';
    }

    checkboxes.forEach(cb => cb.addEventListener('change', hitungTotal));
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            hitungTotal();
        });
    }

    // Hilangkan loader dan jalankan fade-in saat halaman selesai dimuat
    window.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('page-loader');
        document.body.classList.add('fade-in');
        setTimeout(() => {
            loader.classList.remove('show');
        }, 50);
    });

    // Tampilkan loader dan jalankan fade-out saat pindah halaman
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.startsWith('#') && link.target !== '_blank' && !link.hasAttribute('onclick')) {
            const targetUrl = link.href;
            if (targetUrl.includes(window.location.hostname) || targetUrl.startsWith('/')) {
                e.preventDefault();
                const loader = document.getElementById('page-loader');
                loader.classList.add('show');
                document.body.classList.remove('fade-in');
                document.body.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 400);
            }
        }
    });
    </script>
</body>
</html>