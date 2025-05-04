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

            if ($pass === $row['password']) {
                $_SESSION['email'] = $row['email'];
                $_SESSION['user_id'] = $row['id_user'];

                header('location: dashboard.php');
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
</head>
<body>
    <div class="login-container">
        <?php
        if(isset($message)){
            foreach($message as $msg){
                echo '<div class="message">'.$msg.'</div>';
            }
        }
        ?>
        
        <form class="login" action="" method="post">
            <h2 class="title">Login</h2>
            <div class="input-group email">
                <input type="text" placeholder="Email" name="email"  id="email" required />
                <label for="email">Email</label>
            </div>
            <div class="input-group password">
                <input type="password" placeholder="Password" name="password" id="password" required />
                <label for="password">Password</label>
            </div>
            
            <button type="submit" value="LOGIN">Login</button>
        </form>
    </div>
</body>
</html>
