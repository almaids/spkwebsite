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

// Check if ID parameter is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mahasiswa.php");
    exit;
}

$app_id = intval($_GET['id']);

// Get application details
$query_app = "SELECT ba.id_app, ba.tanggal_daftar, ba.status_dokumen, ba.status_keputusan, 
                    u.nama, m.nim, m.id_mahasiswa, p.nama_prodi, m.semester
                FROM beasiswa_applications ba 
                JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa 
                JOIN users u ON m.user_id = u.id_user
                LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
                WHERE ba.id_app = ?";

$stmt = $conn->prepare($query_app);
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result_app = $stmt->get_result();

if ($result_app->num_rows === 0) {
    // Application not found, redirect to mahasiswa.php
    header("Location: mahasiswa.php");
    exit;
}

$app_data = $result_app->fetch_assoc();

// Get list of required documents
$query_documents = "SELECT dp.id_dokumen, dp.nama_dokumen, dp.deskripsi, dp.wajib,
                        dm.id_dok_mhs, dm.nama_file, dm.path_file, dm.status_verifikasi, dm.catatan_verifikasi
                    FROM dokumen_persyaratan dp
                    LEFT JOIN dokumen_mahasiswa dm ON dp.id_dokumen = dm.dokumen_id AND dm.app_id = ?
                    ORDER BY dp.wajib DESC, dp.nama_dokumen ASC";

$stmt = $conn->prepare($query_documents);
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result_documents = $stmt->get_result();

$documents = [];
while ($row = $result_documents->fetch_assoc()) {
    $documents[] = $row;
}

