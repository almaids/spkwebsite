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
$mahasiswa_query = "SELECT m.*, p.nama_prodi, p.jenjang, p.kode_prodi
          FROM mahasiswa m 
          LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
          WHERE m.user_id = ?";

$mahasiswa_stmt = $conn->prepare($mahasiswa_query);
$mahasiswa_stmt->bind_param("i", $user_id);
$mahasiswa_stmt->execute();
$mahasiswa_result = $mahasiswa_stmt->get_result();

if ($mahasiswa_result->num_rows > 0) {
    $mahasiswa = $mahasiswa_result->fetch_assoc();
    $_SESSION['mahasiswa_id'] = $mahasiswa['id_mahasiswa'];
    $prodi = [
        'nama_prodi' => $mahasiswa['nama_prodi'],
        'jenjang' => $mahasiswa['jenjang'],
        'kode_prodi' => $mahasiswa['kode_prodi']
    ];
    $has_profile_data = true;
} else {
    // If no student record found for this user, redirect to profile page to complete data
    header("Location: profile.php?msg=incomplete_profile");
    exit;
}

// Get required documents
$dokumen_query = "SELECT * FROM dokumen_persyaratan";
$dokumen_stmt = $conn->prepare($dokumen_query);
$dokumen_stmt->execute();
$dokumen_result = $dokumen_stmt->get_result();
$dokumenPersyaratan = [];

while ($row = $dokumen_result->fetch_assoc()) {
    $dokumenPersyaratan[] = $row;
}

