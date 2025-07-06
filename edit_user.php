<?php
include 'includes/dash_header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'secretary') {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$id = $_GET['id'];
$error = "";
$success = "";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$role = $user['role'];
$extra = [];

if ($role === 'customer') {
    $stmt = $pdo->prepare("SELECT address, vat_number FROM customers WHERE user_id = ?");
    $stmt->execute([$id]);
    $extra = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($role === 'mechanic') {
    $stmt = $pdo->prepare("SELECT specialty FROM mechanics WHERE user_id = ?");
    $stmt->execute([$id]);
    $extra = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $id_number = $_POST['id_number'];
    $username = $_POST['username'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR id_number = ? OR username = ?) AND id != ?");
    $check->execute([$email, $id_number, $username, $id]);
    if ($check->rowCount() > 0) {
        $error = $trans['error_user_exists'] ?? "Email, ID ή username χρησιμοποιούνται ήδη από άλλον χρήστη.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, id_number = ?, username = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $id_number, $username, $is_active, $id]);

        if ($role === 'customer') {
            $address = $_POST['address'] ?? '';
            $vat_number = $_POST['vat_number'] ?? '';
            $stmt = $pdo->prepare("UPDATE customers SET address = ?, vat_number = ? WHERE user_id = ?");
            $stmt->execute([$address, $vat_number, $id]);
        } elseif ($role === 'mechanic') {
            $specialty = $_POST['specialty'] ?? '';
            $stmt = $pdo->prepare("UPDATE mechanics SET specialty = ? WHERE user_id = ?");
            $stmt->execute([$specialty, $id]);
        }

        $success = $trans['user_updated'] ?? "User updated successfully.";

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role === 'customer') {
            $stmt = $pdo->prepare("SELECT address, vat_number FROM customers WHERE user_id = ?");
            $stmt->execute([$id]);
            $extra = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($role === 'mechanic') {
            $stmt = $pdo->prepare("SELECT specialty FROM mechanics WHERE user_id = ?");
            $stmt->execute([$id]);
            $extra = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php'; 
  include 'includes/mobile_bar.php'; ?>

  <div class="content-area">
    <h2 class="form-title"><?= $trans['edit_user'] ?? 'Edit User' ?></h2>

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
        <input type="email" name="email" value="<?= $user['email'] ?>" required>
      </div>

      <div class="form-group">
        <label><?= $trans['id_number'] ?? 'ID Number' ?>:</label>
        <input type="text" name="id_number" value="<?= htmlspecialchars($user['id_number']) ?? '' ?>" required>
      </div>

      <div class="form-group">
        <label><?= $trans['username'] ?? 'Username' ?>:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?? '' ?>" required>
      </div>

      <div class="form-group">
        <label><?= $trans['role'] ?? 'Role' ?>:</label>
        <input type="text" value="<?= ucfirst($role) ?>" disabled>
      </div>

      <?php if ($role === 'customer'): ?>
        <div class="form-group">
          <label><?= $trans['address'] ?? 'Address' ?>:</label>
          <input type="text" name="address" value="<?= htmlspecialchars($extra['address'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label><?= $trans['vat_number'] ?? 'VAT Number' ?>:</label>
          <input type="text" name="vat_number" value="<?= htmlspecialchars($extra['vat_number'] ?? '') ?>">
        </div>
      <?php elseif ($role === 'mechanic'): ?>
        <div class="form-group">
          <label><?= $trans['specialty'] ?? 'Specialty' ?>:</label>
          <input type="text" name="specialty" value="<?= htmlspecialchars($extra['specialty'] ?? '') ?>">
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label>
          <input type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
          <?= $trans['active'] ?? 'Active' ?>
        </label>
      </div>

      <button type="submit" class="form-button"><?= $trans['save_changes'] ?? 'Save Changes' ?></button>
    </form>
  </div>
</div>
</body>
</html>
