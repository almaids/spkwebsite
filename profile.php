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

// Get user data
$user_query = "SELECT * FROM users WHERE id_user = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Get student data that is connected to the logged in user
$query = "SELECT m.*, p.nama_prodi, p.jenjang, p.kode_prodi
          FROM mahasiswa m 
          LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
          WHERE m.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $has_student_data = true;
} else {
    // If no student record found for this user
    $student = [
        'nim' => '',
        'semester' => '',
        'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
        'nama_prodi' => '',
        'jenjang' => '',
        'kode_prodi' => ''
    ];
    $has_student_data = false;
}

$conn->close();

// Add this to handle the incomplete profile message from formulir.php
if (isset($_GET['msg']) && $_GET['msg'] == 'incomplete_profile') {
    $error_message = "Anda harus melengkapi data profil terlebih dahulu sebelum mendaftar beasiswa.";
} else {
    $error_message = "";
}
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
        .empty-data {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header text-center">
            <!-- Add this in profile.php inside the container div, right after profile-header -->
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
            <h1>Profil Mahasiswa</h1>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="profile-info text-center">
                    <div class="profile-pic">
                        <?php echo strtoupper(substr($user['nama'], 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user['nama']); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="profile-info">
                    <h3>Informasi Akademik</h3>
                    <?php if (!$has_student_data): ?>
                    <div class="alert alert-info mt-3">
                        Data akademik belum lengkap. Silakan lengkapi data Anda.
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>NIM:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php 
                                if (!empty($student['nim'])) {
                                    echo htmlspecialchars($student['nim']);
                                } else {
                                    echo '<span class="empty-data">Belum diisi</span>';
                                }
                            ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Role:</strong>
                        </div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php echo ($user['role'] == 'admin') ? 'danger' : 'primary'; ?>">
                                <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
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
                                    echo '<span class="empty-data">Belum diisi</span>';
                                }
                            ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Semester:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php 
                                if (!empty($student['semester'])) {
                                    echo htmlspecialchars($student['semester']);
                                } else {
                                    echo '<span class="empty-data">Belum diisi</span>';
                                }
                            ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <strong>Terdaftar Sejak:</strong>
                        </div>
                        <div class="col-sm-9">
                            <?php echo date('d F Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="edit_profile.php" class="btn btn-primary">Edit Profil</a>
                        <a href="homepage.php" class="btn btn-secondary ms-2">Kembali ke Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>