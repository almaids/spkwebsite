<?php
// File: admin_dashboard.php
// Dashboard untuk admin mengelola aplikasi beasiswa

// Session start
session_start();

// Cek apakah user sudah login dan role-nya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Database connection
require_once 'config.php';

// Ambil statistik aplikasi
$sql = "SELECT 
            COUNT(*) as total_applications,
            SUM(CASE WHEN status_dokumen = 'belum lengkap' THEN 1 ELSE 0 END) as belum_lengkap,
            SUM(CASE WHEN status_dokumen = 'sedang diverifikasi' THEN 1 ELSE 0 END) as sedang_diverifikasi,
            SUM(CASE WHEN status_dokumen = 'terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
            SUM(CASE WHEN status_dokumen = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
            SUM(CASE WHEN status_keputusan = 'diterima' THEN 1 ELSE 0 END) as diterima
        FROM beasiswa_applications";

$result = $conn->query($sql);
$stats = $result->fetch_assoc();

// Ambil daftar aplikasi terbaru
$sql = "SELECT ba.id_app, ba.tanggal_daftar, u.nama, m.nim, p.nama_prodi, ba.status_dokumen, ba.status_keputusan, ba.total_nilai
        FROM beasiswa_applications ba
        JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa
        JOIN users u ON m.user_id = u.id_user
        JOIN prodi p ON m.id_prodi = p.id_prodi
        ORDER BY ba.tanggal_daftar DESC
        LIMIT 10";

$result = $conn->query($sql);
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
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
    <title>Admin Dashboard - Sistem Beasiswa</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        .action-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .action-btn {
            padding: 12px 20px;
            background: #f0f0f0;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            transition: all 0.3s;
        }
        .action-btn:hover {
            background: #e0e0e0;
        }
        .action-btn.primary {
            background: #4CAF50;
            color: white;
        }
        .action-btn.primary:hover {
            background: #3e8e41;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        .data-table tr:hover {
            background-color: #f5f5f5;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge.success { background-color: #d4edda; color: #155724; }
        .badge.danger { background-color: #f8d7da; color: #721c24; }
        .badge.warning { background-color: #fff3cd; color: #856404; }
        .badge.info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="admin_aplikasi.php">Daftar Aplikasi</a></li>
                    <li><a href="admin_settings.php">Pengaturan</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_applications']; ?></div>
                <div class="stat-label">Total Aplikasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['belum_lengkap']; ?></div>
                <div class="stat-label">Belum Lengkap</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['sedang_diverifikasi']; ?></div>
                <div class="stat-label">Sedang Diverifikasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['terverifikasi']; ?></div>
                <div class="stat-label">Terverifikasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['diterima']; ?></div>
                <div class="stat-label">Diterima</div>
            </div>
        </div>

        <div class="action-menu">
            <a href="admin_verifikasi.php" class="action-btn primary">Verifikasi Dokumen</a>
            <a href="admin_proses_saw.php" class="action-btn">Proses SAW</a>
            <a href="admin_keputusan.php" class="action-btn">Tentukan Keputusan</a>
            <a href="admin_laporan.php" class="action-btn">Laporan</a>
        </div>

        <h2>Aplikasi Terbaru</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Program Studi</th>
                    <th>Status Dokumen</th>
                    <th>Status Keputusan</th>
                    <th>Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo $app['id_app']; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($app['tanggal_daftar'])); ?></td>
                    <td><?php echo $app['nama']; ?></td>
                    <td><?php echo $app['nim']; ?></td>
                    <td><?php echo $app['nama_prodi']; ?></td>
                    <td><?php echo getStatusBadge($app['status_dokumen']); ?></td>
                    <td><?php echo getStatusBadge($app['status_keputusan']); ?></td>
                    <td><?php echo $app['total_nilai'] ? number_format($app['total_nilai'], 2) : '-'; ?></td>
                    <td>
                        <a href="admin_detail_aplikasi.php?id=<?php echo $app['id_app']; ?>">Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Dashboard scripts if needed
    </script>
</body>
</html>