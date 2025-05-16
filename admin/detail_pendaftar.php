<?php
// Start session if not already started
session_start();

// Check if user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection from config file
require_once '../config.php';

// Check if ID parameter is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mahasiswa.php");
    exit;
}

$app_id = intval($_GET['id']);

// Get detailed application information
$query = "SELECT ba.id_app, ba.tanggal_daftar, ba.status_dokumen, ba.status_keputusan, ba.total_nilai, 
                 u.nama, u.email,
                 m.id_mahasiswa, m.nim, m.semester,
                 p.nama_prodi
          FROM beasiswa_applications ba 
          JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa 
          JOIN users u ON m.user_id = u.id_user
          LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
          WHERE ba.id_app = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Application not found, redirect to mahasiswa.php
    header("Location: mahasiswa.php");
    exit;
}

$data = $result->fetch_assoc();

// Get criteria values (IPK, penghasilan_ortu, jarak_rumah, tanggungan_ortu)
$query_criteria = "SELECT k.nama_kriteria, nk.nilai 
                   FROM nilai_kriteria nk 
                   JOIN kriteria k ON nk.kriteria_id = k.id_kriteria 
                   WHERE nk.app_id = ?";

$stmt = $conn->prepare($query_criteria);
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result_criteria = $stmt->get_result();

$criteria_values = [];
while ($row = $result_criteria->fetch_assoc()) {
    $criteria_values[$row['nama_kriteria']] = $row['nilai'];
}

// Get documents
$query_documents = "SELECT dp.id_dokumen, dp.nama_dokumen, dp.deskripsi, dp.wajib,
                        dm.id_dok_mhs, dm.nama_file, dm.path_file, dm.status_verifikasi, dm.catatan_verifikasi
                    FROM dokumen_persyaratan dp
                    LEFT JOIN dokumen_mahasiswa dm ON dp.id_dokumen = dm.dokumen_id AND dm.app_id = ?
                    ORDER BY dp.wajib DESC, dp.nama_dokumen ASC";

$stmt = $conn->prepare($query_documents);
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result_documents = $stmt->get_result();

