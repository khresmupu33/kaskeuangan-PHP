<?php
include 'proses_dashboard.php';
include '../includes/header.php';
?>
<div class="welcome-section" style="margin-bottom: 20px;">
    <h1>Dashboard Keuangan</h1>
    <p style="color: #555; font-size: 16px;">
        Selamat datang kembali, <strong
            style="color: #2c3e50;"><?= htmlspecialchars(ucfirst($manggil_user)); ?></strong>! Berikut ringkasan
        aktivitas keuangan Anda hari ini.
    </p>
</div>
<div class="saldo-card">
    <h3>Saldo Bersih</h3>
    <p class="nominal">Rp <?= number_format($total_saldo_bersih, 0, ',', '.'); ?></p>
</div>
<div class="dashboard-cards dashboard-scroll">
    <?php foreach ($daftar_akun as $akun): ?>
    <div class="info-card">
        <h4><?php echo htmlspecialchars($akun['nama_akun']); ?></h4>
        <p>Saldo: <strong>Rp <?php echo number_format($akun['saldo_akhir'], 0, ',', '.'); ?></strong></p>
        <p class="small-text">Saldo awal: Rp
            <?php echo number_format($saldo_awal_map[$akun['id']] ?? 0, 0, ',', '.'); ?></p>
    </div>
    <?php endforeach; ?>
</div>
<h2>Tagihan & Hutang Aktif</h2>
<div class="target-wrapper">
    <?php if (count($tagihan_aktif) > 0): ?>
    <?php foreach ($tagihan_aktif as $tag): ?>
    <div class="target-card normal" style="border-left: 4px solid #e74c3c;">
        <h4><?php echo htmlspecialchars($tag['nama_tagihan']); ?></h4>
        <p class="small-text">Jenis: <strong><?php echo htmlspecialchars($tag['jenis']); ?></strong></p>
        <p><strong>Total:</strong> Rp <?php echo number_format($tag['total_nominal'], 0, ',', '.'); ?></p>
        <p><strong>Sisa Tagihan:</strong> Rp <?php echo number_format($tag['sisa_nominal'], 0, ',', '.'); ?></p>
        <p class="small-text">Jatuh Tempo: <strong><?php echo htmlspecialchars($tag['tenggat_waktu']); ?></strong></p>

        <?php if (!empty($tag['tempat_bayar'])): ?>
        <p class="small-text" style="margin-top: 8px;">
            Tempat Bayar:
            <?php 
                            $tempat = $tag['tempat_bayar'];
                            // Cek apakah nilai tempat_bayar berupa URL/Link (http/https/www)
                            if (filter_var($tempat, FILTER_VALIDATE_URL) || preg_match('/^(http:\/\/|https:\/\/|www\.)/i', $tempat)) {
                                $url_link = preg_match('/^www\./i', $tempat) ? 'https://' . $tempat : $tempat;
                                echo '<a href="' . htmlspecialchars($url_link, ENT_QUOTES) . '" target="_blank" style="color: #3498db; text-decoration: underline; font-weight: bold;">' . htmlspecialchars($tag['tempat_bayar']) . '</a>';
                            } else {
                                // Jika bukan link, tampilkan sebagai teks biasa
                                echo '<strong>' . htmlspecialchars($tempat) . '</strong>';
                            }
                        ?>
        </p>
        <?php endif; ?>

        <button
            onclick="bukaModalPelunasanDash(<?php echo (int)$tag['id']; ?>, '<?php echo htmlspecialchars($tag['nama_tagihan'], ENT_QUOTES); ?>', <?php echo (float)$tag['sisa_nominal']; ?>)"
            style="background: #2d5378; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-top: 10px; width: 100%;">Bayar
            / Lunas</button>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="info-card">
        <p>Tidak ada tagihan atau hutang aktif saat ini.</p>
    </div>
    <?php endif; ?>
