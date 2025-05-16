<?php
// File: process_application.php
// Implementasi untuk memproses aplikasi beasiswa dan menghitung dengan metode SAW

// Start session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new BeasiswaProcessor($conn);
    
    if (isset($_POST['action']) && $_POST['action'] === 'new_application') {
        $mahasiswaId = $_POST['mahasiswa_id'];
        
        // Map form fields to criteria IDs (adjust these IDs to match your database)
        $dataKriteria = [
            1 => $_POST['ipk'],           // Assuming kriteria_id 1 is for IPK
            2 => $_POST['jarak'],         // Assuming kriteria_id 2 is for Jarak
            3 => $_POST['penghasilan'],   // Assuming kriteria_id 3 is for Penghasilan
            4 => $_POST['tanggungan']     // Assuming kriteria_id 4 is for Tanggungan
        ];
        
        $result = $processor->processNewApplication($mahasiswaId, $dataKriteria, $_FILES);
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'verify_documents') {
        $appId = $_POST['app_id'];
        $status = $_POST['status'];
        $keterangan = isset($_POST['keterangan']) ? $_POST['keterangan'] : '';
        
        $result = $processor->verifyDocuments($appId, $status, $keterangan);
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'calculate_saw') {
        $result = $processor->calculateSAW();
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } elseif (isset($_POST['action']) && $_POST['action'] === 'determine_decision') {
        $nilaiMinimum = $_POST['nilai_minimum'];
        
        $result = $processor->determineDecision($nilaiMinimum);
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

class BeasiswaProcessor {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Memproses upload dokumen untuk aplikasi beasiswa
     */
    public function processDocumentUploads($appId, $files) {
        // Pastikan direktori upload ada
        $targetDir = "uploads/dokumen/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $uploadedFiles = 0;
        $errors = [];
        
        // Loop melalui setiap dokumen yang diupload
        foreach ($files['dokumen']['name'] as $dokumenId => $fileName) {
            // Jika ada file yang diupload
            if (!empty($fileName)) {
                $tempFile = $files['dokumen']['tmp_name'][$dokumenId];
                $fileSize = $files['dokumen']['size'][$dokumenId];
                $fileError = $files['dokumen']['error'][$dokumenId];
                
                // Validasi file
                if ($fileError === 0 && $fileSize <= 2097152) { // 2MB max
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Hanya terima file PDF
                    if ($fileExtension == "pdf") {
                        // Buat nama file unik
                        $newFileName = time() . "_" . $dokumenId . "_" . basename($fileName);
                        $targetFile = $targetDir . $newFileName;
                        
                        // Upload file
                        if (move_uploaded_file($tempFile, $targetFile)) {
                            // Simpan informasi dokumen ke database
                            $sql = "INSERT INTO dokumen_mahasiswa (app_id, dokumen_id, nama_file, path_file, status_verifikasi) 
                                    VALUES (?, ?, ?, ?, 'belum diverifikasi')";
                            $stmt = $this->conn->prepare($sql);
                            $stmt->bind_param("iiss", $appId, $dokumenId, $fileName, $targetFile);
                            
                            if ($stmt->execute()) {
                                $uploadedFiles++;
                            } else {
                                $errors[] = "Gagal menyimpan informasi dokumen: " . $stmt->error;
                            }
                        } else {
                            $errors[] = "Gagal mengupload file: " . $fileName;
                        }
                    } else {
                        $errors[] = "Hanya file PDF yang diperbolehkan untuk dokumen: " . $fileName;
                    }
                } else {
                    if ($fileError !== 0) {
                        $errors[] = "Error upload file: " . $fileName . " (kode: " . $fileError . ")";
                    } else {
                        $errors[] = "Ukuran file terlalu besar (max 2MB): " . $fileName;
                    }
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'uploaded' => $uploadedFiles,
            'errors' => $errors
        ];
    }

    /**
     * Memproses aplikasi baru dan menyimpan nilai kriteria
     */
    public function processNewApplication($mahasiswaId, $dataKriteria, $files = null) {
        // 1. Buat aplikasi baru
        $tanggalDaftar = date('Y-m-d');
        $sql = "INSERT INTO beasiswa_applications (mahasiswa_id, tanggal_daftar, total_nilai, status_keputusan, status_dokumen) 
                VALUES (?, ?, 0, 'belum diproses', 'belum diverifikasi')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $mahasiswaId, $tanggalDaftar);
        
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Gagal menyimpan aplikasi: ' . $stmt->error];
        }
        
        $appId = $this->conn->insert_id;
        
        // 2. Simpan nilai kriteria
        foreach ($dataKriteria as $kriteriaId => $nilai) {
            $sql = "INSERT INTO nilai_kriteria (app_id, kriteria_id, nilai) 
                    VALUES (?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iid", $appId, $kriteriaId, $nilai);
            
            if (!$stmt->execute()) {
                return ['success' => false, 'message' => 'Gagal menyimpan nilai kriteria: ' . $stmt->error];
            }
        }
        
        // 3. Proses upload dokumen jika ada
        $uploadResult = ['success' => true];
        if ($files !== null && isset($files['dokumen'])) {
            $uploadResult = $this->processDocumentUploads($appId, $files);
        }
        
        return [
            'success' => true, 
            'app_id' => $appId,
            'upload_info' => $uploadResult
        ];
    }

    /**
     * Menghitung nilai SAW untuk semua aplikasi yang dokumennya sudah terverifikasi
     */
    public function calculateSAW() {
        // 1. Ambil semua aplikasi dengan dokumen terverifikasi
        $sql = "SELECT id_app FROM beasiswa_applications 
                WHERE status_dokumen = 'terverifikasi'";
        
        $result = $this->conn->query($sql);
        $appIds = [];
        
        while ($row = $result->fetch_assoc()) {
            $appIds[] = $row['id_app'];
        }
        
        if (empty($appIds)) {
            return ['success' => true, 'message' => 'Tidak ada aplikasi yang perlu dihitung'];
        }
        
        // 2. Ambil semua kriteria dengan bobot dan sifatnya
        $sql = "SELECT id_kriteria, bobot, sifat FROM kriteria";
        $result = $this->conn->query($sql);
        $kriteria = [];
        
        while ($row = $result->fetch_assoc()) {
            $kriteria[$row['id_kriteria']] = [
                'bobot' => $row['bobot'],
                'sifat' => $row['sifat']
            ];
        }
        
        // 3. Untuk setiap kriteria, cari nilai min dan max
        $minMax = [];
        foreach ($kriteria as $kriteriaId => $k) {
            $sql = "SELECT MIN(nilai) as min_val, MAX(nilai) as max_val 
                    FROM nilai_kriteria 
                    WHERE kriteria_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $kriteriaId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $minMax[$kriteriaId] = [
                'min' => $row['min_val'],
                'max' => $row['max_val']
            ];
        }
        
        // 4. Untuk setiap aplikasi, hitung total nilai SAW
        $calculatedCount = 0;
        foreach ($appIds as $appId) {
            $totalNilai = 0;
            
            // Ambil nilai untuk setiap kriteria
            $sql = "SELECT kriteria_id, nilai 
                    FROM nilai_kriteria 
                    WHERE app_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $appId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $kriteriaId = $row['kriteria_id'];
                $nilai = $row['nilai'];
                $sifat = $kriteria[$kriteriaId]['sifat'];
                $bobot = $kriteria[$kriteriaId]['bobot'];
                
                // Normalisasi nilai berdasarkan sifat kriteria
                if ($sifat == 'benefit') {
                    // Untuk benefit, normalisasi = nilai / nilai_max
                    $nilaiNormalisasi = $nilai / $minMax[$kriteriaId]['max'];
                } else {
                    // Untuk cost, normalisasi = nilai_min / nilai
                    $nilaiNormalisasi = $minMax[$kriteriaId]['min'] / $nilai;
                }
                
                // Kalikan dengan bobot
                $totalNilai += $nilaiNormalisasi * $bobot;
            }
            
            // Update total nilai di database
            $sql = "UPDATE beasiswa_applications SET total_nilai = ? WHERE id_app = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("di", $totalNilai, $appId);
            
            if ($stmt->execute()) {
                $calculatedCount++;
            }
        }
        
        return [
            'success' => true,
            'message' => "Nilai SAW berhasil dihitung untuk $calculatedCount aplikasi.",
            'count' => $calculatedCount
        ];
    }
    
    /**
     * Mendapatkan nilai SAW tertinggi dari semua aplikasi
     */
    public function getHighestSAWValue() {
        $sql = "SELECT MAX(total_nilai) as highest_value FROM beasiswa_applications 
                WHERE status_dokumen = 'terverifikasi'";
        
        $result = $this->conn->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['highest_value']);
        }
        
        return 0; // Default jika tidak ada data
    }
    
    /**
     * Menentukan keputusan untuk aplikasi berdasarkan nilai minimum
     * FIXED: Perbaikan pemilihan dengan nilai yang lebih realistis
     */
    public function determineDecision($nilaiMinimum) {
        // Ambil aplikasi yang nilai SAW-nya sudah dihitung dan status dokumennya terverifikasi
        $sql = "UPDATE beasiswa_applications 
                SET status_keputusan = CASE 
                    WHEN total_nilai >= ? THEN 'diterima' 
                    ELSE 'ditolak' 
                END 
                WHERE status_dokumen = 'terverifikasi' 
                AND status_keputusan = 'belum diproses'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("d", $nilaiMinimum);
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            
            // Pastikan setidaknya ada pendaftar yang diterima jika ada yang eligible
            if ($affectedRows == 0) {
                // Jika tidak ada yang terpengaruh, cek apakah nilai minimum terlalu tinggi
                $sql = "SELECT COUNT(*) as total FROM beasiswa_applications 
                       WHERE status_dokumen = 'terverifikasi' 
                       AND status_keputusan = 'belum diproses'";
                       
                $result = $this->conn->query($sql);
                $row = $result->fetch_assoc();
                
                if ($row['total'] > 0) {
                    // Ada pendaftar yang belum diproses, coba kurangi nilai minimum
                    $newMinimum = $nilaiMinimum * 0.85; // Kurangi 15% dari nilai minimum
                    
                    $sql2 = "UPDATE beasiswa_applications 
                            SET status_keputusan = 'diterima'
                            WHERE status_dokumen = 'terverifikasi' 
                            AND status_keputusan = 'belum diproses'
                            AND total_nilai >= ?
                            ORDER BY total_nilai DESC
                            LIMIT 3"; // Setidaknya terima 3 pendaftar teratas
                    
                    $stmt2 = $this->conn->prepare($sql2);
                    $stmt2->bind_param("d", $newMinimum);
                    $stmt2->execute();
                    $acceptedRows = $stmt2->affected_rows;
                    
                    // Untuk sisanya, tolak
                    if ($acceptedRows > 0) {
                        $sql3 = "UPDATE beasiswa_applications 
                                SET status_keputusan = 'ditolak'
                                WHERE status_dokumen = 'terverifikasi' 
                                AND status_keputusan = 'belum diproses'";
                        $this->conn->query($sql3);
                        
                        return [
                            'success' => true,
                            'message' => "Keputusan beasiswa berhasil diproses. $acceptedRows pendaftar diterima dengan nilai minimum yang disesuaikan."
                        ];
                    }
                }
            }
            
            if ($affectedRows > 0) {
                return [
                    'success' => true,
                    'message' => "Keputusan beasiswa berhasil diproses untuk $affectedRows pendaftar."
                ];
            } else {
                return [
                    'success' => true,
                    'message' => "Tidak ada pendaftar yang perlu diproses."
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => "Gagal memproses keputusan: " . $stmt->error
            ];
        }
    }
    
    /**
     * Mengambil data ranking untuk halaman keputusan
     * FIXED: Memperbaiki urutan ranking berdasarkan nilai SAW
     */
    public function getRanking() {
        $sql = "SELECT ba.id_app, m.nim, u.nama, p.nama_prodi, ba.total_nilai, ba.status_keputusan,
                (@rank := @rank + 1) as rank
                FROM beasiswa_applications ba
                JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa
                JOIN users u ON m.user_id = u.id_user
                JOIN prodi p ON m.id_prodi = p.id_prodi
                JOIN (SELECT @rank := 0) r
                WHERE ba.status_dokumen = 'terverifikasi'
                ORDER BY ba.total_nilai DESC";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [];
        }
        
        $ranking = [];
        while ($row = $result->fetch_assoc()) {
            // Memastikan status "belum diproses" ditampilkan dengan benar
            if ($row['status_keputusan'] == 'belum diproses') {
                $row['status_keputusan'] = 'belum diproses';
            }
            
            // Konversi nilai total ke format yang sesuai
            $row['total_nilai'] = number_format((float)$row['total_nilai'], 4, '.', '');
            
            $ranking[] = $row;
        }
        
        return $ranking;
    }
    
    /**
     * Verifikasi dokumen aplikasi beasiswa
     */
    public function verifyDocuments($appId, $status, $keterangan = '') {
        // Update status dokumen di aplikasi beasiswa
        $sql = "UPDATE beasiswa_applications 
                SET status_dokumen = ?, keterangan_dokumen = ? 
                WHERE id_app = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $keterangan, $appId);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => "Status dokumen berhasil diperbarui menjadi '$status'."
            ];
        } else {
            return [
                'success' => false,
                'message' => "Gagal memperbarui status dokumen: " . $stmt->error
            ];
        }
    }
    
    /**
     * Mengambil statistik aplikasi beasiswa
     */
    public function getApplicationStatistics() {
        $stats = [
            'total' => 0,
            'terverifikasi' => 0,
            'diterima' => 0,
            'ditolak' => 0,
            'belum_diproses' => 0
        ];
        
        // Total aplikasi
        $sql = "SELECT COUNT(*) as total FROM beasiswa_applications";
        $result = $this->conn->query($sql);
        if ($row = $result->fetch_assoc()) {
            $stats['total'] = $row['total'];
        }
        
        // Total terverifikasi
        $sql = "SELECT COUNT(*) as total FROM beasiswa_applications WHERE status_dokumen = 'terverifikasi'";
        $result = $this->conn->query($sql);
        if ($row = $result->fetch_assoc()) {
            $stats['terverifikasi'] = $row['total'];
        }
        
        // Total diterima
        $sql = "SELECT COUNT(*) as total FROM beasiswa_applications WHERE status_keputusan = 'diterima'";
        $result = $this->conn->query($sql);
        if ($row = $result->fetch_assoc()) {
            $stats['diterima'] = $row['total'];
        }
        
        // Total ditolak
        $sql = "SELECT COUNT(*) as total FROM beasiswa_applications WHERE status_keputusan = 'ditolak'";
        $result = $this->conn->query($sql);
        if ($row = $result->fetch_assoc()) {
            $stats['ditolak'] = $row['total'];
        }
        
        // Total belum diproses
        $sql = "SELECT COUNT(*) as total FROM beasiswa_applications 
                WHERE status_dokumen = 'terverifikasi' AND status_keputusan = 'belum diproses'";
        $result = $this->conn->query($sql);
        if ($row = $result->fetch_assoc()) {
            $stats['belum_diproses'] = $row['total'];
        }
        
        return $stats;
    }
}