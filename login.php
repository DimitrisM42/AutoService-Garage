<?php
session_start();
require 'config.php';
require_once 'langs.php';

$error = "";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_active']) {
            $error = $trans['login_inactive'] ?? "Your account is not activated yet.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: index.php");
            exit();
        }
    } else {
        $error = $trans['login_invalid'] ?? "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $trans['login_title'] ?? 'Login - AutoService Garage' ?></title>
  <link rel="stylesheet" href="css/log-reg.css">
</head>
<body>
  <form method="POST" action="" class="log-reg-form">
    <div class="form-group">
      <img src="images/AutoServiceGarageLogo.png" alt="">
      <h2 style="text-align:center;"><?= $trans['login_header'] ?? 'Login' ?></h2>
      <input type="text" name="username" placeholder="<?= $trans['login_username'] ?? 'Username' ?>" required />
      <input type="password" name="password" placeholder="<?= $trans['login_password'] ?? 'Password' ?>" required />
    </div>

    <?php if (!empty($error)): ?>
      <div class="form-error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <button type="submit" class="form-button"><?= $trans['login_button'] ?? 'Login' ?></button>
    <p><?= $trans['login_no_account'] ?? "Don't have an account?" ?> <a href="register.php"><?= $trans['login_reg'] ?? 'Register' ?></a></p>
    <p><a href="forgot_password.php"><?= $trans['forgot_password'] ?? "Forgot my password"?></a></p>

  </form>
</body>
</html>
