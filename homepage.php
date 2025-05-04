<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pendaftaran Beasiswa</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --accent: #f39c12;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
            padding: 2rem 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        nav {
            background-color: white;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        nav a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        
        nav a:hover {
            color: var(--primary);
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .hero {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .hero-content {
            flex: 1;
        }
        
        .hero h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .hero p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            color: #555;
        }
        
        .hero-image {
            flex: 1;
            text-align: center;
        }
        
        .hero-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .cta-button {
            display: inline-block;
            background-color: var(--accent);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .cta-button:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .feature-card {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .feature-card h3 {
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .steps {
            background-color: white;
            padding: 3rem 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }
        
        .steps h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            color: var(--dark);
        }
        
        .step-list {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .step-item {
            flex: 1;
            min-width: 200px;
            text-align: center;
            padding: 1.5rem;
            position: relative;
        }
        
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .step-item h3 {
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .step-item p {
            font-size: 0.95rem;
            color: #666;
        }
        
        .testimonials {
            margin-bottom: 3rem;
        }
        
        .testimonials h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            color: var(--dark);
        }
        
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .testimonial-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .testimonial-card p {
            font-style: italic;
            margin-bottom: 1rem;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary);
            margin-right: 1rem;
        }
        
        .author-details h4 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }
        
        .author-details p {
            font-size: 0.9rem;
            margin: 0;
            color: #666;
        }
        
        .faq {
            background-color: white;
            padding: 3rem 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }
        
        .faq h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            color: var(--dark);
        }
        
        .faq-item {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1.5rem;
        }
        
        .faq-item h3 {
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
            color: var(--primary);
        }
        
        .faq-item p {
            color: #555;
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            padding: 3rem 1rem;
            text-align: center;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: left;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: var(--light);
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .social-icons {
            margin-bottom: 1rem;
        }
        
        .social-icons a {
            display: inline-block;
            margin: 0 0.5rem;
            color: white;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
            }
            
            nav ul {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .step-list {
                flex-direction: column;
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
            <li><a href="#">Beranda</a></li>
            <li><a href="#">Jenis Beasiswa</a></li>
            <li><a href="#">Persyaratan</a></li>
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Kontak</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <section class="hero">
            <div class="hero-content">
                <h2>Raih Kesempatan Beasiswa untuk Masa Depan Lebih Cerah</h2>
                <p>Portal Beasiswa Mahasiswa menyediakan berbagai program beasiswa bagi mahasiswa berprestasi. Daftarkan dirimu sekarang dan raih kesempatanmu!</p>
                <a href="login.php" class="cta-button">Daftar Sekarang</a>
            </div>
            <div class="hero-image">
                <img src="/api/placeholder/600/400" alt="Mahasiswa Berprestasi">
            </div>
        </section>
        
        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">🎓</div>
                <h3>Beasiswa Prestasi Akademik</h3>
                <p>Beasiswa untuk mahasiswa dengan IPK tinggi dan prestasi akademik yang menonjol di berbagai bidang studi.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3>Beasiswa Non-Akademik</h3>
                <p>Penghargaan untuk mahasiswa berprestasi di bidang olahraga, seni, dan kegiatan ekstrakurikuler lainnya.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💼</div>
                <h3>Beasiswa Penelitian</h3>
                <p>Dukungan finansial untuk mahasiswa yang aktif dalam penelitian dan pengembangan ilmu pengetahuan.</p>
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
                    <h3>Pilih Program Beasiswa</h3>
                    <p>Pilih program beasiswa yang sesuai dengan kriteria dan minatmu.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h3>Lengkapi Dokumen</h3>
                    <p>Unggah semua dokumen pendukung yang diperlukan sesuai persyaratan.</p>
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
                <p>Semua mahasiswa aktif dengan IPK minimal 3.0 dan memenuhi persyaratan spesifik program beasiswa yang dipilih.</p>
            </div>
            <div class="faq-item">
                <h3>Kapan batas waktu pendaftaran?</h3>
                <p>Periode pendaftaran bervariasi untuk setiap program beasiswa. Silakan periksa halaman detail masing-masing program.</p>
            </div>
            <div class="faq-item">
                <h3>Bagaimana proses seleksi dilakukan?</h3>
                <p>Seleksi dilakukan berdasarkan prestasi akademik, kebutuhan finansial, dan kriteria khusus lainnya sesuai dengan jenis beasiswa.</p>
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
                    <li><a href="#">Jenis Beasiswa</a></li>
                    <li><a href="#">Persyaratan</a></li>
                    <li><a href="#">Pendaftaran</a></li>
                    <li><a href="#">Kontak</a></li>
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
            <div class="social-icons">
                <a href="#">📱</a>
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🐦</a>
            </div>
            <p>&copy; 2025 Portal Beasiswa Mahasiswa. Hak Cipta Dilindungi.</p>
        </div>
    </footer>
</body>
</html>