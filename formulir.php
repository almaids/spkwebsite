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
        
        <form id="scholarshipForm">
            <!-- Data Akademik -->
            <h2>Data Akademik</h2>
            
            <div class="form-group">
                <label for="nim" class="required">NIM (Nomor Induk Mahasiswa)</label>
                <input type="text" id="nim" name="nim" required placeholder="Contoh: 19001234">
            </div>
            
            <div class="form-group">
                <label for="nama" class="required">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap Anda">
            </div>
            
            <div class="form-group">
                <label for="prodi" class="required">Program Studi / Jurusan</label>
                <select id="prodi" name="prodi" required>
                    <option value="">-- Pilih Program Studi --</option>
                    <option value="informatika">Teknik Informatika</option>
                    <option value="sipil">Teknik Sipil</option>
                    <option value="elektro">Teknik Elektro</option>
                    <option value="mesin">Teknik Mesin</option>
                    <option value="industri">Teknik Industri</option>
                    <option value="kimia">Teknik Kimia</option>
                    <option value="manajemen">Manajemen</option>
                    <option value="akuntansi">Akuntansi</option>
                    <option value="ekonomi">Ekonomi</option>
                    <option value="hukum">Hukum</option>
                    <option value="kedokteran">Kedokteran</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="ipk" class="required">IPK (Indeks Prestasi Kumulatif)</label>
                <input type="number" id="ipk" name="ipk" step="0.01" min="0" max="4" required placeholder="Contoh: 3.75">
            </div>
            
            <!-- Informasi Sosial Ekonomi -->
            <h2>Informasi Sosial Ekonomi</h2>
            
            <div class="form-group">
                <label for="jarak" class="required">Jarak Rumah ke Kampus (km)</label>
                <input type="number" id="jarak" name="jarak" min="0" step="0.1" required placeholder="Contoh: 15.5">
            </div>
            
            <div class="form-group">
                <label for="penghasilan" class="required">Penghasilan Orang Tua per Bulan (Rp)</label>
                <input type="number" id="penghasilan" name="penghasilan" min="0" required placeholder="Contoh: 5000000">
            </div>
            
            <div class="form-group">
                <label for="tanggungan" class="required">Jumlah Tanggungan Orang Tua</label>
                <input type="number" id="tanggungan" name="tanggungan" min="0" required placeholder="Contoh: 3">
            </div>
            
            <!-- Dokumen Pendukung -->
            <h2>Dokumen Pendukung</h2>
            
            <div class="form-group">
                <label for="transkrip" class="required">Transkrip Nilai Terakhir</label>
                <input type="file" id="transkrip" name="transkrip" required>
            </div>
            
            <div class="form-group">
                <label for="surat_penghasilan" class="required">Surat Keterangan Penghasilan Orang Tua</label>
                <input type="file" id="surat_penghasilan" name="surat_penghasilan" required>
            </div>
            
            <div class="form-group">
                <label for="ktp" class="required">Scan KTP</label>
                <input type="file" id="ktp" name="ktp" required>
            </div>
            
            <div class="form-group">
                <label for="kk" class="required">Scan Kartu Keluarga</label>
                <input type="file" id="kk" name="kk" required>
            </div>
            
            <!-- Tombol Submit dan Reset -->
            <div class="buttons">
                <div>
                    <button type="submit" class="submit-btn">Daftar Beasiswa</button>
                </div>
                <div>
                    <button type="reset" class="reset-btn">Reset Form</button>
                </div>
            </div>
        </form>
        
        <p class="note">Catatan: Isian bertanda (*) wajib diisi. Pastikan semua dokumen yang diunggah dalam format PDF dengan ukuran maksimal 2MB per file.</p>
    </div>

    <script>
        document.getElementById('scholarshipForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validasi form sederhana
            const nim = document.getElementById('nim').value;
            const nama = document.getElementById('nama').value;
            const prodi = document.getElementById('prodi').value;
            const ipk = document.getElementById('ipk').value;
            
            if (!nim || !nama || !prodi || !ipk) {
                alert('Mohon lengkapi semua field yang wajib diisi!');
                return;
            }
            
            // Di sini akan mengirim data ke server (dalam contoh ini hanya simulasi)
            alert('Form berhasil dikirim! Terima kasih telah mendaftar beasiswa.');
            
            // Reset form setelah submit
            this.reset();
        });
    </script>
</body>
</html>