<?php 
include 'includes/dash_header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}


if ($user['role'] === 'customer') {
    $stmt = $pdo->prepare("SELECT address, vat_number FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customerData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customerData) {
        $user = array_merge($user, $customerData);
    }
}

if ($user['role'] === 'mechanic') {
    $stmt = $pdo->prepare("SELECT specialty FROM mechanics WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $mechanicData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($mechanicData) {
        $user = array_merge($user, $mechanicData);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = $_POST['full_name'];
  $email = $_POST['email'];
  $id_number = $_POST['id_number'];
  $username = $_POST['username'];

  $check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR id_number = ? OR username = ?) AND id != ?");
  $check->execute([$email, $id_number, $username, $user_id]);

  if ($check->rowCount() > 0) {
      $error = "The email, ID number or username is already used by another user.";
  } else {

      $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, id_number = ?, username = ? WHERE id = ?");
      $stmt->execute([$full_name, $email, $id_number, $username, $user_id]);

      
      if ($user['role'] === 'customer') {
          $address = $_POST['address'];
          $vat_number = $_POST['vat_number'];

          $stmt = $pdo->prepare("UPDATE customers SET address = ?, vat_number = ? WHERE user_id = ?");
          $stmt->execute([$address, $vat_number, $user_id]);
      }

      if ($user['role'] === 'mechanic') {
          $specialty = $_POST['specialty'];

          $stmt = $pdo->prepare("UPDATE mechanics SET specialty = ? WHERE user_id = ?");
          $stmt->execute([$specialty, $user_id]);
      }

      $_SESSION['full_name'] = $full_name;
      $success = "Profile updated successfully!";

      
      $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
      $stmt->execute([$user_id]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user['role'] === 'customer') {
          $stmt = $pdo->prepare("SELECT address, vat_number FROM customers WHERE user_id = ?");
          $stmt->execute([$user_id]);
          $customerData = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($customerData) {
              $user = array_merge($user, $customerData);
          }
      }
      if ($user['role'] === 'mechanic') {
          $stmt = $pdo->prepare("SELECT specialty FROM mechanics WHERE user_id = ?");
          $stmt->execute([$user_id]);
          $mechanicData = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($mechanicData) {
              $user = array_merge($user, $mechanicData);
          }
      }

  }

//password   
  if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
      $currentPass = $_POST['current_password'] ?? '';
      $newPass = $_POST['new_password'] ?? '';
      $confirmPass = $_POST['confirm_password'] ?? '';

      if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
          $error = "To change your password, fill in all password fields.";
      } elseif (!password_verify($currentPass, $user['password'])) {
          $error = "Current password is incorrect.";
      } elseif ($newPass !== $confirmPass) {
          $error = "New passwords do not match.";
      } else {
          $hashed = password_hash($newPass, PASSWORD_BCRYPT);
          $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
          $stmt->execute([$hashed, $user_id]);
          $success = "Password changed successfully.";
      }
  }

}


//delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
  
    if ($user['role'] === 'customer') {
        $carStmt = $pdo->prepare("SELECT id FROM cars WHERE customer_id = ?");
        $carStmt->execute([$user_id]);
        $carIds = $carStmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($carIds as $carId) {
            $pdo->prepare("DELETE FROM appointments WHERE car_id = ?")->execute([$carId]);
        }

        $pdo->prepare("DELETE FROM cars WHERE customer_id = ?")->execute([$user_id]);

        $pdo->prepare("DELETE FROM customers WHERE user_id = ?")->execute([$user_id]);

    } elseif ($user['role'] === 'mechanic') {
        
        $pdo->prepare("UPDATE appointments SET mechanic_id = NULL WHERE mechanic_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM mechanics WHERE user_id = ?")->execute([$user_id]);
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

    session_destroy();
    header("Location: login.php");
    exit();
}



?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php'; 
  include 'includes/mobile_bar.php';?>
  <div class="content-area">

    <h2 class="form-title"><?= $trans['my_profile'] ?? 'My Profile' ?></h2>

    <?php if ($error): ?>
      <p class="form-message error"><?= $error ?></p>
    <?php elseif ($success): ?>
      <p class="form-message success"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" action="" class="generic-form">

      <div class="form-group">
        <label><?= $trans['full_name'] ?? 'Full Name' ?>:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
      </div>

      <div class="form-group">
        <label><?= $trans['email'] ?? 'Email' ?>:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
      </div>

      <div class="form-group">
        <label><?= $trans['id_number'] ?? 'ID Number' ?>:</label>
        <input type="text" name="id_number" value="<?= htmlspecialchars($user['id_number']) ?>" required>
      </div>

      <div class="form-group">
        <label><?= $trans['username'] ?? 'Username' ?>:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
      </div>

      <div class="form-group">
        <label><?= $trans['role'] ?? 'Role' ?>:</label>
        <input type="text" value="<?= ucfirst($user['role']) ?>" disabled>
      </div>

      <?php if ($user['role'] === 'customer'): ?>
        <div class="form-group">
          <label><?= $trans['address'] ?? 'Address' ?>:</label>
          <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label><?= $trans['vat_number'] ?? 'VAT Number' ?>:</label>
          <input type="text" name="vat_number" value="<?= htmlspecialchars($user['vat_number'] ?? '') ?>">
        </div>
      <?php endif; ?>

      <?php if ($user['role'] === 'mechanic'): ?>
        <div class="form-group">
          <label><?= $trans['specialty'] ?? 'Specialty' ?>:</label>
          <input type="text" name="specialty" value="<?= htmlspecialchars($user['specialty'] ?? '') ?>">
        </div>
      <?php endif; ?>

      <h3><?= $trans['change_password'] ?? 'Change Password' ?></h3>

      <div class="form-group">
        <label><?= $trans['current_password'] ?? 'Current Password' ?>:</label>
        <input type="password" name="current_password">
      </div>

      <div class="form-group">
        <label><?= $trans['new_password'] ?? 'New Password' ?>:</label>
        <input type="password" name="new_password">
      </div>

      <div class="form-group">
        <label><?= $trans['confirm_password'] ?? 'Confirm New Password' ?>:</label>
        <input type="password" name="confirm_password">
      </div>


      <button type="submit" class="form-button"><?= $trans['save_changes'] ?? 'Save Changes' ?></button>
    </form>

    <form method="POST" onsubmit="return confirm('<?= $trans['confirm_delete'] ?? 'Are you sure you want to delete your account?' ?>');">
      <input type="hidden" name="delete_account" value="1">
      <button type="submit" class="delete-button" style="padding: 10px 20px; margin-top: 20px;"><?= $trans['delete_account'] ?? 'Delete Account' ?></button>
    </form>

  </div>
</div>

</body>
</html>