</div>
<h2>Target Anda</h2>
<div class="target-wrapper">
    <?php if (count($targets) > 0): ?>
    <?php foreach ($targets as $target): ?>
    <div class="target-card <?php echo $target['is_over'] ? 'over' : 'normal'; ?>">
        <h4><?php echo htmlspecialchars($target['nama_kategori']); ?></h4>
        <p class="small-text" <?php echo $target['is_over'] ? 'style="color: black;"' : ''; ?>>
            <?php echo htmlspecialchars($target['deskripsi']); ?></p>

        <p><strong>Target:</strong> Rp <?php echo number_format($target['nominal_maksimal'], 0, ',', '.'); ?></p>
        <p><strong>Terpakai:</strong> Rp <?php echo number_format($target['realisasi'], 0, ',', '.'); ?></p>
        <p><strong>Sisa:</strong> Rp <?php echo number_format(max(0, $target['sisa']), 0, ',', '.'); ?></p>

        <div class="progress-box">
            <div class="progress-bar <?php echo $target['is_over'] ? 'over' : ''; ?>"
                style="width: <?php echo min($target['persen'], 100); ?>%;"></div>
        </div>

        <p class="small-text" <?php echo $target['is_over'] ? 'style="color: black;"' : ''; ?>>
            <?php echo number_format($target['persen'], 1, ',', '.'); ?>% terpakai</p>

        <?php if ($target['is_over']): ?>
        <p class="warning-text" style="color: black;">WARNING: Pengeluaran melebihi target.</p>
        <p class="small-text" style="color: black;">Lebih: Rp
            <?php echo number_format(abs($target['sisa']), 0, ',', '.'); ?></p>
        <?php endif; ?>

        <p class="small-text" <?php echo $target['is_over'] ? 'style="color: black;"' : ''; ?>>Tipe:
            <strong><?php echo htmlspecialchars($target['tipe_target']); ?></strong></p>
        <p class="small-text" <?php echo $target['is_over'] ? 'style="color: black;"' : ''; ?>>Tenggat:
            <strong><?php echo htmlspecialchars($target['tenggat_waktu']); ?></strong></p>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="info-card">
        <p>Belum ada target aktif.</p>
    </div>
    <?php endif; ?>
</div>


<h2>Alokasi Dana / Celengan Virtual</h2>
<div class="target-wrapper">
    <?php if (count($alokasi_aktif) > 0): ?>
    <?php foreach ($alokasi_aktif as $alok): 
            $target_alok = (float)$alok['target_nominal'];
            $terkumpul_alok = (float)$alok['terkumpul_nominal'];
            $sisa_alok = $target_alok - $terkumpul_alok;
            $persen_alok = $target_alok > 0 ? min(($terkumpul_alok / $target_alok) * 100, 100) : 0;
        ?>
    <div class="target-card normal" style="border-left: 4px solid #3498db;">
        <h4><?php echo htmlspecialchars($alok['nama_penyisihan']); ?></h4>
        <p class="small-text">Akun: <strong><?php echo htmlspecialchars($alok['nama_akun']); ?></strong></p>
        <p><strong>Target:</strong> Rp <?php echo number_format($target_alok, 0, ',', '.'); ?></p>
        <p><strong>Terkumpul:</strong> Rp <?php echo number_format($terkumpul_alok, 0, ',', '.'); ?></p>
        <p><strong>Kurang:</strong> Rp <?php echo number_format(max(0, $sisa_alok), 0, ',', '.'); ?></p>

        <div class="progress-box">
            <div class="progress-bar" style="width: <?php echo $persen_alok; ?>%; background: #3498db;"></div>
        </div>
        <p class="small-text"><?php echo number_format($persen_alok, 1, ',', '.'); ?>% terkumpul</p>
        <p class="small-text">Status: <strong><?php echo htmlspecialchars($alok['status']); ?></strong></p>

        <?php if ($alok['status'] !== 'TERCAPAI'): ?>
        <div style="display: flex; gap: 5px; margin-top: 10px;">
            <button
                onclick="bukaModalTambahAlokasi(<?php echo (int)$alok['id']; ?>, '<?php echo htmlspecialchars($alok['nama_penyisihan'], ENT_QUOTES); ?>')"
                style="background: #2d5378; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; flex: 1;">+
                Nominal</button>
            <button
                onclick="bukaModalKurangAlokasi(<?php echo (int)$alok['id']; ?>, '<?php echo htmlspecialchars($alok['nama_penyisihan'], ENT_QUOTES); ?>')"
                style="background: #7f8c8d; color: #fff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; flex: 1;">-
                Nominal</button>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="info-card">
        <p>Tidak ada alokasi dana aktif.</p>
    </div>
    <?php endif; ?>
</div>

