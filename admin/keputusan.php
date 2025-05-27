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
require_once '../process_application.php';

// Initialize search query
$search = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
}

// Initialize processor
$processor = new BeasiswaProcessor($conn);

// Get ranking data
$ranking = $processor->getRanking();

// Get highest SAW value
$highestSAW = $processor->getHighestSAWValue();

// Set default minimum nilai - sedikit di bawah nilai tertinggi (99% dari nilai tertinggi)
$defaultMinimum = $highestSAW > 0 ? $highestSAW * 0.99 : 0.65;

// Handle recalculation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recalculate_saw'])) {
    $result = $processor->calculateSAW();
    
    if ($result['success']) {
        $success_message = "Nilai SAW berhasil dihitung ulang.";
        // Refresh ranking data dan nilai tertinggi
        $ranking = $processor->getRanking();
        $highestSAW = $processor->getHighestSAWValue();
        $defaultMinimum = $highestSAW > 0 ? $highestSAW * 0.99 : 0.65;
    } else {
        $error_message = $result['message'];
    }
}

// Handle form submission for determining decisions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_decisions'])) {
    $nilaiMinimum = floatval($_POST['nilai_minimum']);
    $result = $processor->determineDecision($nilaiMinimum);
    
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
}

// Get list of applications waiting for decision
$query_pending = "SELECT COUNT(*) as total_pending 
                 FROM beasiswa_applications 
                 WHERE status_dokumen = 'terverifikasi' 
                 AND status_keputusan = 'belum diproses'";
$result_pending = $conn->query($query_pending);
$pending_data = $result_pending->fetch_assoc();
$total_pending = $pending_data['total_pending'];

// Add search condition if search is not empty
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " WHERE u.nama LIKE '%$search%' OR m.nim LIKE '%$search%'";
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengambilan Keputusan - SPK Rekomendasi Beasiswa</title>
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
        .badge {
            margin-left: 8px;
            vertical-align: middle;
        }
        .badge-status {
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8em;
            display: inline-block;
            margin-top: 5px;
            margin-right: 5px;
            min-width: 90px;
            text-align: center;
        }
        .badge-valid, .badge-terverifikasi, .badge-diterima {
            background-color: #27ae60;
        }
        .badge-invalid, .badge-ditolak {
            background-color: #e74c3c;
        }
        .badge-pending, .badge-sedang-diverifikasi, .badge-belum-diproses {
            background-color: #f39c12;
        }
        .badge-missing, .badge-belum-lengkap {
            background-color: #95a5a6;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px 0;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            margin-right: 10px;
        }
        .button-group {
            margin-top: 15px;
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
            <li class="sidebar-menu-item">
                <a href="mahasiswa.php">
                    <i class="fas fa-users"></i>
                    <span>Data Pendaftar</span>
                </a>
            </li>
            <li class="sidebar-menu-item active">
                <a href="keputusan.php">
                    <i class="fas fa-award"></i>
                    <span>Keputusan Beasiswa</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="permohonan_diterima.php">
                    <i class="fas fa-check-circle"></i>
                    <span>Permohonan Diterima</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="permohonan_ditolak.php">
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
                <img src="../logo.png" alt="User Avatar">
                <span>Admin</span>
            </div>
        </div>

        <div class="content">
            <div class="section-header">
                <h1 class="section-title">Pengambilan Keputusan Beasiswa</h1>
            </div>

            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <div class="data-container">
                <h2>Proses Keputusan Beasiswa</h2>
                <p>Terdapat <strong><?php echo $total_pending; ?></strong> pendaftar yang sudah terverifikasi dan menunggu keputusan.</p>
                
                <div class="button-group">
                    <form method="post" action="" style="display: inline-block;">
                        <button type="submit" name="recalculate_saw" class="btn btn-secondary">
                            <i class="fas fa-calculator"></i> Hitung Ulang Nilai SAW
                        </button>
                    </form>
                </div>
                
                <?php if ($total_pending > 0): ?>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="nilai_minimum">Nilai Minimum untuk Diterima:</label>
                        <input type="number" step="0.01" min="0" max="1" name="nilai_minimum" id="nilai_minimum" class="form-control" value="<?php echo htmlspecialchars($defaultMinimum); ?>" required>
                        <small>Pendaftar dengan nilai SAW >= nilai minimum ini akan diterima, sisanya ditolak. Nilai default adalah 99% dari nilai tertinggi (<?php echo number_format($highestSAW, 4); ?>).</small>
                    </div>
                    <button type="submit" name="process_decisions" class="btn btn-primary">
                        <i class="fas fa-cogs"></i> Proses Keputusan
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Tidak ada pendaftar yang menunggu keputusan.
                </div>
                <?php endif; ?>
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
                        <a href="keputusan.php" class="clear-search-button">
                            <i class="fas fa-times"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="data-container">
                <h2>Ranking Pendaftar Beasiswa</h2>
                <?php if (empty($ranking)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Belum ada pendaftar yang sudah diproses.
                </div>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Program Studi</th>
                            <th>Nilai SAW</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranking as $rank): ?>
                        <tr>
                            <td><?php echo $rank['rank']; ?></td>
                            <td><?php echo htmlspecialchars($rank['nim']); ?></td>
                            <td><?php echo htmlspecialchars($rank['nama']); ?></td>
                            <td><?php echo htmlspecialchars($rank['nama_prodi']); ?></td>
                            <td><?php echo number_format($rank['total_nilai'], 4); ?></td>
                            <td>
                                <?php 
                                $status_class = 'badge-belum-diproses';
                                if ($rank['status_keputusan'] === 'diterima') {
                                    $status_class = 'badge-diterima';
                                } elseif ($rank['status_keputusan'] === 'ditolak') {
                                    $status_class = 'badge-ditolak';
                                }
                                ?>
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php echo ucfirst($rank['status_keputusan']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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