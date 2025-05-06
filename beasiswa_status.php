<?php
// File: beasiswa_status.php
// Menampilkan status aplikasi beasiswa

// Session start
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Database connection
require_once 'config.php';

// Cek apakah ada parameter app_id
if (isset($_GET['app_id'])) {
    $appId = $_GET['app_id'];
} else {
    // Jika tidak ada, ambil app_id terbaru dari mahasiswa yang login
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT ba.id_app 
            FROM beasiswa_applications ba
            JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa
            WHERE m.user_id = ?
            ORDER BY ba.tanggal_daftar DESC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $appId = $row['id_app'];
    } else {
        // Jika tidak ada aplikasi, redirect ke halaman daftar
        header('Location: formulir.php');
        exit;
    }
}

// Ambil detail aplikasi
$sql = "SELECT ba.*, m.nim, u.nama, p.nama_prodi, m.semester
        FROM beasiswa_applications ba
        JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa
        JOIN users u ON m.user_id = u.id_user
        JOIN prodi p ON m.id_prodi = p.id_prodi
        WHERE ba.id_app = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Aplikasi tidak ditemukan
    header('Location: dashboard.php');
    exit;
}

$aplikasi = $result->fetch_assoc();

// Ambil nilai kriteria
$sql = "SELECT k.nama_kriteria, k.sifat, nk.nilai
        FROM nilai_kriteria nk
        JOIN kriteria k ON nk.kriteria_id = k.id_kriteria
        WHERE nk.app_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appId);
$stmt->execute();
$resultKriteria = $stmt->get_result();

$nilaiKriteria = [];
while ($row = $resultKriteria->fetch_assoc()) {
    $nilaiKriteria[] = $row;
}

// Ambil dokumen
$sql = "SELECT dm.*, dp.nama_dokumen, dm.status_verifikasi
        FROM dokumen_mahasiswa dm
        JOIN dokumen_persyaratan dp ON dm.dokumen_id = dp.id_dokumen
        WHERE dm.app_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appId);
$stmt->execute();
$resultDokumen = $stmt->get_result();

$dokumen = [];
while ($row = $resultDokumen->fetch_assoc()) {
    $dokumen[] = $row;
}

// Helper function untuk status badge
function getStatusBadge($status) {
    switch ($status) {
        case 'terverifikasi':
        case 'diterima':
        case 'valid':
            return '<span class="badge success">' . ucfirst($status) . '</span>';
        case 'ditolak':
        case 'tidak valid':
            return '<span class="badge danger">' . ucfirst($status) . '</span>';
        case 'sedang diverifikasi':
        case 'belum diproses':
        case 'belum diverifikasi':
            return '<span class="badge warning">' . ucfirst($status) . '</span>';
        case 'belum lengkap':
            return '<span class="badge info">' . ucfirst($status) . '</span>';
        default:
            return '<span class="badge">' . ucfirst($status) . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Aplikasi Beasiswa</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/status.css">
</head>
<body>

    
    <div class="container">
        <div class="page-header">
            <h1>Status Aplikasi Beasiswa</h1>
            <p>Nomor Aplikasi: #<?php echo $aplikasi['id_app']; ?></p>
        </div>
        
        <div class="status-card">
            <div class="status-header">
                <h2>Informasi Aplikasi</h2>
                <div class="status-badge">
                    <?php echo getStatusBadge($aplikasi['status_keputusan']); ?>
                </div>
            </div>
            
            <div class="status-content">
                <div class="status-info">
                    <p><strong>Tanggal Pendaftaran:</strong> <?php echo date('d F Y', strtotime($aplikasi['tanggal_daftar'])); ?></p>
                    <p><strong>Status Dokumen:</strong> <?php echo getStatusBadge($aplikasi['status_dokumen']); ?></p>
                    <p><strong>Status Keputusan:</strong> <?php echo getStatusBadge($aplikasi['status_keputusan']); ?></p>
                    
                    <?php if (!is_null($aplikasi['total_nilai'])): ?>
                    <p><strong>Total Nilai:</strong> <?php echo number_format($aplikasi['total_nilai'], 2); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="status-section">
                    <h3>Data Mahasiswa</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">NIM</span>
                            <span class="info-value"><?php echo $aplikasi['nim']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nama</span>
                            <span class="info-value"><?php echo $aplikasi['nama']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Program Studi</span>
                            <span class="info-value"><?php echo $aplikasi['nama_prodi']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Semester</span>
                            <span class="info-value"><?php echo $aplikasi['semester']; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="status-section">
                    <h3>Kriteria Penilaian</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kriteria</th>
                                <th>Nilai</th>
                                <th>Sifat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nilaiKriteria as $kriteria): ?>
                            <tr>
                                <td><?php echo $kriteria['nama_kriteria']; ?></td>
                                <td>
                                    <?php 
                                    // Format nilai tertentu
                                    if ($kriteria['nama_kriteria'] == 'IPK') {
                                        echo number_format($kriteria['nilai'], 2);
                                    } else if ($kriteria['nama_kriteria'] == 'Penghasilan Orang Tua') {
                                        echo 'Rp ' . number_format($kriteria['nilai'], 0, ',', '.');
                                    } else {
                                        echo $kriteria['nilai'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($kriteria['sifat'] == 'benefit'): ?>
                                    <span class="badge success">Benefit</span>
                                    <?php else: ?>
                                    <span class="badge info">Cost</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="status-section">
                    <h3>Status Dokumen</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dokumen as $dok): ?>
                            <tr>
                                <td>
                                    <?php echo $dok['nama_dokumen']; ?>
                                    <div class="file-name"><?php echo $dok['nama_file']; ?></div>
                                </td>
                                <td><?php echo getStatusBadge($dok['status_verifikasi']); ?></td>
                                <td><?php echo $dok['catatan_verifikasi'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($aplikasi['status_dokumen'] == 'ditolak'): ?>
                <div class="status-section alert-box danger">
                    <h3>Dokumen Ditolak</h3>
                    <p>Maaf, dokumen aplikasi Anda ditolak. Silakan periksa catatan verifikasi di atas dan ajukan aplikasi baru dengan dokumen yang benar.</p>
                    <a href="formulir.php" class="btn">Ajukan Aplikasi Baru</a>
                </div>
                <?php endif; ?>
                
                <?php if ($aplikasi['status_keputusan'] == 'diterima'): ?>
                <div class="status-section alert-box success">
                    <h3>Selamat!</h3>
                    <p>Aplikasi beasiswa Anda telah diterima. Silakan ikuti petunjuk selanjutnya melalui email yang akan kami kirimkan.</p>
                </div>
                <?php endif; ?>
                
                <?php if ($aplikasi['status_keputusan'] == 'ditolak' && $aplikasi['status_dokumen'] != 'ditolak'): ?>
                <div class="status-section alert-box danger">
                    <h3>Mohon Maaf</h3>
                    <p>Aplikasi beasiswa Anda tidak memenuhi kriteria penilaian minimum. Anda dapat mencoba lagi pada periode beasiswa berikutnya.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="button-container">
            <a href="dashboard.php" class="btn secondary">Kembali ke Dashboard</a>
            <?php if ($aplikasi['status_dokumen'] != 'ditolak' && $aplikasi['status_keputusan'] != 'diterima'): ?>
            <a href="cetak_bukti.php?app_id=<?php echo $appId; ?>" class="btn primary" target="_blank">Cetak Bukti Pendaftaran</a>
            <?php endif; ?>
        </div>
    </div>
    

</body>
</html>