// Process form submission for document verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_documents'])) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, save all the individual document verification statuses
        foreach ($documents as $doc) {
            if (isset($doc['id_dok_mhs']) && isset($_POST['status_' . $doc['id_dok_mhs']])) {
                $doc_id = $doc['id_dok_mhs'];
                $status = $_POST['status_' . $doc_id];
                $catatan = $_POST['catatan_' . $doc_id] ?? '';
                
                $update_doc = "UPDATE dokumen_mahasiswa 
                              SET status_verifikasi = ?, catatan_verifikasi = ?
                              WHERE id_dok_mhs = ?";
                
                $stmt = $conn->prepare($update_doc);
                $stmt->bind_param("ssi", $status, $catatan, $doc_id);
                $stmt->execute();
            }
        }
        

        $query_updated_docs = "SELECT dp.id_dokumen, dp.wajib, dm.status_verifikasi
                              FROM dokumen_persyaratan dp
                              LEFT JOIN dokumen_mahasiswa dm ON dp.id_dokumen = dm.dokumen_id AND dm.app_id = ?
                              ORDER BY dp.wajib DESC";
        $stmt = $conn->prepare($query_updated_docs);
        $stmt->bind_param("i", $app_id);
        $stmt->execute();
        $result_updated_docs = $stmt->get_result();
        
        $all_verified = true;
        $has_invalid = false;
        $missing_required = false;
        
        while ($doc = $result_updated_docs->fetch_assoc()) {
            // Check if it's a required document
            if ($doc['wajib'] == 1) {
                // Check if document is missing or not verified
                if (!isset($doc['status_verifikasi']) || $doc['status_verifikasi'] === NULL) {
                    $missing_required = true;
                    $all_verified = false;
                } 
                // Check if document is not yet verified
                else if ($doc['status_verifikasi'] === 'belum diverifikasi') {
                    $all_verified = false;
                } 
                // Check if document is invalid
                else if ($doc['status_verifikasi'] === 'tidak valid') {
                    $has_invalid = true;
                    $all_verified = false;
                }
            }
        }
        
        // Determine the overall document status based on clear rules
        $new_status = 'sedang diverifikasi'; // default status
        
        if ($missing_required) {
            $new_status = 'belum lengkap';
        } else if ($has_invalid) {
            $new_status = 'ditolak';
        } else if ($all_verified) {
            $new_status = 'terverifikasi';
        }
        
        // Update the application status
        $update_app = "UPDATE beasiswa_applications SET status_dokumen = ? WHERE id_app = ?";
        $stmt = $conn->prepare($update_app);
        $stmt->bind_param("si", $new_status, $app_id);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Redirect to refresh the page
        header("Location: dokumen.php?id={$app_id}&success=1");
        exit;
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Process file upload if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $dokumen_id = intval($_POST['dokumen_id']);
    
    // Check if file was uploaded
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['document_file']['name'];
        $file_tmp = $_FILES['document_file']['tmp_name'];
        $file_size = $_FILES['document_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file extension
        $allowed_extensions = array("pdf", "jpg", "jpeg", "png", "doc", "docx");
        if (!in_array($file_ext, $allowed_extensions)) {
            $upload_error = "Format file tidak diperbolehkan. Format yang diizinkan: " . implode(', ', $allowed_extensions);
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = "../uploads/dokumen/{$app_id}/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_file_name = uniqid('doc_') . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;
            
            // Move uploaded file to destination
            if (move_uploaded_file($file_tmp, $file_path)) {
                $relative_path = "uploads/dokumen/{$app_id}/{$new_file_name}";
                
                // Check if document already exists
                $check_query = "SELECT id_dok_mhs FROM dokumen_mahasiswa WHERE app_id = ? AND dokumen_id = ?";
                $stmt = $conn->prepare($check_query);
                $stmt->bind_param("ii", $app_id, $dokumen_id);
                $stmt->execute();
                $check_result = $stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Update existing document
                    $doc_row = $check_result->fetch_assoc();
                    $update_query = "UPDATE dokumen_mahasiswa 
                                    SET nama_file = ?, path_file = ?, status_verifikasi = 'belum diverifikasi', catatan_verifikasi = NULL
                                    WHERE id_dok_mhs = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ssi", $file_name, $relative_path, $doc_row['id_dok_mhs']);
                    $stmt->execute();
                } else {
                    // Insert new document
                    $insert_query = "INSERT INTO dokumen_mahasiswa (app_id, dokumen_id, nama_file, path_file) 
                                     VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("iiss", $app_id, $dokumen_id, $file_name, $relative_path);
                    $stmt->execute();
                }
                
                // Redirect to refresh the page
                header("Location: dokumen.php?id={$app_id}&upload=success");
                exit;
            } else {
                $upload_error = "Gagal mengupload file. Silakan coba lagi.";
            }
        }
    } else {
        $upload_error = "Terjadi kesalahan saat upload file.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen - SPK Rekomendasi Beasiswa</title>
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
        .document-item {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .document-item h4 {
            margin-top: 0;
            color: #333;
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

        .badge-valid, .badge-terverifikasi {
            background-color: #27ae60;
        }

        .badge-invalid, .badge-ditolak {
            background-color: #e74c3c;
        }

        .badge-pending, .badge-sedang-diverifikasi {
            background-color: #f39c12;
        }

        .badge-missing, .badge-belum-lengkap, .badge-belum-diverifikasi {
            background-color: #95a5a6;
        }

        .document-item {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }

        .document-item h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }


        .document-preview {
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .verification-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
        }
        .verification-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
        .upload-section {
            margin-top: 10px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
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
        .document-preview {
            margin-top: 10px;
            text-align: center;
        }
        .document-preview img {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 3px;
        }
        .document-preview a {
            display: inline-block;
            margin-top: 5px;
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
                <a href="permohonan_diterima.php">
                    <i class="fas fa-award"></i>
                    <span>Permohonan Diterima</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="permohonan_ditolak.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Permohonan Ditolak</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="#">
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
                <h1 class="section-title">Verifikasi Dokumen</h1>
                <a href="mahasiswa.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Dokumen berhasil diverifikasi
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Dokumen berhasil diupload
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($upload_error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $upload_error; ?>
            </div>
            <?php endif; ?>

            <div class="data-container">
                <h2>Data Mahasiswa</h2>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Nama Mahasiswa</th>
                                <td><?php echo htmlspecialchars($app_data['nama']); ?></td>
                            </tr>
                            <tr>
                                <th>NIM</th>
                                <td><?php echo htmlspecialchars($app_data['nim']); ?></td>
                            </tr>
                            <tr>
                                <th>Program Studi</th>
                                <td><?php echo htmlspecialchars($app_data['nama_prodi']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Semester</th>
                                <td><?php echo htmlspecialchars($app_data['semester']); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Daftar</th>
                                <td><?php echo date('d F Y', strtotime($app_data['tanggal_daftar'])); ?></td>
                            </tr>
                            <tr>
                                <th>Status Dokumen</th>
                                <td>
                                    <?php 
                                    $status_dokumen = $app_data['status_dokumen'];
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
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="data-container">
                <h2>Dokumen Persyaratan</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php if (empty($documents)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Tidak ada dokumen persyaratan yang ditemukan.
                        </div>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-item">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4>
                                            <?php echo htmlspecialchars($doc['nama_dokumen']); ?>
                                            <?php if ($doc['wajib'] == 1): ?>
                                                <span class="badge-status badge-pending">Wajib</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-missing">Opsional</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p><?php echo htmlspecialchars($doc['deskripsi']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <?php if (isset($doc['id_dok_mhs'])): ?>
                                            <?php 
                                            $status_class = 'badge-pending';
                                            if ($doc['status_verifikasi'] === 'valid') {
                                                $status_class = 'badge-valid';
                                            } elseif ($doc['status_verifikasi'] === 'tidak valid') {
                                                $status_class = 'badge-invalid';
                                            }
                                            ?>
                                            <span class="badge-status <?php echo $status_class; ?>">
                                                <?php 
                                                if ($doc['status_verifikasi'] === 'belum diverifikasi') {
                                                    echo "Belum diverifikasi";
                                                } elseif ($doc['status_verifikasi'] === 'valid') {
                                                    echo "Valid";
                                                } else {
                                                    echo "Tidak Valid";
                                                }
                                                ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status badge-missing">Belum diupload</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (isset($doc['id_dok_mhs'])): ?>
                                    <div class="document-preview">
                                        <?php 
                                        $file_ext = strtolower(pathinfo($doc['nama_file'], PATHINFO_EXTENSION));
                                        $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']);
                                        ?>
                                        
                                        <?php if ($is_image): ?>
                                            <img src="../<?php echo htmlspecialchars($doc['path_file']); ?>" alt="<?php echo htmlspecialchars($doc['nama_dokumen']); ?>">
                                        <?php endif; ?>
                                        
                                        <a href="../<?php echo htmlspecialchars($doc['path_file']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> Lihat Dokumen
                                        </a>
                                    </div>

                                    <div class="verification-form">
                                        <div class="form-group">
                                            <label for="status_<?php echo $doc['id_dok_mhs']; ?>">Status Verifikasi</label>
                                            <select name="status_<?php echo $doc['id_dok_mhs']; ?>" id="status_<?php echo $doc['id_dok_mhs']; ?>" class="form-control">
                                                <option value="belum diverifikasi" <?php echo ($doc['status_verifikasi'] === 'belum diverifikasi') ? 'selected' : ''; ?>>Belum diverifikasi</option>
                                                <option value="valid" <?php echo ($doc['status_verifikasi'] === 'valid') ? 'selected' : ''; ?>>Valid</option>
                                                <option value="tidak valid" <?php echo ($doc['status_verifikasi'] === 'tidak valid') ? 'selected' : ''; ?>>Tidak Valid</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="catatan_<?php echo $doc['id_dok_mhs']; ?>">Catatan Verifikasi</label>
                                            <textarea name="catatan_<?php echo $doc['id_dok_mhs']; ?>" id="catatan_<?php echo $doc['id_dok_mhs']; ?>" rows="2" class="form-control"><?php echo htmlspecialchars($doc['catatan_verifikasi'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="upload-section">
                                        <form method="post" enctype="multipart/form-data">
                                            <div class="form-group">
                                                <label>Upload Dokumen</label>
                                                <input type="file" name="document_file" class="form-control">
                                                <input type="hidden" name="dokumen_id" value="<?php echo $doc['id_dokumen']; ?>">
                                            </div>
                                            <button type="submit" name="upload_document" class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload"></i> Upload
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-actions">
                            <button type="submit" name="verify_documents" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Simpan Verifikasi
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
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