<?php
include 'includes/dash_header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic' && $_SESSION['role'] !== 'secretary') {
    header("Location: dashboard.php");
    exit();
}

$mechanic_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;
$edit_job_id = $_GET['edit'] ?? null;
$success = "";
$error = "";

$stmt = $pdo->prepare("
  SELECT a.*, c.serial_number, c.brand, c.model, u.full_name AS customer_name
  FROM appointments a
  JOIN cars c ON a.car_id = c.id
  JOIN users u ON a.customer_id = u.id
  WHERE a.id = ? AND a.mechanic_id = ?
");
$stmt->execute([$appointment_id, $mechanic_id]);
$appointment = $stmt->fetch();


$edit_job = null;
if ($edit_job_id) {
    $stmt = $pdo->prepare("SELECT * FROM job WHERE id = ? AND appointment_id = ?");
    $stmt->execute([$edit_job_id, $appointment_id]);
    $edit_job = $stmt->fetch();
    updateAppointmentCost($pdo, $appointment_id);
    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_job_id'])) {
        $stmt = $pdo->prepare("DELETE FROM job WHERE id = ? AND appointment_id = ?");
        $stmt->execute([$_POST['delete_job_id'], $appointment_id]);
        $success = $trans['job_deleted'] ?? 'Job deleted';

        updateAppointmentCost($pdo, $appointment_id);
    } else {
        $description = $_POST['description'];
        $materials = $_POST['materials'];
        $hours = (int)$_POST['duration_hours'];
        $minutes = (int)$_POST['duration_minutes'];
        $duration = sprintf('%02d:%02d', $hours, $minutes);
        $cost = $_POST['cost'];

        if ($cost < 0 || $hours < 0 || $minutes < 0) {
            $error = $trans['invalid_input'] ?? 'invalid input';
        }

        if (!empty($_POST['job_id'])) {
            $stmt = $pdo->prepare("UPDATE job SET description=?, materials=?, duration=?, cost=? WHERE id=? AND appointment_id=?");
            $stmt->execute([$description, $materials, $duration, $cost, $_POST['job_id'], $appointment_id]);
            $success = $trans['job_updated'] ?? 'Job updated';
        } else {
            $stmt = $pdo->prepare("INSERT INTO job (appointment_id, description, materials, duration, cost) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$appointment_id, $description, $materials, $duration, $cost]);
            $success = $trans['job_added'] ?? 'Job Added';
        }

        updateAppointmentCost($pdo, $appointment_id);
    }

    header("Location: manage_jobs.php?id=$appointment_id");
    exit();
}


$stmt = $pdo->prepare("SELECT * FROM job WHERE appointment_id = ?");
$stmt->execute([$appointment_id]);
$jobs = $stmt->fetchAll();

?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php'; 
  include 'includes/mobile_bar.php'; ?>

  <div class="content-area">
    <h2><?= $trans['manage_jobs'] ?? 'Manage Jobs' ?></h2>

    <div class="row-container">
      <form method="POST" class="generic-form" style="max-width: 600px;">
        <h3><?= $edit_job ? ($trans['edit_job'] ?? 'Edit Job') : ($trans['add_job'] ?? 'Add Job') ?></h3>
        <input type="hidden" name="job_id" value="<?= $edit_job['id'] ?? '' ?>">

        <div class="form-group">
          <label><?= $trans['description'] ?? 'Description' ?>:</label>
          <textarea name="description" required><?= htmlspecialchars($edit_job['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label><?= $trans['materials'] ?? 'Materials' ?>:</label>
          <textarea name="materials" required><?= htmlspecialchars($edit_job['materials'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label><?= $trans['duration'] ?? 'Duration' ?>:</label>
          <div style="display: flex; gap: 10px;">
            <input type="number" name="duration_hours" min="0" placeholder="<?= $trans['hours'] ?? 'Hours' ?>" required style="width:100px;"
              value="<?= isset($edit_job['duration']) ? explode(':', $edit_job['duration'])[0] : '' ?>">
            <input type="number" name="duration_minutes" min="0" max="59" placeholder="<?= $trans['minutes'] ?? 'Minutes' ?>" required style="width:100px;"
              value="<?= isset($edit_job['duration']) ? explode(':', $edit_job['duration'])[1] : '' ?>">
          </div>
        </div>

        <div class="form-group">
          <label><?= $trans['cost'] ?? 'Cost(€)' ?>:</label>
          <input type="number" step="0.01" name="cost" required value="<?= $edit_job['cost'] ?? '' ?>">
        </div>

        <button type="submit" class="form-button"><?= $edit_job ? ($trans['update'] ?? 'Update') : ($trans['add'] ?? 'Add') ?> <?= $trans['job'] ?? 'Job' ?></button>
        <?php if ($edit_job): ?>
          <a href="manage_jobs.php?id=<?= $appointment_id ?>" class="form-button" style="background-color: grey;"><?= $trans['cancel'] ?? 'Cancel' ?></a>
        <?php endif; ?>
      </form>

      <div class="column-container">
        <h3 style="margin-top:40px;" id="jobs-title"><?= $trans['jobs_added'] ?? 'Jobs Added' ?></h3>

        <?php if (empty($jobs)): ?>
          <p><?= $trans['no_jobs'] ?? 'No jobs recorded yet.' ?></p>
        <?php else: ?>
          <table class="table" id="jobs-table" style="max-width: 600px;">
            <thead>
              <tr>
                <th><?= $trans['description'] ?? 'Description' ?></th>
                <th><?= $trans['materials'] ?? 'Materials' ?></th>
                <th><?= $trans['duration'] ?? 'Duration' ?></th>
                <th><?= $trans['cost'] ?? 'Cost(€)' ?></th>
                <th><?= $trans['actions'] ?? 'Actions' ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($jobs as $j): ?>
                <tr>
                  <td><?= nl2br(htmlspecialchars($j['description'])) ?></td>
                  <td><?= nl2br(htmlspecialchars($j['materials'])) ?></td>
                  <td><?= $j['duration'] ?></td>
                  <td><?= number_format($j['cost'],2)?>€</td>
                  <td class="actions">
                    <a class="edit-button" href="manage_jobs.php?id=<?= $appointment_id ?>&edit=<?= $j['id'] ?>"><?= $trans['edit'] ?? 'Edit' ?></a>
                    <form method="POST" class="inline" onsubmit="return confirm('<?= $trans['confirm_delete_job'] ?? 'Delete this job?' ?>');" style="display:inline;">
                      <input type="hidden" name="delete_job_id" value="<?= $j['id'] ?>">
                      <button type="submit" class="delete-button"><?= $trans['delete'] ?? 'Delete' ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
