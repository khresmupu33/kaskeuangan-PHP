<?php 
session_start();
$base_url = "../"; 
include '../includes/header.php'; 
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>

<style>
/* Styling Khusus Halaman Tentang Website */
.about-section {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-bottom: 40px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.about-header {
    text-align: center;
    margin-bottom: 35px;
}

.about-logo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #2c3e50;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 15px;
}

.about-header h1 {
    color: #2c3e50;
    font-size: 26px;
    margin-bottom: 5px;
}

.about-header p {
    color: #7f8c8d;
    font-size: 15px;
}

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 25px;
}

.about-card {
    background: #fdfefe;
    border: 1px solid #e5e8e8;
    padding: 24px;
    border-radius: 8px;
}

.about-card h2 {
    color: #2c3e50;
    font-size: 18px;
    margin-bottom: 15px;
    border-bottom: 2px solid #27ae60;
    padding-bottom: 8px;
}

.feature-list {
    list-style: none;
    padding-left: 0;
}

.feature-list li {
    padding: 8px 0;
    border-bottom: 1px dashed #ecf0f1;
    font-size: 14px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.feature-list li::before {
    content: "•";
    color: #27ae60;
    font-weight: bold;
    font-size: 18px;
    line-height: 1;
}

.philosophy-container {
    text-align: center;
    margin: 15px 0 20px 0;
}

.philosophy-logo-large {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #2c3e50;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
}

.philosophy-text,
.profile-text {
    font-size: 14px;
    color: #4f5b66;
    text-align: justify;
    margin-bottom: 12px;
}

.profile-img-container {
    text-align: center;
    margin: 15px 0 20px 0;
}

.profile-img-large {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #27ae60;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
}

.profile-quote {
    background: #f4f9f6;
    border-left: 4px solid #27ae60;
    padding: 12px 15px;
    font-style: italic;
    font-size: 13px;
    color: #2c3e50;
    margin-top: 15px;
    border-radius: 0 6px 6px 0;
}

/* Portfolio / Social Media Links Styling */
.portfolio-links {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 18px;
}

.portfolio-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #2c3e50;
    color: #fff;
    border-radius: 50%;
    transition: background 0.2s, transform 0.2s;
    text-decoration: none;
}

.portfolio-links a:hover {
    background: #27ae60;
    transform: translateY(-2px);
}

.portfolio-links svg {
    width: 18px;
    height: 18px;
}

