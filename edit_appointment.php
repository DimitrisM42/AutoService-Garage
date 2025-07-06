<?php 
include 'includes/dash_header.php'; 
 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = "";
$error = "";


if (!isset($_GET['id']) && $_SESSION['role'] == 'customer') {
    header("Location: my_appointments.php");
    exit();
}

if (!isset($_GET['id']) && $_SESSION['role'] == 'secretary') {
  header("Location: manage_appointments.php");
  exit();
}

$appointment_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT customer_id FROM appointments WHERE id = ?");
$stmt->execute([$appointment_id]);
$c_id = $stmt->fetch();

if ($_SESSION['role'] === 'customer') {
  $customer_id = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'secretary' && isset($c_id)) {
  $customer_id = $c_id['customer_id'];
} else {
  exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = 'CREATED';
    $current_date = date('Y-m-d');

    if ($appointment_date < $current_date) {
      $error = $trans['no_past_date'] ?? 'You cannot select a past date for the appointment.';
    }

    if ($appointment_date == $current_date) {
      $current_time = date('H:i');
      
      if ($appointment_time <= $current_time) {
        $error = $trans['no_past_time'] ?? "You cannot select a past time for today.";
      }
    }

    if(empty($error)) {
      $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = ? WHERE id = ? AND customer_id = ? AND status = 'CREATED' ");
      $stmt->execute([$appointment_date, $appointment_time, $status, $appointment_id, $customer_id]);

      updateAppointmentCost($pdo, $appointment_id);

      $success = $trans['appointment_updated'] ?? 'Appointment updated successfully!';
    }
    
    
    if ($role == 'secretary') {
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$appointment_id]);
        $a = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND customer_id = ?");
        $stmt->execute([$appointment_id, $user_id]);
        $a = $stmt->fetch();
    }

}

if ($role == 'secretary') {
  $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
  $stmt->execute([$appointment_id]);
  $a = $stmt->fetch();
} else {
  $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND customer_id = ?");
  $stmt->execute([$appointment_id, $user_id]);
  $a = $stmt->fetch();
}



if (!$a) {
    $error = $trans['appointment_not_found'] ?? 'Appointment not found';
} 
?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php'; 
  include 'includes/mobile_bar.php'; ?>
  <div class="content-area">

  <h2 class="form-title"><?= $trans['edit_appointment'] ?? 'Edit Appointment' ?></h2>

  <?php 
    $cur_date = date('Y-m-d');
    $cur_time = date('H:i');
  
    if ($a['status'] !== 'CREATED' || $a['appointment_date'] < $cur_date || ($a['appointment_date'] == $cur_date && $a['appointment_time'] < $cur_time)) {
      $error = $trans['not_allowed_edit'] ?? "You can not edit this appointment.";
      echo '<p class="form-message error">' . $error . '</p>';
    } else { ?>

      <?php if ($error){ ?>
        <p class="form-message error"><?= $error ?></p>
      <?php }elseif ($success){ ?>
        <p class="form-message success"><?= $success ?></p>
      <?php }?>

      <?php if ($a){ ?>
        <form method="POST" action="" class="generic-form">

          <div class="form-group">
            <label><?= $trans['date'] ?? 'Date:' ?></label>
            <input type="date" name="appointment_date" value="<?= htmlspecialchars($a['appointment_date']) ?>" required>
          </div>

          <div class="form-group">
            <label><?= $trans['time'] ?? 'Time:' ?></label>
            <select name="appointment_time" required>
              <?php
              for ($hour = 8; $hour <= 14; $hour++) {
                $time_slot = sprintf('%02d:00', $hour);
                $selected = (substr($a['appointment_time'], 0, 5) === $time_slot) ? 'selected' : '';
                echo "<option value=\"$time_slot\" $selected>$time_slot</option>";
              }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label><?= $trans['status'] ?? 'Status:' ?></label>
            <?php if ($role == 'secretary') { ?>
              <select name="status" required>
                <option value="CREATED" <?= ($a['status'] === 'CREATED') ? 'selected' : '' ?>>CREATED</option>
                <option value="IN_PROGRESS" <?= ($a['status'] === 'IN_PROGRESS') ? 'selected' : '' ?>>IN PROGRESS</option>
                <option value="COMPLETED" <?= ($a['status'] === 'COMPLETED') ? 'selected' : '' ?>>COMPLETED</option>
              </select>
            <?php } else { ?>
              <input type="text" name="status" value="<?= htmlspecialchars($a['status']) ?>" disabled>
            <?php } ?>
          </div>

          <button type="submit" class="form-button"><?= $trans['save_changes'] ?? 'Save Changes' ?></button>
        </form>
      <?php } ?>
  </div>
</div>
<?php } ?>
</body>
</html>
