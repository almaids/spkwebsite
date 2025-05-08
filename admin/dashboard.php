<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK Rekomendasi Beasiswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">SPK Beasiswa</div>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </li>
            <li class="sidebar-menu-item">
                <a href="mahasiswa.php">
                    <i class="fas fa-users"></i>
                    <span>Data Pendaftar</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-award"></i>
                <span>Permohonan Diterima</span>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Permohonan Ditolak</span>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-calculator"></i>
                <span>Kriteria</span>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-file-alt"></i>
                <span>Laporan</span>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-cog"></i>
                <span>Pengaturan</span>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <!-- Header -->
        <div class="header">
            <div class="user-menu">
                <img src="/api/placeholder/40/40" alt="User Avatar">
                <span>Admin</span>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="section-header">
                <h1 class="section-title">Dashboard</h1>
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i> Input Data Baru
                </button>
            </div>

            <!-- Stat Cards -->
            <div class="dashboard-cards">
                <div class="card stat-card">
                    <div class="stat-card-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-card-info">
                        <h4>248</h4>
                        <p>Total Mahasiswa</p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-card-icon bg-success">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="stat-card-info">
                        <h4>15</h4>
                        <p>Program Beasiswa</p>
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
                <div class="card stat-card">
                    <div class="stat-card-icon bg-info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-card-info">
                        <h4>3</h4>
                        <p>Beasiswa Aktif</p>
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