// Validate required profile data
if (empty($mahasiswa['nim']) || empty($mahasiswa['semester']) || empty($prodi['nama_prodi'])) {
    header("Location: profile.php?msg=incomplete_profile");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran Beasiswa</title>
    <link rel="stylesheet" href="css/formulir.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="/api/placeholder/150/150" alt="Logo Universitas">
            <h1>Formulir Pendaftaran Beasiswa</h1>
        </div>
        
        <form id="scholarshipForm" method="POST" action="process_application.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="new_application">
            <input type="hidden" name="mahasiswa_id" value="<?php echo $_SESSION['mahasiswa_id']; ?>">
            
            <!-- Step 1: Data Mahasiswa (Otomatis dari profil) -->
            <div class="form-step" id="step1">
                <h2>Data Mahasiswa</h2>
                
                <div class="form-info">
                    <p>Data ini diambil dari profil Anda. Jika ada kesalahan, silahkan update di halaman profil.</p>
                </div>
                
                <div class="form-group">
                    <label for="nim">NIM (Nomor Induk Mahasiswa)</label>
                    <input type="text" id="nim" value="<?php echo isset($mahasiswa['nim']) ? htmlspecialchars($mahasiswa['nim']) : ''; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" value="<?php echo isset($user['nama']) ? htmlspecialchars($user['nama']) : ''; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="prodi">Program Studi / Jurusan</label>
                    <input type="text" id="prodi" value="<?php echo isset($prodi['nama_prodi']) ? htmlspecialchars($prodi['nama_prodi']) : ''; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <input type="number" id="semester" value="<?php echo isset($mahasiswa['semester']) ? htmlspecialchars($mahasiswa['semester']) : ''; ?>" readonly>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="next-btn" onclick="nextStep(1)">Lanjut</button>
                </div>
            </div>
            
            <!-- Step 2: Kriteria Penilaian SAW -->
            <div class="form-step" id="step2" style="display: none;">
                <h2>Kriteria Penilaian</h2>
                
                <div class="form-info">
                    <p>Masukkan data sesuai dengan kondisi Anda saat ini. Semua data akan diverifikasi berdasarkan dokumen yang Anda upload.</p>
                </div>
                
                <!-- IPK (Benefit) -->
                <div class="form-group">
                    <label for="ipk" class="required">IPK (Indeks Prestasi Kumulatif)</label>
                    <input type="number" id="ipk" name="ipk" step="0.01" min="0" max="4" required placeholder="Contoh: 3.75">
                    <span class="criterion-info">Semakin tinggi semakin baik</span>
                </div>
                
                <!-- Jarak (Cost) -->
                <div class="form-group">
                    <label for="jarak" class="required">Jarak Rumah ke Kampus (km)</label>
                    <input type="number" id="jarak" name="jarak" min="0" step="0.1" required placeholder="Contoh: 15.5">
                    <span class="criterion-info">Semakin dekat semakin baik</span>
                </div>
                
                <!-- Penghasilan (Cost) -->
                <div class="form-group">
                    <label for="penghasilan" class="required">Penghasilan Orang Tua per Bulan (Rp)</label>
                    <input type="number" id="penghasilan" name="penghasilan" min="0" required placeholder="Contoh: 5000000">
                    <span class="criterion-info">Semakin rendah semakin diprioritaskan</span>
                </div>
                
                <!-- Tanggungan (Benefit) -->
                <div class="form-group">
                    <label for="tanggungan" class="required">Jumlah Tanggungan Orang Tua</label>
                    <input type="number" id="tanggungan" name="tanggungan" min="0" required placeholder="Contoh: 3">
                    <span class="criterion-info">Semakin banyak semakin diprioritaskan</span>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="prev-btn" onclick="prevStep(2)">Kembali</button>
                    <button type="button" class="next-btn" onclick="nextStep(2)">Lanjut</button>
                </div>
            </div>
            
            <!-- Step 3: Dokumen Pendukung -->
            <div class="form-step" id="step3" style="display: none;">
                <h2>Dokumen Pendukung</h2>
                
                <div class="form-info">
                    <p>Upload semua dokumen berikut dalam format PDF. Ukuran maksimal per file adalah 2MB.</p>
                </div>
                
                <?php foreach ($dokumenPersyaratan as $dokumen): ?>
                <div class="form-group">
                    <label for="dokumen_<?php echo $dokumen['id_dokumen']; ?>" class="required">
                        <?php echo htmlspecialchars($dokumen['nama_dokumen']); ?>
                        <?php if (!empty($dokumen['deskripsi'])): ?>
                            <span class="tooltip" title="<?php echo htmlspecialchars($dokumen['deskripsi']); ?>">?</span>
                        <?php endif; ?>
                    </label>
                    <input type="file" id="dokumen_<?php echo $dokumen['id_dokumen']; ?>" 
                           name="dokumen[<?php echo $dokumen['id_dokumen']; ?>]" 
                           accept="application/pdf" required>
                </div>
                <?php endforeach; ?>
                
                <div class="form-buttons">
                    <button type="button" class="prev-btn" onclick="prevStep(3)">Kembali</button>
                    <button type="button" class="next-btn" onclick="nextStep(3)">Lanjut</button>
                </div>
            </div>
            
            <!-- Step 4: Konfirmasi -->
            <div class="form-step" id="step4" style="display: none;">
                <h2>Konfirmasi Pendaftaran</h2>
                
                <div class="confirmation-box">
                    <p>Dengan ini saya menyatakan bahwa:</p>
                    <ol>
                        <li>Semua informasi yang saya berikan adalah benar dan dapat dipertanggungjawabkan.</li>
                        <li>Saya bersedia mengikuti semua ketentuan dan persyaratan program beasiswa.</li>
                        <li>Saya memahami bahwa pemberian informasi yang tidak benar dapat mengakibatkan pembatalan beasiswa.</li>
                    </ol>
                    
                    <div class="form-group">
                        <input type="checkbox" id="agreement" name="agreement" required>
                        <label for="agreement">Saya menyetujui pernyataan di atas</label>
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="prev-btn" onclick="prevStep(4)">Kembali</button>
                    <button type="submit" class="submit-btn">Daftar Beasiswa</button>
                </div>
            </div>
        </form>
        
        <p class="note">Catatan: Isian bertanda (*) wajib diisi. Pastikan semua dokumen yang diunggah dalam format PDF dengan ukuran maksimal 2MB per file.</p>
    </div>

    <script>
        // Form multi-step navigation
        function nextStep(currentStep) {
            // Validate current step
            if (!validateStep(currentStep)) {
                return false;
            }
            
            // Hide current step
            document.getElementById('step' + currentStep).style.display = 'none';
            
            // Show next step
            document.getElementById('step' + (currentStep + 1)).style.display = 'block';
            
            // Update progress indicator - Fix the logic here
            const progressSteps = document.querySelectorAll('.progress-step');
            
            // First mark current as completed and remove active
            progressSteps[currentStep - 1].classList.add('completed');
            progressSteps[currentStep - 1].classList.remove('active');
            
            // Then mark next as active
            progressSteps[currentStep].classList.add('active');
            
            // If moving to summary step, populate summary
            if (currentStep + 1 === 4) {
                populateSummary();
            }
            
            return true;
        }
        
        function prevStep(currentStep) {
            // Hide current step
            document.getElementById('step' + currentStep).style.display = 'none';
            
            // Show previous step
            document.getElementById('step' + (currentStep - 1)).style.display = 'block';
            
            // Update progress indicator
            const progressSteps = document.querySelectorAll('.progress-step');
            
            // Mark current as not active
            progressSteps[currentStep - 1].classList.remove('active');
            
            // Mark previous as active and not completed
            progressSteps[currentStep - 2].classList.add('active');
            progressSteps[currentStep - 2].classList.remove('completed');
            
            return true;
        }
        
        function validateStep(step) {
            let valid = true;
            const inputs = document.querySelectorAll('#step' + step + ' input[required], #step' + step + ' select[required]');
            
            inputs.forEach(input => {
                if (!input.value) {
                    input.classList.add('error');
                    valid = false;
                } else {
                    input.classList.remove('error');
                }
            });
            
            if (!valid) {
                alert('Mohon lengkapi semua field yang wajib diisi!');
            }
            
            return valid;
        }
        
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }
        
        function populateSummary() {
            const summary = document.getElementById('summary-content');
            let html = '';
            
            // Data Mahasiswa
            html += '<h4>Data Mahasiswa</h4>';
            html += '<p><strong>NIM:</strong> ' + document.getElementById('nim').value + '</p>';
            html += '<p><strong>Nama:</strong> ' + document.getElementById('nama').value + '</p>';
            html += '<p><strong>Program Studi:</strong> ' + document.getElementById('prodi').value + '</p>';
            html += '<p><strong>Semester:</strong> ' + document.getElementById('semester').value + '</p>';
            
            // Kriteria Penilaian
            html += '<h4>Kriteria Penilaian</h4>';
            html += '<p><strong>IPK:</strong> ' + document.getElementById('ipk').value + '</p>';
            html += '<p><strong>Jarak (km):</strong> ' + document.getElementById('jarak').value + '</p>';
            html += '<p><strong>Penghasilan Orang Tua:</strong> Rp ' + formatRupiah(document.getElementById('penghasilan').value) + '</p>';
            html += '<p><strong>Jumlah Tanggungan:</strong> ' + document.getElementById('tanggungan').value + '</p>';
            
            // Dokumen yang diupload
            html += '<h4>Dokumen yang Diupload</h4>';
            html += '<ul>';
            
            // Dapatkan semua dokumen yang diupload
            const dokumenInputs = document.querySelectorAll('input[type="file"]');
            dokumenInputs.forEach(input => {
                // Ambil label untuk dokumen ini
                const labelElement = document.querySelector(`label[for="${input.id}"]`);
                const label = labelElement ? labelElement.textContent.trim().replace('?', '') : input.id;
                
                // Tampilkan nama file yang dipilih atau status "Belum dipilih"
                const fileName = input.files.length > 0 ? input.files[0].name : 'Belum dipilih';
                html += `<li><strong>${label}:</strong> ${fileName}</li>`;
            });
            
            html += '</ul>';
            
            // Set HTML ke summary box
            summary.innerHTML = html;
        }

        // Validasi input IPK (0-4.0)
        document.getElementById('ipk').addEventListener('input', function() {
            let value = parseFloat(this.value);
            if (value > 4) {
                this.value = 4;
            } else if (value < 0) {
                this.value = 0;
            }
        });

        // Format nilai rupiah pada penghasilan
        document.getElementById('penghasilan').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        // Validasi input jumlah tanggungan (tidak boleh negatif)
        document.getElementById('tanggungan').addEventListener('input', function() {
            let value = parseInt(this.value);
            if (value < 0) {
                this.value = 0;
            }
        });

        // Validasi input jarak (tidak boleh negatif)
        document.getElementById('jarak').addEventListener('input', function() {
            let value = parseFloat(this.value);
            if (value < 0) {
                this.value = 0;
            }
        });

        // Form submission dengan AJAX
        document.getElementById('scholarshipForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validasi agreement checkbox
            if (!document.getElementById('agreement').checked) {
                alert('Anda harus menyetujui pernyataan untuk melanjutkan pendaftaran.');
                return false;
            }
            
            // Siapkan form data
            const formData = new FormData(this);
            
            // Kirim request AJAX
            fetch('process_application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Redirect ke halaman sukses atau tampilkan pesan sukses
                    alert('Pendaftaran beasiswa berhasil! Nomor aplikasi: ' + data.app_id);
                    window.location.href = 'beasiswa_status.php?app_id=' + data.app_id;
                } else {
                    // Tampilkan pesan error
                    alert('Terjadi kesalahan: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengirim data. Silakan coba lagi.');
            });
        });

        // Improved tooltip functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tooltips = document.querySelectorAll('.tooltip');
            
            tooltips.forEach(tooltip => {
                tooltip.addEventListener('mouseover', function() {
                    // Remove any existing tooltip texts first
                    const existingTooltip = this.querySelector('.tooltip-text');
                    if (existingTooltip) {
                        this.removeChild(existingTooltip);
                    }
                    
                    // Create new tooltip text
                    const tooltipText = document.createElement('div');
                    tooltipText.className = 'tooltip-text';
                    tooltipText.textContent = this.getAttribute('title');
                    this.appendChild(tooltipText);
                });
                
                tooltip.addEventListener('mouseout', function() {
                    const tooltipText = this.querySelector('.tooltip-text');
                    if (tooltipText) {
                        this.removeChild(tooltipText);
                    }
                });
            });
        });
    </script>
</body>
</html>