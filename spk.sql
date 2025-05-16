-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 16 Bulan Mei 2025 pada 05.10
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spk`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `beasiswa_applications`
--

CREATE TABLE `beasiswa_applications` (
  `id_app` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `tanggal_daftar` date NOT NULL,
  `status_keputusan` enum('belum diproses','diterima','ditolak') DEFAULT 'belum diproses',
  `status_dokumen` enum('belum lengkap','sedang diverifikasi','terverifikasi','ditolak') DEFAULT 'belum lengkap',
  `total_nilai` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokumen_mahasiswa`
--

CREATE TABLE `dokumen_mahasiswa` (
  `id_dok_mhs` int(11) NOT NULL,
  `app_id` int(11) NOT NULL,
  `dokumen_id` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `path_file` varchar(255) NOT NULL,
  `tanggal_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_verifikasi` enum('belum diverifikasi','valid','tidak valid') DEFAULT 'belum diverifikasi',
  `catatan_verifikasi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokumen_persyaratan`
--

CREATE TABLE `dokumen_persyaratan` (
  `id_dokumen` int(11) NOT NULL,
  `nama_dokumen` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `wajib` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `dokumen_persyaratan`
--

INSERT INTO `dokumen_persyaratan` (`id_dokumen`, `nama_dokumen`, `deskripsi`, `wajib`, `created_at`) VALUES
(1, 'Transkrip Nilai', 'Transkrip nilai terbaru yang menunjukkan IPK', 1, '2025-05-05 06:10:40'),
(2, 'KTP', 'Kartu Tanda Penduduk mahasiswa', 1, '2025-05-05 06:10:40'),
(3, 'Kartu Keluarga', 'Kartu keluarga untuk verifikasi jumlah tanggungan', 1, '2025-05-05 06:10:40'),
(4, 'Slip Gaji Orang Tua', 'Slip gaji atau surat keterangan penghasilan orang tua', 1, '2025-05-05 06:10:40'),
(5, 'Surat Keterangan Domisili', 'Surat keterangan tempat tinggal dari RT/RW', 1, '2025-05-05 06:10:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kriteria`
--

CREATE TABLE `kriteria` (
  `id_kriteria` int(11) NOT NULL,
  `nama_kriteria` varchar(100) NOT NULL,
  `bobot` float NOT NULL,
  `sifat` enum('benefit','cost') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kriteria`
--

INSERT INTO `kriteria` (`id_kriteria`, `nama_kriteria`, `bobot`, `sifat`) VALUES
(1, 'IPK', 0.4, 'benefit'),
(2, 'Jarak Tempat Tinggal', 0.2, 'benefit'),
(3, 'Penghasilan Orang Tua', 0.2, 'cost'),
(4, 'Tanggungan Orang Tua', 0.2, 'benefit');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mahasiswa` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `id_prodi` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai_kriteria`
--

CREATE TABLE `nilai_kriteria` (
  `id_nilai` int(11) NOT NULL,
  `app_id` int(11) NOT NULL,
  `kriteria_id` int(11) NOT NULL,
  `nilai` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `prodi`
--

CREATE TABLE `prodi` (
  `id_prodi` int(11) NOT NULL,
  `nama_prodi` varchar(100) NOT NULL,
  `kode_prodi` varchar(20) DEFAULT NULL,
  `jenjang` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `prodi`
--

INSERT INTO `prodi` (`id_prodi`, `nama_prodi`, `kode_prodi`, `jenjang`, `created_at`) VALUES
(1, 'Teknik Informatika', 'TI ', 'S1', '2025-05-04 12:17:34'),
(2, 'Sistem Informasi', 'SI', 'S1', '2025-05-04 12:17:34'),
(5, 'Bisnis Digital', 'BD', 'S1', '2025-05-04 12:19:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$7qMIDlqBhhdOsHhBTHowbeLRJ8O7Ibe1sZSL/ib4QS68sIjeZ88.a', 'admin', '2025-05-04 11:45:50');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `beasiswa_applications`
--
ALTER TABLE `beasiswa_applications`
  ADD PRIMARY KEY (`id_app`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`);

--
-- Indeks untuk tabel `dokumen_mahasiswa`
--
ALTER TABLE `dokumen_mahasiswa`
  ADD PRIMARY KEY (`id_dok_mhs`),
  ADD KEY `app_id` (`app_id`),
  ADD KEY `dokumen_id` (`dokumen_id`);

--
-- Indeks untuk tabel `dokumen_persyaratan`
--
ALTER TABLE `dokumen_persyaratan`
  ADD PRIMARY KEY (`id_dokumen`);

--
-- Indeks untuk tabel `kriteria`
--
ALTER TABLE `kriteria`
  ADD PRIMARY KEY (`id_kriteria`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mahasiswa_ibfk_2` (`id_prodi`);

--
-- Indeks untuk tabel `nilai_kriteria`
--
ALTER TABLE `nilai_kriteria`
  ADD PRIMARY KEY (`id_nilai`),
  ADD KEY `app_id` (`app_id`),
  ADD KEY `kriteria_id` (`kriteria_id`);

--
-- Indeks untuk tabel `prodi`
--
ALTER TABLE `prodi`
  ADD PRIMARY KEY (`id_prodi`),
  ADD UNIQUE KEY `nama_prodi` (`nama_prodi`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `beasiswa_applications`
--
ALTER TABLE `beasiswa_applications`
  MODIFY `id_app` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `dokumen_mahasiswa`
--
ALTER TABLE `dokumen_mahasiswa`
  MODIFY `id_dok_mhs` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT untuk tabel `dokumen_persyaratan`
--
ALTER TABLE `dokumen_persyaratan`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `kriteria`
--
ALTER TABLE `kriteria`
  MODIFY `id_kriteria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `nilai_kriteria`
--
ALTER TABLE `nilai_kriteria`
  MODIFY `id_nilai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT untuk tabel `prodi`
--
ALTER TABLE `prodi`
  MODIFY `id_prodi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `beasiswa_applications`
--
ALTER TABLE `beasiswa_applications`
  ADD CONSTRAINT `beasiswa_applications_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id_mahasiswa`);

--
-- Ketidakleluasaan untuk tabel `dokumen_mahasiswa`
--
ALTER TABLE `dokumen_mahasiswa`
  ADD CONSTRAINT `dokumen_mahasiswa_ibfk_1` FOREIGN KEY (`app_id`) REFERENCES `beasiswa_applications` (`id_app`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_mahasiswa_ibfk_2` FOREIGN KEY (`dokumen_id`) REFERENCES `dokumen_persyaratan` (`id_dokumen`);

--
-- Ketidakleluasaan untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `mahasiswa_ibfk_2` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `nilai_kriteria`
--
ALTER TABLE `nilai_kriteria`
  ADD CONSTRAINT `nilai_kriteria_ibfk_1` FOREIGN KEY (`app_id`) REFERENCES `beasiswa_applications` (`id_app`),
  ADD CONSTRAINT `nilai_kriteria_ibfk_2` FOREIGN KEY (`kriteria_id`) REFERENCES `kriteria` (`id_kriteria`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
