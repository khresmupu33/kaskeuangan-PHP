<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session login tidak ditemukan.'
    ]);
    exit;
}

require_once '../config/koneksi.php';

$user_id = (int) $_SESSION['user_id'];
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$field = isset($_POST['field']) ? trim($_POST['field']) : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

if ($id <= 0 || $field === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak valid.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Cek transaksi milik user
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare($conn, "SELECT * FROM transaksi WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaksi = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$transaksi) {
    echo json_encode([
        'success' => false,
        'message' => 'Transaksi tidak ditemukan.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Validasi field yang boleh diedit
|--------------------------------------------------------------------------
*/
$allowed_fields = ['tanggal', 'deskripsi', 'kategori_id', 'akun_id', 'pemasukan', 'pengeluaran'];

if (!in_array($field, $allowed_fields, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Field tidak diizinkan untuk diedit.'
    ]);
    exit;
}



/*
|--------------------------------------------------------------------------
| Helper update saldo akun
|--------------------------------------------------------------------------
*/
function updateSaldoAkun($conn, $akun_id, $user_id) {
    $akun_id = (int) $akun_id;
    $user_id = (int) $user_id;

    $q = mysqli_query($conn, "
        SELECT 
            COALESCE(SUM(pemasukan), 0) AS total_masuk,
            COALESCE(SUM(pengeluaran), 0) AS total_keluar
        FROM transaksi
        WHERE user_id = $user_id AND akun_id = $akun_id
    ");
    $d = mysqli_fetch_assoc($q);

    $saldo_baru = (float) $d['total_masuk'] - (float) $d['total_keluar'];

    mysqli_query($conn, "
        UPDATE akun_pembayaran
        SET saldo_akhir = $saldo_baru
        WHERE id = $akun_id AND user_id = $user_id
    ");
}

/*
|--------------------------------------------------------------------------
| Proses update
|--------------------------------------------------------------------------
*/
if ($field === 'tanggal') {
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt || $dt->format('Y-m-d') !== $value) {
        echo json_encode([
            'success' => false,
            'message' => 'Format tanggal harus YYYY-MM-DD.'
        ]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE transaksi SET tanggal = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "sii", $value, $id, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Tanggal berhasil diperbarui.' : 'Gagal memperbarui tanggal.',
        'display' => $value,
        'raw_value' => $value
    ]);
    exit;
}

if ($field === 'deskripsi') {
    $value = trim($value);

    $stmt = mysqli_prepare($conn, "UPDATE transaksi SET deskripsi = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "sii", $value, $id, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Deskripsi berhasil diperbarui.' : 'Gagal memperbarui deskripsi.',
        'display' => htmlspecialchars($value, ENT_QUOTES),
        'raw_value' => $value
    ]);
    exit;
}

if ($field === 'kategori_id') {
    $kategori_id = (int) $value;

    $stmt = mysqli_prepare($conn, "SELECT nama_kategori FROM kategori WHERE id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $kategori_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $kategori = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$kategori) {
        echo json_encode([
            'success' => false,
            'message' => 'Kategori tidak valid.'
        ]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE transaksi SET kategori_id = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $kategori_id, $id, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Kategori berhasil diperbarui.' : 'Gagal memperbarui kategori.',
        'display' => $kategori['nama_kategori'],
        'value_id' => $kategori_id
    ]);
    exit;
}

if ($field === 'akun_id') {
    $akun_baru_id = (int) $value;
    $akun_lama_id = (int) $transaksi['akun_id'];

    $stmt = mysqli_prepare($conn, "SELECT nama_akun FROM akun_pembayaran WHERE id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $akun_baru_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $akun = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$akun) {
        echo json_encode([
            'success' => false,
            'message' => 'Akun tidak valid.'
        ]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE transaksi SET akun_id = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $akun_baru_id, $id, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        updateSaldoAkun($conn, $akun_lama_id, $user_id);
        updateSaldoAkun($conn, $akun_baru_id, $user_id);
    }

    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Akun berhasil diperbarui.' : 'Gagal memperbarui akun.',
        'display' => $akun['nama_akun'],
        'value_id' => $akun_baru_id
    ]);
    exit;
}

if ($field === 'pemasukan' || $field === 'pengeluaran') {
    $nominal = str_replace('.', '', $value);
    $nominal = str_replace(',', '.', $nominal);
    $nominal = (float) $nominal;

    if ($nominal < 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Nominal tidak boleh negatif.'
        ]);
        exit;
    }

    if ($field === 'pemasukan' && (float)$transaksi['pengeluaran'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Transaksi pengeluaran tidak bisa diubah menjadi pemasukan.'
        ]);
        exit;
    }

    if ($field === 'pengeluaran' && (float)$transaksi['pemasukan'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Transaksi pemasukan tidak bisa diubah menjadi pengeluaran.'
        ]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE transaksi SET $field = ? WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "dii", $nominal, $id, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        updateSaldoAkun($conn, (int)$transaksi['akun_id'], $user_id);
    }

    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Nominal berhasil diperbarui.' : 'Gagal memperbarui nominal.',
        'display' => number_format($nominal, 0, ',', '.'),
        'raw_value' => $nominal
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Permintaan tidak dikenali.'
]);
exit;