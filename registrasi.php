<?php
include 'config.php';

$nama = '';
$email = '';
$password = '';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validasi input
    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi.";
    } else {
        // Cek apakah email sudah digunakan
        $sql = "SELECT id_user FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            // Hash password dengan password_hash
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Simpan data ke database
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'mahasiswa')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $nama, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil. Anda akan dialihkan ke halaman login dalam 2 detik.";
                $nama = $email = $password = '';
                // Redirect setelah 2 detik
                header("Refresh: 2; url=login.php?registered=success");
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrasi</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="login-container">
    <form class="login" method="post" action="">
        <h2>Registrasi</h2>

        <?php if ($error): ?>
            <div class="message"><?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="message" style="color: green; border-left-color: green;"><?= $success ?></div>
        <?php endif; ?>

        <div class="input-group email">
            <input type="text" name="nama" required placeholder="Nama" value="<?= htmlspecialchars($nama) ?>">
            <label>Nama</label>
        </div>

        <div class="input-group email">
            <input type="email" name="email" required placeholder="Email" value="<?= htmlspecialchars($email) ?>">
            <label>Email</label>
        </div>

        <div class="input-group password">
            <input type="password" name="password" required placeholder="Password">
            <label>Password</label>
        </div>

        <button type="submit">Daftar</button>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Masuk disini</a>
        </div>
    </form>
</div>
</body>
</html>