<?php
@include 'config.php';
session_start();

$message = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    if (empty($email) || empty($pass)) {
        $message[] = 'Email dan password wajib diisi!';
    } else {
        $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die(mysqli_error($conn));

        if (mysqli_num_rows($select_users) > 0) {
            $row = mysqli_fetch_assoc($select_users);
            
            // Debug untuk memeriksa panjang password hash yang tersimpan
            // $message[] = 'Panjang password: ' . strlen($row['password']);
            
            // Verifikasi password dengan hash
            if (password_verify($pass, $row['password'])) {
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_id'] = $row['id_user'];
                $_SESSION['role'] = $row['role']; // Tambahkan role ke session
                $_SESSION['username'] = $row['username']; // Tambahkan username ke session

                // Cek role untuk redirect ke halaman yang sesuai
                if ($row['role'] === 'admin') {
                    header('location: dashboard.php');
                } else {
                    header('location: homepage.php');
                }
                exit();
            } else {
                $message[] = 'Password salah!';
            }
        } else {
            $message[] = 'Email tidak ditemukan!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        .login-link {
            text-align: center;
            margin-top: 15px;
            opacity: 0;
            animation: fadeIn 0.5s 1s forwards;
        }
        
        .login-link a {
            color: var(--blueQueen);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: var(--redFire);
        }
        
        .success-message {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(46, 204, 113, 0.2);
            animation: fadeIn 0.5s ease-in-out;
            border-left: 4px solid #2ecc71;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php
        if(isset($message)){
            foreach($message as $msg){
                // Jika pesan berisi 'berhasil', tampilkan dengan kelas success-message
                if(strpos($msg, 'berhasil') !== false){
                    echo '<div class="success-message">'.$msg.'</div>';
                } else {
                    echo '<div class="message">'.$msg.'</div>';
                }
            }
        }
        
        // Tampilkan pesan redirect jika ada
        if(isset($_GET['registered']) && $_GET['registered'] == 'success'){
            echo '<div class="success-message">Registrasi berhasil! Silakan login dengan akun Anda.</div>';
        }
        ?>
        
        <form class="login" action="" method="post">
            <h2 class="title">Login</h2>
            <div class="input-group email">
                <input type="text" placeholder="Email" name="email" id="email" required />
                <label for="email">Email</label>
            </div>
            <div class="input-group password">
                <input type="password" placeholder="Password" name="password" id="password" required />
                <label for="password">Password</label>
            </div>
            
            <button type="submit" value="LOGIN">Login</button>

            <div class="login-link">
                Belum punya akun? <a href="registrasi.php">Daftar disini</a>
            </div>
        </form>
    </div>
</body>
</html>