<div id="modalTambahAlokasi"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; width:350px; border-radius:6px; position:relative;">
        <h3 id="modalTitleTambahAlok">Tambah Nominal</h3>
        <form method="POST">
            <input type="hidden" name="alokasi_id" id="modal_alokasi_id_tambah">
            <div style="margin-bottom: 15px;">
                <label>Nominal Tambahan (Rp)</label>
                <input type="text" class="format-uang" data-hidden="nominal_tambah" required placeholder="0"
                    style="width:100%; padding:6px;">
                <input type="hidden" name="nominal_tambah" id="nominal_tambah">
            </div>
            <button type="submit" name="tambah_nominal_dash"
                style="background: #27ae60; color:#fff; padding: 6px 12px; border:none; border-radius:4px; cursor:pointer;">Simpan</button>
            <button type="button" onclick="tutupModalTambahAlokasi()"
                style="background: #7f8c8d; color:#fff; padding: 6px 12px; border:none; border-radius:4px; margin-left: 5px; cursor:pointer;">Batal</button>
        </form>
    </div>
</div>

<div id="modalKurangAlokasi"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; width:350px; border-radius:6px; position:relative;">
        <h3 id="modalTitleKurangAlok">Kurangi Nominal</h3>
        <form method="POST">
            <input type="hidden" name="alokasi_id" id="modal_alokasi_id_kurang">
            <div style="margin-bottom: 15px;">
                <label>Nominal Pengurangan (Rp)</label>
                <input type="text" class="format-uang" data-hidden="nominal_kurang" required placeholder="0"
                    style="width:100%; padding:6px;">
                <input type="hidden" name="nominal_kurang" id="nominal_kurang">
            </div>
            <button type="submit" name="kurang_nominal_dash"
                style="background: #e67e22; color:#fff; padding: 6px 12px; border:none; border-radius:4px; cursor:pointer;">Kurangi</button>
            <button type="button" onclick="tutupModalKurangAlokasi()"
                style="background: #7f8c8d; color:#fff; padding: 6px 12px; border:none; border-radius:4px; margin-left: 5px; cursor:pointer;">Batal</button>
        </form>
    </div>
</div>

<script>
function bukaModalTambahAlokasi(id, nama) {
    document.getElementById('modal_alokasi_id_tambah').value = id;
    document.getElementById('modalTitleTambahAlok').innerText = 'Tambah Nominal: ' + nama;
    document.getElementById('modalTambahAlokasi').style.display = 'flex';
}

function tutupModalTambahAlokasi() {
    document.getElementById('modalTambahAlokasi').style.display = 'none';
}

function bukaModalKurangAlokasi(id, nama) {
    document.getElementById('modal_alokasi_id_kurang').value = id;
    document.getElementById('modalTitleKurangAlok').innerText = 'Kurangi Nominal: ' + nama;
    document.getElementById('modalKurangAlokasi').style.display = 'flex';
}

function tutupModalKurangAlokasi() {
    document.getElementById('modalKurangAlokasi').style.display = 'none';
}

document.querySelectorAll('input.format-uang').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, "");
        let hiddenInput = document.querySelector('input[name="' + this.getAttribute('data-hidden') +
            '"]');
        if (hiddenInput) hiddenInput.value = value;
        this.value = value ? value.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
    });
});
</script>

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
<div class="info-card">
    <p>Belum ada transaksi.</p>
