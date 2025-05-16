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

// Initialize search query
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
}

// Get all beasiswa applications data with joined tables
$query = "SELECT ba.id_app, ba.tanggal_daftar, ba.status_dokumen, ba.status_keputusan, ba.total_nilai, 
                 u.nama, 
                 m.id_mahasiswa, m.nim
          FROM beasiswa_applications ba 
          JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa 
          JOIN users u ON m.user_id = u.id_user";


// Add search condition if search is not empty
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " WHERE u.nama LIKE '%$search%' OR m.nim LIKE '%$search%'";
}

$query .= " ORDER BY ba.id_app DESC";

$result = $conn->query($query);
$pendaftar_list = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pendaftar_list[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK Rekomendasi Beasiswa</title>
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
        font-size: 1rem;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .table {
        font-size: 0.700rem; /* Tambahan ini untuk mengecilkan font tabel */
    }
    .badge-status {
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8em;
    }
    .badge-diterima, .badge-terverifikasi {
        background-color: #27ae60;
    }
    .badge-ditolak {
        background-color: #e74c3c;
    }
    .badge-diproses, .badge-sedang-diverifikasi {
        background-color: #f39c12;
    }
    .badge-belum-diproses, .badge-belum-lengkap {
        background-color: #95a5a6;
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
                <h1 class="section-title">Data Pendaftar Beasiswa</h1>
            </div>

        <!-- Search Bar -->
    <div class="search-container">
    <form action="" method="GET" class="search-form">
        <div class="search-input-group">
            <input type="text" name="search" id="search" class="search-input" 
                placeholder="Cari Nama Mahasiswa atau NIM..." 
                value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> Cari
            </button>
            <?php if (!empty($search)): ?>
            <a href="mahasiswa.php" class="clear-search-button">
                <i class="fas fa-times"></i> Reset
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

        <div class="data-container">
            <?php if (empty($pendaftar_list)): ?>
                <div class="alert alert-info">
                    Belum ada data pendaftar beasiswa yang terdaftar.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>No.</th>
                                <th>Nama Mahasiswa</th>
                                <th>Tanggal Daftar</th>
                                <th>Status Dokumen</th>
                                <th>Status Keputusan</th>
                                <th>Total Nilai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($pendaftar_list as $pendaftar): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($pendaftar['nama']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($pendaftar['tanggal_daftar'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_dokumen = $pendaftar['status_dokumen'];
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
                                    </td>
                                    <td>
                                        <?php 
                                        $status_keputusan = $pendaftar['status_keputusan'];
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
                                    </td>
                                    <td>
                                        <?php 
                                        if (!is_null($pendaftar['total_nilai'])) {
                                            echo number_format($pendaftar['total_nilai'], 2);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="detail_pendaftar.php?id=<?php echo $pendaftar['id_app']; ?>" class="btn btn-sm btn-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_pendaftar.php?id=<?php echo $pendaftar['id_app']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="dokumen.php?id=<?php echo $pendaftar['id_app']; ?>" class="btn btn-sm btn-secondary" title="Lihat Dokumen">
                                                <i class="fas fa-folder-open"></i>
                                            </a>
                                            <?php if ($pendaftar['status_dokumen'] == 'terverifikasi' && $pendaftar['status_keputusan'] == 'belum diproses'): ?>
                                            <a href="proses_saw.php?id=<?php echo $pendaftar['id_app']; ?>" class="btn btn-sm btn-success" title="Proses SAW">
                                                <i class="fas fa-calculator"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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