$documents = [];
while ($row = $result_documents->fetch_assoc()) {
    $documents[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pendaftar Beasiswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .page-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .data-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .section-title {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-group h4 {
            margin-bottom: 15px;
            color: #3498db;
            font-size: 1.2rem;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            min-width: 200px;
        }
        .info-value {
            flex: 1;
        }
        .badge-status {
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            display: inline-block;
        }
        .badge-diterima, .badge-terverifikasi, .badge-valid {
            background-color: #27ae60;
        }
        .badge-ditolak, .badge-invalid {
            background-color: #e74c3c;
        }
        .badge-diproses, .badge-sedang-diverifikasi {
            background-color: #f39c12;
        }
        .badge-belum-diproses, .badge-belum-lengkap, .badge-belum-diverifikasi {
            background-color: #95a5a6;
        }
        .document-item {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .document-item h4 {
            margin-top: 0;
            color: #333;
        }
        .document-preview {
            margin-top: 10px;
            text-align: center;
        }
        .document-preview img {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 3px;
        }
        .btn-action {
            margin-right: 5px;
        }
        .btn-circle {
            width: 40px;
            height: 40px;
            padding: 10px;
            border-radius: 50%;
            text-align: center;
            line-height: 1.42857;
            margin-right: 10px;
        }
        .criteria-value {
            font-weight: bold;
            font-size: 1.1em;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">SPK Beasiswa</div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item active">
                <a href="mahasiswa.php">
                    <i class="fas fa-users"></i>
                    <span>Data Pendaftar</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="keputusan.php">
                    <i class="fas fa-award"></i>
                    <span>Keputusan Beasiswa</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="permohonan-diterima.php">
                    <i class="fas fa-check-circle"></i>
                    <span>Permohonan Diterima</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="permohonan-ditolak.php">
                    <i class="fas fa-times-circle"></i>
                    <span>Permohonan Ditolak</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="kriteria.php">
                    <i class="fas fa-calculator"></i>
                    <span>Kriteria</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="../index.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <!-- Header -->
        <div class="header">
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="user-menu">
                <img src="logofom.png" alt="User Avatar">
                <span>Admin</span>
            </div>
        </div>

        <div class="content">
            <div class="section-header">
                <h1 class="section-title">Detail Pendaftar Beasiswa</h1>
                <div>
                    <a href="mahasiswa.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="dokumen.php?id=<?php echo $app_id; ?>" class="btn btn-info">
                        <i class="fas fa-folder-open"></i> Kelola Dokumen
                    </a>
                </div>
            </div>

            <!-- Status and Application Info -->
            <div class="data-container">
                <h2 class="section-title">Informasi Aplikasi</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">ID Aplikasi</div>
                            <div class="info-value"><?php echo $data['id_app']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Tanggal Daftar</div>
                            <div class="info-value"><?php echo date('d F Y', strtotime($data['tanggal_daftar'])); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Status Dokumen</div>
                            <div class="info-value">
                                <?php 
                                $status_dokumen = $data['status_dokumen'];
                                $badge_class = '';
                                
                                switch ($status_dokumen) {
                                    case 'terverifikasi':
                                        $badge_class = 'badge-terverifikasi';
                                        break;
                                    case 'ditolak':
                                        $badge_class = 'badge-ditolak';
                                        break;
                                    case 'sedang diverifikasi':
                                        $badge_class = 'badge-sedang-diverifikasi';
                                        break;
                                    case 'belum lengkap':
                                        $badge_class = 'badge-belum-lengkap';
                                        break;
                                    default:
                                        $badge_class = 'badge-belum-lengkap';
                                }
                                ?>
                                <span class="badge-status <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($status_dokumen); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Status Keputusan</div>
                            <div class="info-value">
                                <?php 
                                $status_keputusan = $data['status_keputusan'];
                                $badge_class = '';
                                
                                switch ($status_keputusan) {
                                    case 'diterima':
                                        $badge_class = 'badge-diterima';
                                        break;
                                    case 'ditolak':
                                        $badge_class = 'badge-ditolak';
                                        break;
                                    case 'belum diproses':
                                        $badge_class = 'badge-belum-diproses';
                                        break;
                                    default:
                                        $badge_class = 'badge-belum-diproses';
                                }
                                ?>
                                <span class="badge-status <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($status_keputusan); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Total Nilai</div>
                            <div class="info-value"><?php echo is_null($data['total_nilai']) ? '-' : number_format($data['total_nilai'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Data -->
            <div class="data-container">
                <h2 class="section-title">Data Pribadi</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Nama Lengkap</div>
                            <div class="info-value"><?php echo htmlspecialchars($data['nama']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">NIM</div>
                            <div class="info-value"><?php echo htmlspecialchars($data['nim']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Program Studi</div>
                            <div class="info-value"><?php echo htmlspecialchars($data['nama_prodi']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Semester</div>
                            <div class="info-value"><?php echo htmlspecialchars($data['semester']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($data['email']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Criteria Data -->
            <div class="data-container">
                <h2 class="section-title">Data Kriteria Beasiswa</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-group">
                            <h4><i class="fas fa-graduation-cap"></i> Indeks Prestasi Kumulatif (IPK)</h4>
                            <div class="criteria-value"><?php echo isset($criteria_values['IPK']) ? number_format($criteria_values['IPK'], 2) : '-'; ?></div>
                        </div>
                        <div class="info-group">
                            <h4><i class="fas fa-money-bill-wave"></i> Penghasilan Orang Tua</h4>
                            <div class="criteria-value">Rp <?php echo isset($criteria_values['Penghasilan Orang Tua']) ? number_format($criteria_values['Penghasilan Orang Tua'], 0, ',', '.') : '-'; ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <h4><i class="fas fa-route"></i> Jarak Rumah ke Kampus</h4>
                            <div class="criteria-value"><?php echo isset($criteria_values['Jarak Tempat Tinggal']) ? $criteria_values['Jarak Tempat Tinggal'] : '-'; ?> km</div>
                        </div>
                        <div class="info-group">
                            <h4><i class="fas fa-users"></i> Jumlah Tanggungan Orang Tua</h4>
                            <div class="criteria-value"><?php echo isset($criteria_values['Tanggungan Orang Tua']) ? $criteria_values['Tanggungan Orang Tua'] : '-'; ?> orang</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document List -->
            <div class="data-container">
                <h2 class="section-title">Dokumen Persyaratan</h2>
                <div class="row">
                    <?php if (empty($documents)): ?>
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Tidak ada dokumen persyaratan yang ditemukan.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <div class="col-md-6">
                                <div class="document-item">
                                    <h4>
                                        <?php echo htmlspecialchars($doc['nama_dokumen']); ?>
                                        <?php if ($doc['wajib'] == 1): ?>
                                            <span class="badge-status badge-sedang-diverifikasi">Wajib</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-belum-diproses">Opsional</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p><?php echo htmlspecialchars($doc['deskripsi']); ?></p>
                                    
                                    <?php if (isset($doc['id_dok_mhs'])): ?>
                                        <?php 
                                        $status_class = 'badge-belum-diverifikasi';
                                        if ($doc['status_verifikasi'] === 'valid') {
                                            $status_class = 'badge-valid';
                                        } elseif ($doc['status_verifikasi'] === 'tidak valid') {
                                            $status_class = 'badge-invalid';
                                        }
                                        ?>
                                        <div>
                                            <span class="badge-status <?php echo $status_class; ?>">
                                                <?php 
                                                if ($doc['status_verifikasi'] === 'belum diverifikasi') {
                                                    echo "Belum diverifikasi";
                                                } elseif ($doc['status_verifikasi'] === 'valid') {
                                                    echo "Valid";
                                                } else {
                                                    echo "Tidak Valid";
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (!empty($doc['catatan_verifikasi'])): ?>
                                            <div style="margin-top: 10px;">
                                                <strong>Catatan:</strong> <?php echo htmlspecialchars($doc['catatan_verifikasi']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="document-preview">
                                            <?php 
                                            $file_ext = strtolower(pathinfo($doc['nama_file'], PATHINFO_EXTENSION));
                                            $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']);
                                            ?>
                                            
                                            <?php if ($is_image): ?>
                                                <img src="../<?php echo htmlspecialchars($doc['path_file']); ?>" alt="<?php echo htmlspecialchars($doc['nama_dokumen']); ?>">
                                            <?php endif; ?>
                                            
                                            <a href="../<?php echo htmlspecialchars($doc['path_file']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> Lihat Dokumen
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Dokumen belum diupload
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Active menu item
        document.querySelectorAll('.sidebar-menu-item').forEach(function(item) {
            item.addEventListener('click', function() {
                document.querySelectorAll('.sidebar-menu-item').forEach(function(i) {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>