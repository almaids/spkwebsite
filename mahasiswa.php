<?php
// Start session if not already started
session_start();

// Check if user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include database connection from config file
require_once 'config.php';

// Get all mahasiswa data with joined tables
$query = "SELECT m.id_mahasiswa, m.nim, m.semester, m.created_at, 
                 u.nama, u.email, u.role,
                 p.nama_prodi, p.jenjang, p.kode_prodi
          FROM mahasiswa m 
          JOIN users u ON m.user_id = u.id_user 
          LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
          ORDER BY m.id_mahasiswa DESC";

$result = $conn->query($query);
$mahasiswa_list = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mahasiswa_list[] = $row;
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
    <link rel="stylesheet" href="css/dashboard.css">
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
    .badge-prodi {
        background-color: #17a2b8;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8em;
    }
    .badge-semester {
        background-color: #6c757d;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8em;
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
                    <span>Data Mahasiswa</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-award"></i>
                <span>Data Beasiswa</span>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Kriteria Penilaian</span>
            </li>
            <li class="sidebar-menu-item">
                <i class="fas fa-calculator"></i>
                <span>Perhitungan SPK</span>
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
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="user-menu">
                <img src="/api/placeholder/40/40" alt="User Avatar">
                <span>Admin</span>
            </div>
        </div>

        <div class="content">
            <div class="section-header">
                <h1 class="section-title">Data Mahasiswa</h1>
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i> Input Data Baru
                </button>
            </div>

        <div class="data-container">
            <?php if (empty($mahasiswa_list)): ?>
                <div class="alert alert-info">
                    Belum ada data mahasiswa yang terdaftar.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>No.</th>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Program Studi</th>
                                <th>Semester</th>
                                <th>Terdaftar Sejak</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($mahasiswa_list as $mahasiswa): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($mahasiswa['nim']); ?></td>
                                    <td><?php echo htmlspecialchars($mahasiswa['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($mahasiswa['email']); ?></td>
                                    <td>
                                        <?php if (!empty($mahasiswa['nama_prodi'])): ?>
                                            <span class="badge-prodi">
                                                <?php 
                                                    echo htmlspecialchars($mahasiswa['nama_prodi']);
                                                    if (!empty($mahasiswa['jenjang'])) {
                                                        echo " (".htmlspecialchars($mahasiswa['jenjang']).")";
                                                    }
                                                ?>
                                            </span>
                                        <?php else: ?>
                                            <em>Tidak ada</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-semester">
                                            Semester <?php echo htmlspecialchars($mahasiswa['semester']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($mahasiswa['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="detail_mahasiswa.php?id=<?php echo $mahasiswa['id_mahasiswa']; ?>" class="btn btn-sm btn-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_mahasiswa.php?id=<?php echo $mahasiswa['id_mahasiswa']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_mahasiswa.php?id=<?php echo $mahasiswa['id_mahasiswa']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data mahasiswa ini?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
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