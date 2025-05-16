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
                        <h4>248</h4>
                        <p>Total Pendaftar</p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-card-icon bg-warning">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-card-info">
                        <h4>87</h4>
                        <p>Penerima Beasiswa</p>
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
                <button class="btn btn-primary btn-sm">
                    <i class="fas fa-eye"></i> Lihat Semua
                </button>
            </div>
            <div class="table-container">
                <!-- Table content will go here -->
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