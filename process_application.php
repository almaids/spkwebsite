<?php
// File: process_application.php
// Implementasi untuk memproses aplikasi beasiswa dan menghitung dengan metode SAW

// Database connection
require_once 'config.php';

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
    $sql = "INSERT INTO beasiswa_applications (mahasiswa_id, tanggal_daftar) 
            VALUES (?, ?)";
    
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
        // 1. Ambil semua aplikasi dengan dokumen terverifikasi tapi belum ada keputusan
        $sql = "SELECT id_app FROM beasiswa_applications 
                WHERE status_dokumen = 'terverifikasi' 
                AND status_keputusan = 'belum diproses'";
        
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
            $stmt->execute();
        }
        
        return ['success' => true, 'message' => 'Perhitungan SAW berhasil'];
    }
    
    /**
     * Memverifikasi dokumen aplikasi
     */
    public function verifyDocument($dokMhsId, $status, $catatan = null) {
        $sql = "UPDATE dokumen_mahasiswa 
                SET status_verifikasi = ?, catatan_verifikasi = ? 
                WHERE id_dok_mhs = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $catatan, $dokMhsId);
        
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Gagal memperbarui status dokumen: ' . $stmt->error];
        }
        
        // Cek apakah semua dokumen sudah diverifikasi untuk aplikasi ini
        $sql = "SELECT dm.app_id, 
                       COUNT(dm.id_dok_mhs) as total_dok,
                       SUM(CASE WHEN dm.status_verifikasi = 'valid' THEN 1 ELSE 0 END) as valid_dok,
                       SUM(CASE WHEN dm.status_verifikasi = 'tidak valid' THEN 1 ELSE 0 END) as invalid_dok
                FROM dokumen_mahasiswa dm
                WHERE dm.id_dok_mhs = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $dokMhsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $appId = $row['app_id'];
        
        // Ambil semua dokumen untuk aplikasi ini
        $sql = "SELECT COUNT(id_dok_mhs) as total, 
                       SUM(CASE WHEN status_verifikasi = 'valid' THEN 1 ELSE 0 END) as valid,
                       SUM(CASE WHEN status_verifikasi = 'tidak valid' THEN 1 ELSE 0 END) as invalid,
                       SUM(CASE WHEN status_verifikasi = 'belum diverifikasi' THEN 1 ELSE 0 END) as pending
                FROM dokumen_mahasiswa 
                WHERE app_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $appId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Update status dokumen aplikasi berdasarkan hasil verifikasi
        $statusDokumen = 'sedang diverifikasi';
        
        if ($row['pending'] == 0) {
            // Semua dokumen sudah diverifikasi
            if ($row['invalid'] > 0) {
                $statusDokumen = 'ditolak';
            } else {
                $statusDokumen = 'terverifikasi';
            }
        }
        
        $sql = "UPDATE beasiswa_applications SET status_dokumen = ? WHERE id_app = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $statusDokumen, $appId);
        $stmt->execute();
        
        // Jika semua dokumen terverifikasi, lakukan perhitungan SAW
        if ($statusDokumen == 'terverifikasi') {
            $this->calculateSAW();
        }
        
        return ['success' => true, 'message' => 'Dokumen berhasil diverifikasi'];
    }
    
    /**
     * Menentukan keputusan beasiswa berdasarkan nilai minimum
     */
    public function determineDecision($nilaiMinimum) {
        $sql = "UPDATE beasiswa_applications 
                SET status_keputusan = CASE 
                    WHEN total_nilai >= ? THEN 'diterima' 
                    ELSE 'ditolak' 
                END
                WHERE status_dokumen = 'terverifikasi' 
                AND status_keputusan = 'belum diproses'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("d", $nilaiMinimum);
        
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Gagal menentukan keputusan: ' . $stmt->error];
        }
        
        $affected = $stmt->affected_rows;
        return ['success' => true, 'message' => "Keputusan berhasil ditentukan untuk $affected aplikasi"];
    }
    
    /**
     * Mendapatkan ranking aplikasi beasiswa
     */
    public function getRanking() {
        $sql = "SELECT ba.id_app, m.nim, u.nama, p.nama_prodi, ba.total_nilai, ba.status_keputusan
                FROM beasiswa_applications ba
                JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa
                JOIN users u ON m.user_id = u.id_user
                JOIN prodi p ON m.id_prodi = p.id_prodi
                WHERE ba.status_dokumen = 'terverifikasi'
                ORDER BY ba.total_nilai DESC";
        
        $result = $this->conn->query($sql);
        $ranking = [];
        
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $row['rank'] = $rank++;
            $ranking[] = $row;
        }
        
        return $ranking;
    }
}

// Contoh penggunaan (bila file ini dipanggil langsung)
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // Process POST request jika ada
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';
        
        $processor = new BeasiswaProcessor($conn);
        
        switch ($action) {
            case 'new_application':
                $mahasiswaId = $_POST['mahasiswa_id'] ?? 0;
                $dataKriteria = [
                    1 => $_POST['ipk'], // IPK
                    2 => $_POST['jarak'], // Jarak
                    3 => $_POST['penghasilan'], // Penghasilan
                    4 => $_POST['tanggungan'], // Tanggungan
                ];
                
                // Tambahkan files ke parameter
                $result = $processor->processNewApplication($mahasiswaId, $dataKriteria, $_FILES);
                echo json_encode($result);
                break;
                
            // Kode lainnya tetap sama
        }
    }
}
?>