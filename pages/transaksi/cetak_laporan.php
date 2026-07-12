<?php
session_start();
require_once '../../config/koneksi.php';

if (!isset($_SESSION['user_id'])) exit("Akses ditolak");
$user_id = (int)$_SESSION['user_id'];

// 1. Ambil data akun dan hitung saldo awal (sama seperti di dashboard)
$res_akun = mysqli_query($conn, "SELECT id, nama_akun, saldo_akhir FROM akun_pembayaran WHERE user_id = $user_id");
$daftar_akun = [];
$saldo_saat_ini = [];

while ($akun = mysqli_fetch_assoc($res_akun)) {
    $akun_id = (int) $akun['id'];
    $daftar_akun[] = $akun;
    
    // Hitung total mutasi untuk mendapatkan saldo awal yang benar
    $q_mutasi = mysqli_query($conn, "SELECT SUM(pemasukan) as in_sum, SUM(pengeluaran) as out_sum FROM transaksi WHERE user_id = $user_id AND akun_id = $akun_id");
    $mutasi = mysqli_fetch_assoc($q_mutasi);
    
    $saldo_awal = (float)$akun['saldo_akhir'] - ((float)$mutasi['in_sum'] - (float)$mutasi['out_sum']);
    $saldo_saat_ini[$akun_id] = $saldo_awal;
}

// 2. Ambil transaksi dengan filter (sesuai $_GET dari dashboard)
$where_clauses = ["tr.user_id = $user_id"];
if (!empty($_GET['f_akun'])) $where_clauses[] = "tr.akun_id = " . (int)$_GET['f_akun'];
if (!empty($_GET['f_kategori'])) $where_clauses[] = "tr.kategori_id = " . (int)$_GET['f_kategori'];
if (!empty($_GET['f_tipe'])) {
    if ($_GET['f_tipe'] == 'MASUK') $where_clauses[] = "tr.pemasukan > 0";
    if ($_GET['f_tipe'] == 'KELUAR') $where_clauses[] = "tr.pengeluaran > 0";
}
if (!empty($_GET['f_tgl_awal'])) $where_clauses[] = "tr.tanggal >= '" . mysqli_real_escape_string($conn, $_GET['f_tgl_awal']) . "'";
if (!empty($_GET['f_tgl_akhir'])) $where_clauses[] = "tr.tanggal <= '" . mysqli_real_escape_string($conn, $_GET['f_tgl_akhir']) . "'";

$query = "SELECT tr.*, k.nama_kategori, ak.nama_akun 
          FROM transaksi tr 
          JOIN kategori k ON tr.kategori_id = k.id 
          JOIN akun_pembayaran ak ON tr.akun_id = ak.id 
          WHERE " . implode(' AND ', $where_clauses) . " ORDER BY tr.tanggal ASC, tr.id ASC";

$res_transaksi = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background: #f2f2f2; text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body onload="window.print(); setTimeout(function(){ window.location.href = '../riwayat_lengkap.php'; }, 500);">
    <h2>Laporan Transaksi</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <?php foreach ($daftar_akun as $akun): ?>
                    <th>Saldo <?= htmlspecialchars($akun['nama_akun']) ?></th>
                <?php endforeach; ?>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($tr = mysqli_fetch_assoc($res_transaksi)): 
                $id_akun = (int)$tr['akun_id'];
                $saldo_saat_ini[$id_akun] += ((float)$tr['pemasukan'] - (float)$tr['pengeluaran']);
                $total_global = array_sum($saldo_saat_ini);
            ?>
            <tr>
                <td><?= $tr['tanggal'] ?></td>
                <td><?= htmlspecialchars($tr['deskripsi']) ?></td>
                <?php foreach ($daftar_akun as $akun): ?>
                    <td class="text-right"><?= number_format($saldo_saat_ini[$akun['id']], 0, ',', '.') ?></td>
                <?php endforeach; ?>
                <td class="text-right"><?= number_format($tr['pemasukan'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($tr['pengeluaran'], 0, ',', '.') ?></td>
                <td class="text-right"><strong><?= number_format($total_global, 0, ',', '.') ?></strong></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>