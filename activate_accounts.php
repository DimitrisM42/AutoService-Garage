<?php
include 'includes/dash_header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'secretary') {
    header("Location: dashboard.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
    $stmt->execute([$_POST['user_id']]);
}


$stmt = $pdo->query("SELECT id, full_name, username, email, role FROM users WHERE is_active = 0 AND role IN ('customer', 'mechanic')");
$inactive_users = $stmt->fetchAll();


?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php';
  include 'includes/mobile_bar.php'; ?>
  <div class="content-area">

    <h2><?= $trans['inactive_accounts'] ?? 'Inactive Accounts' ?></h2>

    <?php if (count($inactive_users) === 0){ ?>
      <p><?= $trans['all_accounts_active'] ?? 'All accounts are active.' ?></p>
    <?php } else { ?>
      <table class="table">
        <thead>
          <tr>
            <th><?= $trans['full_name'] ?? 'Full Name' ?></th>
            <th><?= $trans['username'] ?? 'Username' ?></th>
            <th><?= $trans['role'] ?? 'Role' ?></th>
            <th><?= $trans['action'] ?? 'Action' ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($inactive_users as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['full_name']) ?></td>
              <td><?= htmlspecialchars($user['username']) ?></td>
              <td><?= htmlspecialchars($user['role']) ?></td>
              <td>
                <form method="POST" action="activate_accounts.php">
                  <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                  <button type="submit" class="activate-button"><?= $trans['activate'] ?? 'Activate' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php } ?>

  </div>
</div>
</body>
</html>