/* Responsif untuk Layar HP */
@media screen and (max-width: 768px) {
    .about-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container">
    <div class="about-section">
        <div class="about-header">
            <h1>KasKeuangan Khresmupu</h1>
            <p>Solusi manajemen finansial digital yang intuitif, terstruktur, dan elegan.</p>
        </div>

        <div class="about-grid">
            <div class="about-card">
                <h2>Fitur Utama Website</h2>
                <ul class="feature-list">
                    <li><strong>Pencatatan Transaksi:</strong> Memantau pemasukan dan pengeluaran harian secara rinci.
                    </li>
                    <li><strong>Daftar Tagihan:</strong> Mengelola kewajiban pembayaran agar tidak ada yang terlewat.
                    </li>
                    <li><strong>Target Pengeluaran:</strong> Menetapkan batas anggaran dan memantau realisasi dana
                        secara real-time.</li>
                    <li><strong>Alokasi & Celengan:</strong> Menyisihkan dan mengelompokkan pos tabungan terstruktur.
                    </li>
                    <li><strong>Pengaturan Kategori & Dompet:</strong> Kustomisasi jenis pengeluaran dan
                        multi-rekening/dompet.</li>
                </ul>

                <h2 style="margin-top: 30px;">Filosofi Logo</h2>
                <div class="philosophy-container">
                    <img src="<?php echo $base_url; ?>includes/KasKeuanganKhresmupu.png?v=<?php echo time(); ?>"
                        alt="Logo Kas Keuangan Khresmupu" class="philosophy-logo-large">
                </div>
                <p class="philosophy-text">
                    Logo ini merepresentasikan inisial <strong>KKK</strong> dari <strong>Kas Keuangan Khresmupu</strong>
                    yang saling terhubung secara simetris dan geometris. Garis tegak melambangkan keteraturan serta
                    kedisiplinan dalam mencatat finansial, sedangkan bentuk belah ketupat di tengah menyimbolkan
                    kestabilan sebagai titik temu alur masuk dan keluarnya arus kas. Keterhubungan antar elemen ini
                    mencerminkan filosofi harmoni dalam mengelola stabilitas keuangan secara utuh.
                </p>
            </div>

            <div class="about-card">
                <h2>Tentang Pembuat</h2>
                <div class="profile-img-container">
                    <img src="<?php echo $base_url; ?>includes/khresmupu_coding.jpg?v=<?php echo time(); ?>"
                        alt="Khresmupu Coding" class="profile-img-large">
                </div>
                <p class="profile-text">
                    Khresmupu adalah seorang Creative Developer dan mahasiswa di Universitas Kristen Maranatha yang
                    bergerak di persimpangan antara teknologi digital dan ekspresi kreatif. Fokus utama Khresmupu adalah
                    mentransformasi penyampaian materi konvensional menjadi pengalaman yang interaktif dan intuitif
                    melalui integrasi animasi 3D pada web.
                </p>
                <p class="profile-text">
                    Di luar baris kode, Khresmupu mengeksplorasi dunia audio sebagai sarana bercerita. Bagi Khresmupu,
                    baik coding maupun musik adalah alat untuk memecahkan masalah: yang satu melalui logika fungsional,
                    yang lainnya melalui resonansi emosional. Melalui khresmupu, komitmennya adalah menciptakan solusi
                    digital yang tidak hanya bekerja dengan baik, tetapi juga memiliki nilai edukatif dan estetika yang
                    mendalam.
                </p>


                <div class="portfolio-links">
                    <a href="https://github.com/khresmupu33" target="_blank" rel="noopener noreferrer" title="GitHub">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 .5C5.73.5.75 5.48.75 11.75c0 5.02 3.25 9.27 7.76 10.77.57.1.78-.25.78-.56 0-.28-.01-1.02-.02-2-3.15.68-3.81-1.52-3.81-1.52-.52-1.3-1.26-1.65-1.26-1.65-1.03-.7.08-.69.08-.69 1.13.08 1.73 1.16 1.73 1.16 1.01 1.72 2.65 1.22 3.3.93.1-.73.39-1.22.71-1.5-2.52-.29-5.17-1.26-5.17-5.6 0-1.24.44-2.25 1.16-3.04-.12-.29-.5-1.45.11-3.02 0 0 .95-.3 3.12 1.16a10.8 10.8 0 0 1 5.68 0 c2.17-1.46 3.12-1.16 3.12-1.16.61 1.57.23 2.73.11 3.02.72.79 1.16 1.8 1.16 3.04 0 4.35-2.66 5.31-5.19 5.59.4.35.77 1.04.77 2.1 0 1.52-.01 2.74-.01 3.11 0 .31.2.67.79.56 4.5-1.5 7.75-5.75 7.75-10.77C23.25 5.48 18.27.5 12 .5z" />
                        </svg>
                    </a>
                    <a href="https://id.linkedin.com/in/khresna-mulia-putra-339b97382" target="_blank"
                        rel="noopener noreferrer" title="LinkedIn">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M4.98 3.5C4.98 4.88 3.86 6 2.49 6S0 4.88 0 3.5 1.11 1 2.49 1 4.98 2.12 4.98 3.5zM.5 8h4V24h-4V8zm7 0h3.84v2.16h.05 c.53-1.01 1.84-2.16 3.79-2.16 4.05 0 4.8 2.67 4.8 6.14V24h-4v-7.1 c0-1.7-.03-3.88-2.36-3.88-2.36 0-2.72 1.84-2.72 3.75V24h-4V8z" />
                        </svg>
                    </a>
                    <a href="https://portofolio-khresmupu.netlify.app/" target="_blank" rel="noopener noreferrer"
                        title="Portfolio">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="2" y1="12" x2="22" y2="12" />
                            <path
                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                        </svg>
                    </a>
                    <a href="https://www.instagram.com/khresmupu_coding/" target="_blank" rel="noopener noreferrer"
                        title="Instagram">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>