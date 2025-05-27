<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle kriteria edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_kriteria'])) {
    $id_kriteria = $_POST['id_kriteria'];
    $nama_kriteria = $_POST['nama_kriteria'];
    $bobot = $_POST['bobot'];
    $sifat = $_POST['sifat'];
    
    $sql = "UPDATE kriteria SET nama_kriteria = ?, bobot = ?, sifat = ? WHERE id_kriteria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsi", $nama_kriteria, $bobot, $sifat, $id_kriteria);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Kriteria berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $conn->error;
    }
    
    header('Location: kriteria.php');
    exit;
}

// Check if total bobot = 1.0
$check_bobot_query = "SELECT SUM(bobot) as total_bobot FROM kriteria";
$bobot_result = $conn->query($check_bobot_query);
$total_bobot = $bobot_result->fetch_assoc()['total_bobot'];

// Get all kriteria
$query = "SELECT * FROM kriteria ORDER BY id_kriteria ASC";
$result = $conn->query($query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kriteria - SPK Beasiswa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
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
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 50%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .btn-edit {
            background-color: #ffc107;
            color: white;
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
            <li class="sidebar-menu-item active">
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

        <!-- Content -->
        <div class="content">
            <div class="section-header">
                <h1 class="section-title">Manajemen Kriteria</h1>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($total_bobot != 1.0): ?>
                <div class="alert alert-warning">
                    <strong>Perhatian!</strong> Total bobot kriteria saat ini adalah <?= number_format($total_bobot, 2) ?>. 
                    Total bobot seharusnya sama dengan 1.0 untuk hasil yang akurat.
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Kriteria Penilaian</h2>
                    <p>Kriteria yang digunakan dalam sistem pendukung keputusan penerimaan beasiswa.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kriteria</th>
                                    <th>Bobot</th>
                                    <th>Sifat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $no = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . $row['nama_kriteria'] . "</td>";
                                        echo "<td>" . number_format($row['bobot'], 2) . "</td>";
                                        echo "<td>" . ucfirst($row['sifat']) . "</td>";
                                        echo "<td>
                                                <button class='btn btn-edit btn-sm' onclick='openEditModal(" . 
                                                $row['id_kriteria'] . ", \"" . 
                                                $row['nama_kriteria'] . "\", " . 
                                                $row['bobot'] . ", \"" . 
                                                $row['sifat'] . "\")'>
                                                    <i class='fas fa-edit'></i> Edit
                                                </button>
                                            </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Tidak ada data kriteria</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mt-5" style="margin-top: 30px;">
                <div class="card-header">
                <h2>Informasi Kriteria</h2>
    </div>
    <div class="card-body">
        <section style="margin-bottom: 20px;">
            <h3>Penjelasan Kriteria</h3>
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li><strong>IPK (Indeks Prestasi Kumulatif)</strong>: Nilai akademik mahasiswa yang menunjukkan performa belajar.</li>
                <li><strong>Jarak Tempat Tinggal</strong>: Jarak tempat tinggal mahasiswa dari kampus dalam kilometer.</li>
                <li><strong>Penghasilan Orang Tua</strong>: Total penghasilan orang tua per bulan.</li>
                <li><strong>Tanggungan Orang Tua</strong>: Jumlah anggota keluarga yang masih menjadi tanggungan.</li>
            </ul>
        </section>
        
        <section style="margin-bottom: 20px;">
            <h3>Penjelasan Sifat Kriteria</h3>
            <ul style="list-style-type: disc; padding-left: 20px;">
                <li><strong>Benefit</strong>: Semakin tinggi nilai, semakin baik (contoh: IPK).</li>
                <li><strong>Cost</strong>: Semakin rendah nilai, semakin baik (contoh: Penghasilan Orang Tua).</li>
            </ul>
        </section>
        
        <section>
            <h3>Catatan Penting</h3>
            <p style="margin: 0;">
                Total bobot semua kriteria harus berjumlah <strong>1.0 (atau 100%)</strong> untuk hasil penilaian yang valid.
            </p>
                    </section>
            </div>
        </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Kriteria</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_id_kriteria" name="id_kriteria">
                <div class="form-group">
                    <label for="nama_kriteria">Nama Kriteria</label>
                    <input type="text" class="form-control" id="edit_nama_kriteria" name="nama_kriteria" required>
                </div>
                <div class="form-group">
                    <label for="bobot">Bobot</label>
                    <input type="number" class="form-control" id="edit_bobot" name="bobot" step="0.01" min="0" max="1" required>
                    <small class="form-text text-muted">Nilai antara 0 dan 1. Total semua bobot harus 1.0</small>
                </div>
                <div class="form-group">
                    <label for="sifat">Sifat</label>
                    <select class="form-control" id="edit_sifat" name="sifat" required>
                        <option value="benefit">Benefit</option>
                        <option value="cost">Cost</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="edit_kriteria" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
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
            }
        });
        
        // Modal functions
        const modal = document.getElementById("editModal");
        
        function openEditModal(id, nama, bobot, sifat) {
            document.getElementById("edit_id_kriteria").value = id;
            document.getElementById("edit_nama_kriteria").value = nama;
            document.getElementById("edit_bobot").value = bobot;
            document.getElementById("edit_sifat").value = sifat;
            modal.style.display = "block";
        }
        
        function closeModal() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Automatically hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>