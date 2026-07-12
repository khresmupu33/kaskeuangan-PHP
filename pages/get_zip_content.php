<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit('Akses ditolak.');
}

$file_path = $_GET['file'] ?? '';
// Validasi keamanan sederhana agar tidak bisa path traversal
$file_path = '../assets/img/' . str_replace('..', '', $file_path);

if (!file_exists($file_path)) {
    echo '<p style="color: red;">File ZIP tidak ditemukan.</p>';
    exit;
}

$zip = new ZipArchive();
if ($zip->open($file_path) === TRUE) {
    echo '<div style="display: flex; flex-direction: column; gap: 15px; text-align: left;">';
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $is_img_in_zip = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']);

        echo '<div style="border: 1px solid #ddd; padding: 12px; border-radius: 6px; background: #fdfdfd;">';
        echo '<strong>File:</strong> ' . htmlspecialchars($filename) . '<br><br>';
        
        if ($is_img_in_zip) {
            $img_data = $zip->getFromIndex($i);
            $base64_img = 'data:image/' . $file_ext . ';base64,' . base64_encode($img_data);
            
            // Tampilkan gambar
            echo '<img src="' . $base64_img . '" alt="' . htmlspecialchars($filename) . '" style="max-width: 100%; max-height: 400px; object-fit: contain; display: block; margin: 0 auto 12px auto; border-radius: 4px;">';
            
            // Tombol download khusus untuk file gambar ini (menggunakan data URI Base64)
            echo '<div style="text-align: center;">';
            echo '<a href="' . $base64_img . '" download="' . htmlspecialchars($filename) . '" style="display: inline-block; padding: 6px 14px; background: #27ae60; color: white; text-decoration: none; border-radius: 4px; font-size: 13px;">Download Foto Ini</a>';
            echo '</div>';
        } else {
            echo '<span style="color: #666; font-style: italic;">Format file bukan gambar (tidak bisa dipratinjau langsung).</span>';
        }
        echo '</div>';
    }
    echo '</div>';
    $zip->close();
} else {
    echo '<p style="color: red;">Gagal membaca file ZIP.</p>';
}
?>