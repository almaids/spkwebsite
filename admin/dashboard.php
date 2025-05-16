<?php
// Koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "spk";

$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Query untuk mendapatkan total pendaftar
$sqlTotalPendaftar = "SELECT COUNT(*) as total FROM beasiswa_applications";
$resultTotalPendaftar = $conn->query($sqlTotalPendaftar);
$totalPendaftar = $resultTotalPendaftar->fetch_assoc()['total'];

// Query untuk mendapatkan jumlah permohonan diterima
$sqlDiterima = "SELECT COUNT(*) as total FROM beasiswa_applications WHERE status_keputusan = 'diterima'";
$resultDiterima = $conn->query($sqlDiterima);
$totalDiterima = $resultDiterima->fetch_assoc()['total'];

// Query untuk mendapatkan jumlah permohonan ditolak
$sqlDitolak = "SELECT COUNT(*) as total FROM beasiswa_applications WHERE status_keputusan = 'ditolak'";
$resultDitolak = $conn->query($sqlDitolak);
$totalDitolak = $resultDitolak->fetch_assoc()['total'];

// Query untuk mendapatkan pendaftar terbaru
$sqlPendaftarTerbaru = "SELECT ba.id_app, ba.tanggal_daftar, ba.status_keputusan, 
                        u.nama, m.nim, p.nama_prodi
                        FROM beasiswa_applications ba
                        JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa
                        JOIN users u ON m.user_id = u.id_user
                        LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
                        ORDER BY ba.tanggal_daftar DESC LIMIT 5";
$resultPendaftarTerbaru = $conn->query($sqlPendaftarTerbaru);
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
        /* Additional styles to fix navigation */
        .sidebar-menu-item a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            width: 100%;
            transition: all 0.2s ease;
        }
        
        .sidebar-menu-item:hover a,
        .sidebar-menu-item a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu-item.active a {
            background-color: rgba(255, 255, 255, 0.2);
            border-right: 4px solid white;
            font-weight: 600;
        }
        
        /* Styling for dashboard tables */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th, .table td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-diterima {
            background-color: #e6f7e6;
            color: #2e7d32;
        }
        
        .status-ditolak {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        
        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
            color: white;
        }
        
        .bg-primary {
            background-color: #4361ee;
        }
        
        .bg-success {
            background-color: #2e7d32;
        }
        
        .bg-warning {
            background-color: #ff9800;
        }
        
        .bg-danger {
            background-color: #e53935;
        }
        
        .stat-card-info h4 {
            font-size: 24px;
            margin: 0 0 5px 0;
        }
        
        .stat-card-info p {
            margin: 0;
            color: #666;
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
            <li class="sidebar-menu-item active">
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
            <li class="sidebar-menu-item">
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
                <img src="/api/placeholder/40/40" alt="User Avatar">
                <span>Admin</span>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="section-header">
                <h1 class="section-title">Dashboard</h1>
            </div>

            <!-- Stat Cards -->
            <div class="dashboard-cards">
                <div class="card stat-card">
                    <div class="stat-card-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-card-info">
                        <h4><?php echo $totalPendaftar; ?></h4>
                        <p>Total Pendaftar</p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-card-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-card-info">
                        <h4><?php echo $totalDiterima; ?></h4>
                        <p>Permohonan Diterima</p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-card-icon bg-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-card-info">
                        <h4><?php echo $totalDitolak; ?></h4>
                        <p>Permohonan Ditolak</p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="section-header">
                <h2 class="section-title">Statistik</h2>
            </div>
            <div class="charts-container">
                <div class="chart-card">
                    <h3 class="chart-title">Distribusi Penerima Beasiswa</h3>
                    <div class="chart-placeholder">
                        <div>Grafik Distribusi Penerima Beasiswa per Fakultas</div>
                    </div>
                </div>
                <div class="chart-card">
                    <h3 class="chart-title">Perbandingan Kriteria</h3>
                    <div class="chart-placeholder">
                        <div>Grafik Perbandingan Bobot Kriteria</div>
                    </div>
                </div>
            </div>

            <!-- Recent Applications Table -->
            <div class="section-header">
                <h2 class="section-title">Pendaftar Terbaru</h2>
                <a href="mahasiswa.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-eye"></i> Lihat Semua
                </a>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Program Studi</th>
                            <th>Tanggal Daftar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($resultPendaftarTerbaru->num_rows > 0) {
                            while($row = $resultPendaftarTerbaru->fetch_assoc()) {
                                $statusClass = "";
                                switch($row["status_keputusan"]) {
                                    case "diterima":
                                        $statusClass = "status-diterima";
                                        break;
                                    case "ditolak":
                                        $statusClass = "status-ditolak";
                                        break;
                                    default:
                                        $statusClass = "status-pending";
                                }
                                
                                $statusText = ucfirst($row["status_keputusan"]);
                                if ($row["status_keputusan"] == "belum diproses") {
                                    $statusText = "Dalam Proses";
                                }
                                
                                echo "<tr>";
                                echo "<td>" . $row["nim"] . "</td>";
                                echo "<td>" . $row["nama"] . "</td>";
                                echo "<td>" . $row["nama_prodi"] . "</td>";
                                echo "<td>" . date('d/m/Y', strtotime($row["tanggal_daftar"])) . "</td>";
                                echo "<td><span class='status-badge " . $statusClass . "'>" . $statusText . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center;'>Belum ada pendaftar</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Get current page URL
        const currentLocation = window.location.href;
        
        // Get all menu items with anchors
        const menuItems = document.querySelectorAll('.sidebar-menu-item a');
        
        // Set default active if no match is found
        let activeFound = false;
        
        // Loop through menu items to find the current page
        menuItems.forEach(function(item) {
            // Compare href with current location
            if (item.href === currentLocation) {
                // Clear active class from all items
                document.querySelectorAll('.sidebar-menu-item').forEach(function(i) {
                    i.classList.remove('active');
                });
                // Add active class to current item's parent
                item.parentElement.classList.add('active');
                activeFound = true;
            }
        });
        
        // If no active item found, set the dashboard as active by default
        if (!activeFound && currentLocation.includes('dashboard.php')) {
            document.querySelector('.sidebar-menu-item:first-child').classList.add('active');
        }
    </script>
</body>
</html>
<?php
// Tutup koneksi database
$conn->close();
?>