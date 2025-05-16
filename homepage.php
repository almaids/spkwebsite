<?php
// Start session if not already started
session_start();

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);

// Default status for nav items (if not logged in, everything is inactive)
$profile_complete = false;
$has_applied = false;

if ($logged_in) {
    // Include database connection
    require_once 'config.php';
    
    $user_id = $_SESSION['user_id'];
    
    // Check if profile is complete
    $profile_query = "SELECT m.*, p.nama_prodi 
                      FROM mahasiswa m 
                      LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
                      WHERE m.user_id = ?";
    
    $profile_stmt = $conn->prepare($profile_query);
    $profile_stmt->bind_param("i", $user_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    
    // If student record exists and has basic required fields
    if ($profile_result->num_rows > 0) {
        $profile_data = $profile_result->fetch_assoc();
        $profile_complete = !empty($profile_data['nim']) && 
                           !empty($profile_data['semester']) && 
                           !empty($profile_data['nama_prodi']);
    }
    
    // Check if user has already applied for scholarship
    $application_query = "SELECT COUNT(*) as application_count 
                         FROM beasiswa_applications ba
                         JOIN mahasiswa m ON ba.mahasiswa_id = m.id_mahasiswa
                         WHERE m.user_id = ?";
                         
    $application_stmt = $conn->prepare($application_query);
    $application_stmt->bind_param("i", $user_id);
    $application_stmt->execute();
    $application_result = $application_stmt->get_result();
    $application_data = $application_result->fetch_assoc();
    
    $has_applied = ($application_data['application_count'] > 0);
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pendaftaran Beasiswa</title>
    <link rel="stylesheet" href="css/home.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .dropdown-toggle .fa-chevron-down {
            font-size: 0.8rem;
            margin-left: 0.3rem;
            transition: transform 0.3s;
        }
        
        .dropdown.active .fa-chevron-down {
            transform: rotate(180deg);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            border-radius: 0 0 4px 4px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s;
            z-index: 101;
            display: none; /* Penting: tetap tersembunyi secara default */
        }
        
        /* Hapus hover effect */
        .dropdown:hover .dropdown-menu {
            opacity: 0;
            visibility: hidden;
        }
        
        /* Hanya tampilkan saat active */
        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            display: block;
        }
        
        .dropdown-menu a {
            padding: 1rem 1.5rem;
            display: block;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .dropdown-menu a:last-child {
            border-bottom: none;
        }
        
        .dropdown-menu a:hover {
            background-color: rgba(244, 91, 105, 0.1);
        }

        /* Nav item disabled style */
        .nav-item-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Notification popup */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: #fff;
            color: #333;
            border-left: 4px solid #f45b69;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            z-index: 1000;
            max-width: 350px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .notification-message {
            font-size: 14px;
        }
        
        .notification-close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 16px;
        }
        
        /* Responsive styling */
        @media (max-width: 768px) {
            nav ul {
                flex-direction: column;
                width: 100%;
            }
            
            nav a {
                padding: 1rem 1.5rem;
                justify-content: center;
            }
            
            .dropdown-menu {
                position: static;
                box-shadow: none;
                width: 100%;
            }
            
            .dropdown-menu a {
                padding-left: 3rem;
                text-align: left;
            }
            
            .notification {
                left: 20px;
                right: 20px;
                max-width: calc(100% - 40px);
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Portal Beasiswa Mahasiswa</h1>
        <p>Wujudkan Cita-Cita Akademismu dengan Dukungan Beasiswa</p>
    </header>
    
    <nav>
        <ul>
            <li><a href="#" class="active"><i class="fas fa-home"></i> Beranda</a></li>
            <li><a href="beasiswa.php"><i class="fas fa-graduation-cap"></i> Info Beasiswa</a></li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-user-circle"></i> Akun Saya <i class="fas fa-chevron-down"></i></a>
                <div class="dropdown-menu">
                    <a href="profile.php"><i class="fas fa-id-card"></i> Profil</a>
                    <a href="javascript:void(0)" class="<?php echo !$profile_complete ? 'nav-item-disabled' : ($has_applied ? 'nav-item-disabled' : ''); ?>" 
                       data-action="daftar-beasiswa" 
                       data-complete="<?php echo $profile_complete ? 'true' : 'false'; ?>" 
                       data-applied="<?php echo $has_applied ? 'true' : 'false'; ?>">
                        <i class="fas fa-edit"></i> Daftar Beasiswa
                    </a>
                    <a href="javascript:void(0)" class="<?php echo !$profile_complete || !$has_applied ? 'nav-item-disabled' : ''; ?>" 
                       data-action="status-beasiswa" 
                       data-complete="<?php echo $profile_complete ? 'true' : 'false'; ?>" 
                       data-applied="<?php echo $has_applied ? 'true' : 'false'; ?>">
                        <i class="fas fa-tasks"></i> Status Beasiswa
                    </a>
                    <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </li>
        </ul>
    </nav>
    
    <!-- Notification Element -->
    <div id="notification" class="notification">
        <div class="notification-close">&times;</div>
        <div class="notification-title">Pemberitahuan</div>
        <div class="notification-message"></div>
    </div>
    
    <div class="container">
        <section class="hero">
            <div class="hero-content">
                <h2>Raih Kesempatan Beasiswa untuk Masa Depan Lebih Cerah</h2>
                <p>Portal Beasiswa Mahasiswa menyediakan program beasiswa kampus bagi mahasiswa berprestasi. Daftarkan dirimu sekarang dan raih kesempatanmu!</p>
                <?php if ($logged_in): ?>
                    <?php if (!$profile_complete): ?>
                        <a href="profile.php" class="cta-button">Lengkapi Profil</a>
                    <?php elseif (!$has_applied): ?>
                        <a href="formulir.php" class="cta-button">Daftar Sekarang</a>
                    <?php else: ?>
                        <a href="beasiswa_status.php" class="cta-button">Lihat Status</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="cta-button">Login untuk Mendaftar</a>
                <?php endif; ?>
            </div>
            <div class="hero-image">
                <img src="logo.png" alt="Mahasiswa Berprestasi">
            </div>
        </section>
        
        <!-- Rest of the content remains the same -->
        <section class="features">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-medal"></i></div>
                <h3>Beasiswa Prestasi Kampus</h3>
                <p>Beasiswa khusus dari kampus untuk mahasiswa berprestasi baik di bidang akademik maupun non-akademik sesuai kriteria yang ditetapkan oleh kampus.</p>
            </div>
        </section>
        
        <section class="steps">
            <h2>Cara Mendaftar Beasiswa</h2>
            <div class="step-list">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h3>Buat Akun</h3>
                    <p>Daftarkan akun baru dengan mengisi formulir pendaftaran dengan data yang valid.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h3>Lengkapi Profil</h3>
                    <p>Lengkapi data diri dan informasi akademik yang diperlukan.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h3>Lengkapi Dokumen</h3>
                    <p>Unggah semua dokumen pendukung yang diperlukan sesuai persyaratan kampus.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">4</div>
                    <h3>Kirim Aplikasi</h3>
                    <p>Tinjau dan kirimkan aplikasi beasiswamu untuk diproses lebih lanjut.</p>
                </div>
            </div>
        </section>
        
        <section class="testimonials">
            <h2>Apa Kata Penerima Beasiswa</h2>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p>"Beasiswa ini telah mengubah hidup saya dan membantu saya fokus pada studi tanpa khawatir masalah finansial."</p>
                    <div class="testimonial-author">
                        <div class="author-image"></div>
                        <div class="author-details">
                            <h4>Andi Pratama</h4>
                            <p>Mahasiswa Teknik Informatika</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p>"Saya sangat bersyukur mendapatkan beasiswa ini. Proses pendaftarannya mudah dan transparan."</p>
                    <div class="testimonial-author">
                        <div class="author-image"></div>
                        <div class="author-details">
                            <h4>Dina Maharani</h4>
                            <p>Mahasiswa Kedokteran</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p>"Berkat beasiswa ini, saya bisa mengikuti berbagai seminar internasional yang sangat bermanfaat."</p>
                    <div class="testimonial-author">
                        <div class="author-image"></div>
                        <div class="author-details">
                            <h4>Budi Santoso</h4>
                            <p>Mahasiswa Ekonomi</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="faq">
            <h2>Pertanyaan Umum</h2>
            <div class="faq-item">
                <h3>Siapa yang bisa mendaftar beasiswa?</h3>
                <p>Semua mahasiswa aktif dengan IPK minimal 3.0 dan memenuhi persyaratan spesifik program beasiswa kampus.</p>
            </div>
            <div class="faq-item">
                <h3>Kapan batas waktu pendaftaran?</h3>
                <p>Periode pendaftaran untuk program beasiswa kampus dapat dilihat di halaman Info Beasiswa.</p>
            </div>
            <div class="faq-item">
                <h3>Bagaimana proses seleksi dilakukan?</h3>
                <p>Seleksi dilakukan berdasarkan prestasi akademik, kebutuhan finansial, dan kriteria khusus lainnya sesuai dengan ketentuan kampus.</p>
            </div>
            <div class="faq-item">
                <h3>Apakah beasiswa ini perlu dikembalikan?</h3>
                <p>Tidak, beasiswa yang kami sediakan adalah hibah yang tidak perlu dikembalikan selama penerima memenuhi persyaratan akademik.</p>
            </div>
        </section>
    </div>
    
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Tentang Kami</h3>
                <p>Portal Beasiswa Mahasiswa didedikasikan untuk membantu mahasiswa berprestasi meraih pendidikan tinggi tanpa kendala finansial.</p>
            </div>
            <div class="footer-section">
                <h3>Tautan Cepat</h3>
                <ul>
                    <li><a href="#">Beranda</a></li>
                    <li><a href="beasiswa.php">Info Beasiswa</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="registrasi.php">Daftar</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Hubungi Kami</h3>
                <p>Email: info@beasiswamahasiswa.ac.id</p>
                <p>Telepon: (021) 1234-5678</p>
                <p>Alamat: Jl. Pendidikan No. 123, Jakarta</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Sistem Pendukung Keputusan. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
    // Script untuk dropdown yang hanya muncul saat diklik (bukan hover)
    document.addEventListener('DOMContentLoaded', function() {
        // Get all dropdown elements
        const dropdowns = document.querySelectorAll('.dropdown');
        
        // Add click event to toggle dropdown visibility
        dropdowns.forEach(dropdown => {
            const toggleBtn = dropdown.querySelector('.dropdown-toggle');
            
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default anchor behavior
                
                // Toggle active class for current dropdown
                dropdown.classList.toggle('active');
                
                // Close other dropdowns
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown && otherDropdown.classList.contains('active')) {
                        otherDropdown.classList.remove('active');
                    }
                });
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
        
        // Add keyboard accessibility
        dropdowns.forEach(dropdown => {
            const toggleBtn = dropdown.querySelector('.dropdown-toggle');
            
            toggleBtn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    dropdown.classList.toggle('active');
                }
            });
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });
        
        // Notification functions
        function showNotification(title, message, duration = 4000) {
            const notification = document.getElementById('notification');
            const notificationTitle = notification.querySelector('.notification-title');
            const notificationMessage = notification.querySelector('.notification-message');
            
            notificationTitle.textContent = title;
            notificationMessage.textContent = message;
            
            notification.classList.add('show');
            
            // Hide notification after duration
            setTimeout(() => {
                notification.classList.remove('show');
            }, duration);
        }
        
        // Close notification when clicking on close button
        const notificationClose = document.querySelector('.notification-close');
        notificationClose.addEventListener('click', function() {
            document.getElementById('notification').classList.remove('show');
        });
        
        // Handle conditionally disabled nav items
        const conditionalNavItems = document.querySelectorAll('[data-action]');
        
        conditionalNavItems.forEach(item => {
            item.addEventListener('click', function(e) {
                if (item.classList.contains('nav-item-disabled')) {
                    e.preventDefault();
                    
                    const action = item.getAttribute('data-action');
                    const isProfileComplete = item.getAttribute('data-complete') === 'true';
                    const hasApplied = item.getAttribute('data-applied') === 'true';
                    
                    // Different notifications based on the condition
                    if (action === 'daftar-beasiswa') {
                        if (!isProfileComplete) {
                            showNotification('Profil Belum Lengkap', 'Maaf, lengkapi dulu data profil Anda sebelum mendaftar beasiswa.');
                        } else if (hasApplied) {
                            showNotification('Sudah Terdaftar', 'Maaf, Anda sudah melakukan pendaftaran beasiswa.');
                        }
                    } else if (action === 'status-beasiswa') {
                        if (!isProfileComplete) {
                            showNotification('Profil Belum Lengkap', 'Maaf, lengkapi dulu data profil Anda sebelum melihat status beasiswa.');
                        } else if (!hasApplied) {
                            showNotification('Belum Mendaftar', 'Maaf, Anda belum melakukan pendaftaran beasiswa.');
                        }
                    }
                } else {
                    // If not disabled, redirect to the appropriate page
                    const action = item.getAttribute('data-action');
                    if (action === 'daftar-beasiswa') {
                        window.location.href = 'formulir.php';
                    } else if (action === 'status-beasiswa') {
                        window.location.href = 'beasiswa_status.php';
                    }
                }
            });
        });
    });
    </script>
</body>
</html>