</div>
<?php else: ?>
<div class="table-scroll-container" id="scrollContainer"
    style="max-width: 100%; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; background: #fff; padding: 10px; position: relative;">

    <h3 id="stickyGlobalBulan"
        style="margin-top: 0; margin-bottom: 10px; color: #fff; position: sticky; top: 0; background: #2c3e50; z-index: 20; padding: 5px 0; padding-left: 20px; border-bottom: 2px solid #f4f6f7; border-radius:10px;">
        <?php echo array_key_first($transaksi_per_bulan); ?>
    </h3>

    <?php foreach ($transaksi_per_bulan as $bulan => $transaksi_grup): ?>

    <h3 class="month-section-title" data-bulan="<?php echo $bulan; ?>"
        style="margin-top: 15px; margin-bottom: 10px; color: #333; position: relative; background: #fff; z-index: 5; padding: 5px 0; padding-left: 20px; border-bottom: 2px solid #f4f6f7;">
        <?php echo $bulan; ?>
    </h3>

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
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Total Saldo</th>
                    <th>Bukti</th>
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
                    <td><input type="checkbox" class="row-checkbox" data-masuk="<?php echo (float)$tr['pemasukan']; ?>"
                            data-keluar="<?php echo (float)$tr['pengeluaran']; ?>"></td>
                    <td data-field="tanggal" data-id="<?php echo (int) $tr['id']; ?>"
                        data-value="<?php echo htmlspecialchars($tr['tanggal'], ENT_QUOTES); ?>" data-type="date">
                        <?php echo htmlspecialchars($tr['tanggal']); ?>
                    </td>
                    <td data-field="deskripsi" data-id="<?php echo (int) $tr['id']; ?>"
                        data-value="<?php echo htmlspecialchars($tr['deskripsi'], ENT_QUOTES); ?>" data-type="text">
                        <?php echo htmlspecialchars($tr['deskripsi']); ?>
                    </td>
                    <td data-field="kategori_id" data-id="<?php echo (int) $tr['id']; ?>"
                        data-value="<?php echo (int) $tr['kategori_id']; ?>" data-type="select-kategori">
                        <?php echo htmlspecialchars($tr['nama_kategori']); ?>
                    </td>
                    <td data-field="akun_id" data-id="<?php echo (int) $tr['id']; ?>"
                        data-value="<?php echo (int) $tr['akun_id']; ?>" data-type="select-akun">
                        <?php echo htmlspecialchars($tr['nama_akun']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($tr['tipe_transaksi']); ?></td>
                    <td data-field="pemasukan" data-id="<?php echo (int) $tr['id']; ?>"
                        data-value="<?php echo (float) $pemasukan; ?>" data-type="number">
                        <?php echo number_format($pemasukan, 0, ',', '.'); ?>
                    </td>
                    <td data-field="pengeluaran" data-id="<?php echo (int) $tr['id']; ?>"
                        data-value="<?php echo (float) $pengeluaran; ?>" data-type="number">
                        <?php echo number_format($pengeluaran, 0, ',', '.'); ?>
                    </td>
                    <td><strong>Rp <?php echo number_format($running_total, 0, ',', '.'); ?></strong></td>
                    <td>
                        <?php if (!empty($tr['path_bukti'])): ?>
                        <?php  
                                            $ext = strtolower(pathinfo($tr['path_bukti'], PATHINFO_EXTENSION));
                                            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                            $is_zip = ($ext === 'zip');

                                            // Membaca path file bukti dari assets/img/
                                            $file_path = '../assets/img/' . $tr['path_bukti'];
                                        ?>

                        <?php if ($is_image): ?>
                        <a href="javascript:void(0);" onclick="bukaModal(this)">Lihat</a>
                        <div class="overlay-bukti"
                            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.85); z-index: 9999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box;">
                            <div style="position: relative; max-width: 95%; max-height: 95%; text-align: center;">
                                <span onclick="tutupModal(this)"
                                    style="position: absolute; top: -45px; right: 0; font-size: 35px; color: white; cursor: pointer; font-weight: bold; background: rgba(0,0,0,0.6); width: 42px; height: 42px; text-align: center; line-height: 40px; border-radius: 50%; transition: background 0.2s;">&times;</span>
                                <img src="<?php echo htmlspecialchars($file_path); ?>" alt="Bukti"
                                    style="max-width: 100%; max-height: 85vh; width: auto; height: auto; display: inline-block; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                            </div>
                        </div>

                        <?php elseif ($is_zip): ?>
                        <a href="javascript:void(0);"
                            onclick="bukaModalZip(this, '<?php echo htmlspecialchars($tr['path_bukti'], ENT_QUOTES); ?>')">Lihat
                            Isi ZIP</a>
                        <div class="overlay-bukti"
                            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.85); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
                            <div
                                style="position: relative; width: 600px; max-width: 95%; background: white; padding: 25px; border-radius: 8px; max-height: 85vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                                <span onclick="tutupModal(this)"
                                    style="position: absolute; top: 12px; right: 18px; font-size: 28px; color: black; cursor: pointer; font-weight: bold;">&times;</span>
                                <h4 style="margin-top: 0; margin-bottom: 15px;">Daftar File & Pratinjau dalam ZIP:</h4>
                                <div class="zip-content-container">Memuat isi...</div>
                            </div>
                        </div>

                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars($file_path); ?>" target="_blank">Lihat File</a>
                        <?php endif; ?>

                        <?php else: ?>
                        -
                        <?php endif; ?>

                        <script>
                        function bukaModal(el) {
                            var overlay = el.nextElementSibling;
                            if (overlay) {
                                overlay.style.display = "flex";
                            }
                        }

                        // Fungsi khusus AJAX untuk file ZIP agar tidak memberatkan loading utama
                        function bukaModalZip(el, pathBukti) {
                            var overlay = el.nextElementSibling;
                            if (overlay) {
                                overlay.style.display = "flex";
                                var container = overlay.querySelector('.zip-content-container');
                                container.innerHTML = "Memuat isi...";

                                // Panggil file eksternal via fetch API
                                fetch('get_zip_content.php?file=' + encodeURIComponent(pathBukti))
                                    .then(response => response.text())
                                    .then(html => {
                                        container.innerHTML = html;
                                    })
                                    .catch(error => {
                                        container.innerHTML = "Gagal memuat isi ZIP.";
                                    });
                            }
                        }

                        function tutupModal(el) {
                            var overlay = el.closest('.overlay-bukti');
                            if (overlay) {
                                overlay.style.display = "none";
                            }
                        }
                        </script>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="overview-riwayat-box">
    <div>
        <h3>Lihat untuk lebih lengkap untuk melihat dan mengedit Riwayat keseluruhan</h3>
    </div>
    <a href="riwayat_lengkap.php">
        Lihat Selengkapnya &rarr;
    </a>
