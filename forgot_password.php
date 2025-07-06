<?php
session_start();
require 'config.php';
require_once 'langs.php';

$mode = $_GET['mode'] ?? 'request';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'request') {
    $email = $_POST['email'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() === 0) {
        $message = $trans['forgot_email_not_found'] ?? "This email is not registered.";
    } else {
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 3600 * 24); // 1 ώρα

        $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$email, $token, $expires]);

        $resetLink = "http://localhost/AutoServiceGarage/forgot_password.php?mode=reset&token=$token";

        $message = "<a href='$resetLink'>" . ($trans['forgot_reset_link'] ?? "Click here to reset your password") . "</a>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'reset') {
    $token = $_POST['token'] ?? '';
    $newPass = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")
            ->execute([$hashed, $reset['email']]);

        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset['email']]);

        $message = ($trans['forgot_updated'] ?? "Your password has been updated.") . ' <a href="login.php">' . ($trans['forgot_login'] ?? "Go to Login") . '</a>';
    } else {
        $message = $trans['forgot_invalid_token'] ?? "Invalid or expired token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $mode === 'request' ? 'Forgot Password' : 'Reset Password' ?></title>
    <link rel="stylesheet" href="css/log-reg.css">
</head>
<body>

<div class="log-reg-form">
    <h2 class="form-title"><?= $mode === 'request' ? ($trans['forgot_title'] ?? 'Forgot Password') : ($trans['reset_title'] ?? 'Reset Password') ?></h2>

    <?php if ($message){ ?>
        <div class="<?= str_contains($message, 'updated') || str_contains($message, 'Click here') ? 'success' : 'error' ?>">
            <?= $message ?>
        </div>
    <?php } ?>

    <?php if ($mode === 'request'){ ?>
        <form method="post">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="form-button">Send Reset Link</button>
        </form>

    <?php }elseif ($mode === 'reset'){ ?>
        <form method="post" action="?mode=reset">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
            <div class="form-group">
                <label><?= $trans['reset_password'] ?? 'New Password' ?>:</label>
                <input type="password" name="password" placeholder="<?= $trans['reset_placeholder'] ?? 'Enter new password' ?>" required>
            </div>
            <button type="submit" class="form-button"><?= $trans['reset_button'] ?? 'Reset Password' ?></button>
        </form>
    <?php } ?>
</div>

</body>
</html>

