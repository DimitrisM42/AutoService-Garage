<?php
session_start();
require 'config.php';
require_once 'langs.php';

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $full_name = $_POST['full_name'];
    $id_number = $_POST['id_number'];
    $role = $_POST['role'];

    $check = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR id_number = ?");
    $check->execute([$username, $email, $id_number]);

    if ($check->rowCount() > 0) {
        $errors[] = $trans['register_exists'] ?? "User with the same username, email or ID number already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, full_name, id_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role, $full_name, $id_number]);
        $user_id = $pdo->lastInsertId();

        if ($role === 'customer') {
            $vat_number = $_POST['vat_number'];
            $address = $_POST['address'];
            $stmt = $pdo->prepare("INSERT INTO customers (user_id, vat_number, address) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $vat_number, $address]);
        } elseif ($role === 'mechanic') {
            $specialty = $_POST['specialty'];
            $stmt = $pdo->prepare("INSERT INTO mechanics (user_id, specialty) VALUES (?, ?)");
            $stmt->execute([$user_id, $specialty]);
        }

        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $trans['register_title'] ?? 'Register' ?></title>
  <link rel="stylesheet" href="css/log-reg.css">
</head>
<body>

  <?php if (!empty($errors)): ?>
    <div class="error"><?= implode('<br>', $errors); ?></div>
  <?php elseif ($success): ?>
    <div class="success"><?= $trans['register_success'] ?? 'Registration successful! Please wait for activation.' ?></div>
  <?php endif; ?>

  <form method="POST" action="" class="log-reg-form">
    <div class="form-group">
      <img src="images/AutoServiceGarageLogo.png" alt="">
      <h2 style="text-align:center;"><?= $trans['register_header'] ?? 'Register' ?></h2>

      <input type="text" name="username" placeholder="<?= $trans['register_username'] ?? 'Username' ?>" required>
      <input type="text" name="full_name" placeholder="<?= $trans['register_fullname'] ?? 'Full Name' ?>" required>
      <input type="text" name="id_number" placeholder="<?= $trans['register_id'] ?? 'ID Number' ?>" required>
      <input type="email" name="email" placeholder="<?= $trans['register_email'] ?? 'Email' ?>" required>
      <input type="password" name="password" placeholder="<?= $trans['register_password'] ?? 'Password' ?>" required>

      <label for="role" class="form-label"><?= $trans['register_role'] ?? 'Role' ?>:</label>
      <select name="role" required onchange="showExtraFields(this.value)">
        <option value=""><?= $trans['register_select_role'] ?? 'Select Role' ?></option>
        <option value="customer"><?= $trans['register_customer'] ?? 'Customer' ?></option>
        <option value="mechanic"><?= $trans['register_mechanic'] ?? 'Mechanic' ?></option>
      </select>

      <div id="customer-fields" class="extra-fields">
        <input type="text" name="vat_number" placeholder="<?= $trans['register_vat'] ?? 'VAT Number' ?>">
        <input type="text" name="address" placeholder="<?= $trans['register_address'] ?? 'Address' ?>">
      </div>

      <div id="mechanic-fields" class="extra-fields">
        <input type="text" name="specialty" placeholder="<?= $trans['register_specialty'] ?? 'Specialty' ?>">
      </div>
    </div>

    <button type="submit" class="form-button"><?= $trans['register_button'] ?? 'Register' ?></button>
    <p><?= $trans['register_have_account'] ?? 'Already have an account?' ?> <a href="login.php"><?= $trans['register_login'] ?? 'Login' ?></a></p>
  </form>

  <script>
    function showExtraFields(role) {
      document.getElementById('customer-fields').style.display = role === 'customer' ? 'block' : 'none';
      document.getElementById('mechanic-fields').style.display = role === 'mechanic' ? 'block' : 'none';
    }

    document.addEventListener("DOMContentLoaded", function() {
      const role = document.querySelector("select[name='role']").value;
      if (role) showExtraFields(role);
    });
  </script>
</body>
</html>