</div>

<div id="modalPelunasanDash"
    style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
    <div style="background: #fff; padding: 25px; width: 400px; border-radius: 6px; position: relative;">
        <h3>Konfirmasi Pelunasan Tagihan</h3>
        <p id="infoNamaTagihanDash" style="font-weight: bold; margin-bottom: 15px;"></p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="tagihan_id" id="modal_tagihan_id_dash">
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Pilih Akun Pembayaran</label>
                <select name="akun_id" required style="width: 100%; padding: 6px;">
                    <?php foreach($daftar_akun as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp
                        <?= number_format($a['saldo_akhir'], 0, ',', '.') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Pilih Kategori Pembayaran</label>
                <select name="kategori_id" required style="width: 100%; padding: 6px;">
                    <?php foreach($kategori_tagihan_list as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Nominal Pembayaran</label>
                <input type="number" step="any" name="nominal_bayar" id="modal_nominal_bayar_dash" required
                    style="width: 100%; padding: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Bukti Pembayaran (Gambar - di-zip otomatis):</label>
                <input type="file" name="bukti_bayar_lunas_dash" accept="image/*" style="width: 100%;">
            </div>
            <button type="submit" name="proses_lunas_dash"
                style="background: #27ae60; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Konfirmasi
                Bayar</button>
            <button type="button" onclick="tutupModalPelunasanDash()"
                style="background: #7f8c8d; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">Batal</button>
        </form>
    </div>
</div>

<script>
function bukaModalPelunasanDash(id, nama, sisa) {
    document.getElementById('modal_tagihan_id_dash').value = id;
    document.getElementById('infoNamaTagihanDash').innerText = "Tagihan: " + nama + " (Sisa: Rp " + sisa.toLocaleString(
        'id-ID') + ")";
    document.getElementById('modal_nominal_bayar_dash').value = sisa;
    document.getElementById('modalPelunasanDash').style.display = 'flex';
}

function tutupModalPelunasanDash() {
    document.getElementById('modalPelunasanDash').style.display = 'none';
}

window.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('scrollContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;

        const titles = container.querySelectorAll('.month-section-title');
        const globalTitle = document.getElementById('stickyGlobalBulan');

        container.addEventListener('scroll', () => {
            let activeBulan = '';
            titles.forEach(title => {
                const rect = title.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();
                if (rect.top <= containerRect.top + 40) {
                    activeBulan = title.dataset.bulan;
                }
            });
            if (activeBulan) {
                globalTitle.textContent = activeBulan;
            }
        });
    }
});

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
<div id="calculator-box"
    style="display: none; position: fixed; bottom: 20px; right: 20px; background: #2c3e50; color: white; padding: 15px 25px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 1000; font-weight: bold; transition: all 0.3s ease;">
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

<script>
function bukaModalGambar(el) {
    var overlay = el.nextElementSibling;
    if (overlay) {
        overlay.style.display = "flex"; // Menggunakan flex agar gambar persis di tengah
    }
}

function tutupModalGambar(el) {
    var overlay = el.closest('.overlay-bukti');
    if (overlay) {
        overlay.style.display = "none";
    }
}
</script>

<?php include '../includes/footer.php'; ?>