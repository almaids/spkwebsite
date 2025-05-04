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

// Get logged in user ID
$user_id = $_SESSION['user_id'];

// Get student data that is connected to the logged in user
$query = "SELECT m.*, u.nama, u.email, u.role, p.nama_prodi, p.jenjang, p.kode_prodi
          FROM mahasiswa m 
          JOIN users u ON m.user_id = u.id_user 
          LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
          WHERE m.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    // If no student record found for this user
    $student = null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            padding: 30px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .profile-info {
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 50px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header text-center">
            <h1>Profil Mahasiswa</h1>
        </div>

        <?php if ($student): ?>
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-info text-center">
                    <div class="profile-pic">
                        <?php echo strtoupper(substr($student['nama'], 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($student['nama']); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($student['email']); ?></p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="profile-info">
                    <h3>Informasi Akademik</h3>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>NIM:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php echo htmlspecialchars($student['nim']); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Role:</strong>
                        </div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php echo ($student['role'] == 'admin') ? 'danger' : 'primary'; ?>">
                                <?php echo ucfirst(htmlspecialchars($student['role'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Program Studi:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php 
                                if (!empty($student['nama_prodi'])) {
                                    echo htmlspecialchars($student['nama_prodi']);
                                    if (!empty($student['jenjang'])) {
                                        echo " (".htmlspecialchars($student['jenjang']).")";
                                    }
                                    if (!empty($student['kode_prodi'])) {
                                        echo " - ".htmlspecialchars($student['kode_prodi']);
                                    }
                                } else {
                                    echo "<em>Belum diisi</em>";
                                }
                            ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Semester:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php echo htmlspecialchars($student['semester']); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Terdaftar Sejak:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php echo date('d F Y', strtotime($student['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="edit_profile.php" class="btn btn-primary">Edit Profil</a>
                        <a href="homepage.php" class="btn btn-secondary ms-2">Kembali ke Home</a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <h4>Profil mahasiswa tidak ditemukan</h4>
            <p>Anda belum memiliki profil mahasiswa. Silakan hubungi administrator untuk mendaftarkan data mahasiswa Anda.</p>
            <?php
            // Menampilkan data user meskipun belum ada data mahasiswa
            $user_id = $_SESSION['user_id'];
            $user_query = "SELECT * FROM users WHERE id_user = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                $user = $user_result->fetch_assoc();
            ?>
            <div class="profile-info mt-4">
                <h5>Data Pengguna</h5>
                <p><strong>Nama:</strong> <?php echo htmlspecialchars($user['nama']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role:</strong> 
                    <span class="badge bg-<?php echo ($user['role'] == 'admin') ? 'danger' : 'primary'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </p>
                <p><strong>Terdaftar sejak:</strong> <?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
            </div>
            <?php } ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>