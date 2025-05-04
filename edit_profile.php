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

// Initialize variables
$nim = '';
$id_prodi = '';
$semester = '';
$message = '';
$messageClass = '';
$isUpdate = false;

// Check if the user already has mahasiswa data
$check_query = "SELECT m.*, p.nama_prodi 
                FROM mahasiswa m 
                LEFT JOIN prodi p ON m.id_prodi = p.id_prodi 
                WHERE m.user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // User already has mahasiswa data, fetch it
    $mahasiswa_data = $result->fetch_assoc();
    $nim = $mahasiswa_data['nim'];
    $id_prodi = $mahasiswa_data['id_prodi'];
    $semester = $mahasiswa_data['semester'];
    $isUpdate = true;
}

// Get all program studi
$prodi_query = "SELECT * FROM prodi ORDER BY nama_prodi ASC";
$prodi_result = $conn->query($prodi_query);
$prodi_list = [];

if ($prodi_result->num_rows > 0) {
    while ($row = $prodi_result->fetch_assoc()) {
        $prodi_list[] = $row;
    }
}

// Get user data
$user_query = "SELECT * FROM users WHERE id_user = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $id_prodi = trim($_POST['id_prodi']); // Mengubah $prodi menjadi $id_prodi agar konsisten
    $semester = trim($_POST['semester']);

    // Validate form
    $errors = [];

    if (empty($nim)) {
        $errors[] = "NIM harus diisi";
    }

    if (empty($id_prodi)) { // Mengubah $prodi menjadi $id_prodi
        $errors[] = "Program Studi harus diisi";
    }

    if (empty($semester) || !is_numeric($semester)) {
        $errors[] = "Semester harus diisi dengan angka";
    }

    // Check if NIM already exists but not for this user
    if (!empty($nim)) {
        $check_nim_query = "SELECT * FROM mahasiswa WHERE nim = ? AND user_id != ?";
        $check_nim_stmt = $conn->prepare($check_nim_query);
        $check_nim_stmt->bind_param("si", $nim, $user_id);
        $check_nim_stmt->execute();
        $check_nim_result = $check_nim_stmt->get_result();
        
        if ($check_nim_result->num_rows > 0) {
            $errors[] = "NIM sudah digunakan oleh mahasiswa lain";
        }
    }

    // If no errors, save data
    if (empty($errors)) {
        if ($isUpdate) {
            // Update existing data
            $update_query = "UPDATE mahasiswa SET nim = ?, id_prodi = ?, semester = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("siii", $nim, $id_prodi, $semester, $user_id);
            
            if ($stmt->execute()) {
                $message = "Data mahasiswa berhasil diperbarui!";
                $messageClass = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $messageClass = "danger";
            }
        } else {
            // Insert new data
            $insert_query = "INSERT INTO mahasiswa (user_id, nim, id_prodi, semester) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("isii", $user_id, $nim, $id_prodi, $semester);
            
            if ($stmt->execute()) {
                $message = "Data mahasiswa berhasil disimpan!";
                $messageClass = "success";
                $isUpdate = true;
            } else {
                $message = "Error: " . $stmt->error;
                $messageClass = "danger";
            }
        }
    } else {
        $message = "Silakan perbaiki kesalahan berikut:<br>" . implode("<br>", $errors);
        $messageClass = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isUpdate ? 'Edit' : 'Tambah'; ?> Data Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .readonly-field {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header text-center">
            <h1><?php echo $isUpdate ? 'Edit' : 'Tambah'; ?> Data Mahasiswa</h1>
        </div>

        <div class="form-container">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama</label>
                    <input type="text" class="form-control readonly-field" id="nama" name="nama" value="<?php echo htmlspecialchars($user_data['nama']); ?>" readonly>
                    <div class="form-text">Nama diambil dari data akun Anda dan tidak dapat diubah disini.</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control readonly-field" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                    <div class="form-text">Email diambil dari data akun Anda dan tidak dapat diubah disini.</div>
                </div>

                <div class="mb-3">
                    <label for="nim" class="form-label">NIM <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nim" name="nim" value="<?php echo htmlspecialchars($nim); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="id_prodi" class="form-label">Program Studi <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_prodi" name="id_prodi" required>
                        <option value="" disabled <?php echo empty($id_prodi) ? 'selected' : ''; ?>>Pilih Program Studi</option>
                        <?php foreach ($prodi_list as $prodi_item): ?>
                            <option value="<?php echo $prodi_item['id_prodi']; ?>" <?php echo $id_prodi == $prodi_item['id_prodi'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prodi_item['nama_prodi']); ?>
                                <?php if (!empty($prodi_item['jenjang'])): ?>
                                    (<?php echo htmlspecialchars($prodi_item['jenjang']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="" disabled <?php echo empty($semester) ? 'selected' : ''; ?>>Pilih Semester</option>
                        <?php for ($i = 1; $i <= 14; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isUpdate ? 'Perbarui Data' : 'Simpan Data'; ?>
                    </button>
                    <a href="profile.php" class="btn btn-secondary">Kembali ke Profil</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>