<?php
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) { 
    session_start();
    require 'config.php';

    $id = $_GET['id'];

    $selected_appointment = $pdo->prepare("
        SELECT 
            a.appointment_date AS date,
            a.appointment_time AS time,
            CONCAT(c.brand, ' ', c.model) AS car,
            a.reason,
            a.problem_description AS description,
            a.status,
            a.cost,
            u.full_name AS customer
        FROM appointments a
        JOIN cars c ON a.car_id = c.id
        JOIN customers cu ON a.customer_id = cu.user_id
        JOIN users u ON cu.user_id = u.id
        WHERE a.id = ?
        LIMIT 1
    ");
    $selected_appointment->execute([$id]);
    $data = $selected_appointment->fetch(PDO::FETCH_ASSOC);

    $job_stmt = $pdo->prepare("SELECT description, materials, duration, cost FROM job WHERE appointment_id = ?");
    $job_stmt->execute([$id]);
    $data['jobs'] = $job_stmt->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($data ?: []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appointment_id'])) {
  session_start();
  require 'config.php';

  $appointment_id = $_POST['appointment_id'];

  if ($_POST['action'] === 'arrived') {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'IN_PROGRESS' WHERE id = ?");
    $stmt->execute([$appointment_id]);
  } elseif ($_POST['action'] === 'not_arrived') {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'CANCELLED' WHERE id = ?");
    $stmt->execute([$appointment_id]);
  }

}

include 'includes/dash_header.php';

$stmt = $pdo->prepare("
    SELECT a.*, c.serial_number, u.full_name AS customer 
    FROM appointments a 
    JOIN cars c ON a.car_id = c.id 
    JOIN users u ON c.customer_id = u.id 
    WHERE a.mechanic_id = ?
      AND a.appointment_date = CURDATE()
      AND NOW() BETWEEN 
        CONCAT(a.appointment_date, ' ', a.appointment_time) 
        AND ADDTIME(CONCAT(a.appointment_date, ' ', a.appointment_time), '02:00:00')
      AND (a.status = 'CREATED' OR a.status = 'IN_PROGRESS')
    LIMIT 1
");

$stmt->execute([$_SESSION['user_id']]);
$current_appointment = $stmt->fetch(PDO::FETCH_ASSOC);


if ($current_appointment === false) {
  $current_appointment = null;
}

?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php';
  include 'includes/mobile_bar.php';

  require_once 'functions.php';
  updateAppointmentsToCompleted($pdo, $_SESSION['user_id']);
  updateMissedAppointments($pdo);

  if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
      header("Location: login.php");
      exit;
  }

    $mechanic_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT a.*, c.serial_number, u.full_name AS customer
        FROM appointments a
        JOIN cars c ON a.car_id = c.id
        JOIN users u ON c.customer_id = u.id
        WHERE a.mechanic_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 8
    ");
    $stmt->execute([$mechanic_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

  ?>

  <div class="content-area">
    <h2 class="form-title"><?= $trans['assigned_appointments'] ?? 'Assigned Appointments' ?></h2>

    <div class="row-container">
      <div class="column-container">
        <?php if (empty($appointments)) : ?>
          <p><?= $trans['no_appointments'] ?? "You don't have any appointments yet." ?></p>
        <?php else : ?>
          <table class="table">
            <thead>
              <tr>
                <th><?= $trans['date'] ?? 'Date' ?></th>
                <th><?= $trans['time'] ?? 'Time' ?></th>
                <th><?= $trans['client'] ?? 'Client' ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $a) : ?>
                <tr class="row-clickable" data-id="<?= $a['id'] ?>">
                  <td><?= date("d/m/Y", strtotime($a['appointment_date'])) ?></td>
                  <td><?= date("H:i", strtotime($a['appointment_time'])) ?></td>
                  <td><?= htmlspecialchars($a['customer']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

        <?php if ($current_appointment) : ?>
          <div class="current-appointment">
            <h3><?= $trans['current_appointment'] ?? 'Current Appointment' ?></h3>
            <p><strong><?= $trans['date'] ?? 'Date' ?>:</strong> <?= $current_appointment['appointment_date'] ?> | <?= $current_appointment['appointment_time'] ?></p>
            <p><strong><?= $trans['car'] ?? 'Car' ?>:</strong> <?= $current_appointment['serial_number'] ?></p>
            <p><strong><?= $trans['customer'] ?? 'Customer' ?>:</strong> <?= $current_appointment['customer'] ?></p>

            <?php if ($current_appointment['status'] === 'CREATED') : ?>
              <div class="row-container">
                <form method="POST">
                  <input type="hidden" name="appointment_id" value="<?= $current_appointment['id'] ?>">
                  <input type="hidden" name="action" value="arrived">
                  <button type="submit" class="arrived-button"><?= $trans['arrived'] ?? 'Arrived' ?></button>
                </form>
                <form method="POST">
                  <input type="hidden" name="appointment_id" value="<?= $current_appointment['id'] ?>">
                  <input type="hidden" name="action" value="not_arrived">
                  <button type="submit" class="not-arrived-button"><?= $trans['not_arrived'] ?? 'Not Arrived' ?></button>
                </form>
              </div>
            <?php elseif ($current_appointment['status'] === 'IN_PROGRESS') : ?>
              <a href="manage_jobs.php?id=<?= $current_appointment['id'] ?>" class="button"><?= $trans['manage_jobs'] ?? 'Manage Jobs' ?></a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="details-panel" id="detailsPanel">
        <h3><?= $trans['details'] ?? 'Details' ?></h3>
        <p><?= $trans['select_appointment'] ?? 'Select an appointment to view details' ?></p>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.row-clickable').forEach(row => {
  row.addEventListener('click', () => {
    document.querySelectorAll('.row-clickable').forEach(r => r.classList.remove('active'));
    row.classList.add('active');

    const appointmentId = row.dataset.id;

    fetch(`assigned_appointments.php?action=details&id=${appointmentId}`)
      .then(res => res.json())
      .then(data => {
        const details = document.getElementById('detailsPanel');
        
        if (!data || Object.keys(data).length === 0) {
          details.innerHTML = "<h3><?= $trans['details'] ?? 'Details' ?></h3><p><?= $trans['no_data_found'] ?? 'No data found for this appointment.' ?></p>";
          return;
        }

        let html = `
          <h3><?= $trans['details'] ?? 'Details' ?></h3>
          <p><strong><?= $trans['date'] ?? 'Date' ?>:</strong> ${data.date}</p>
          <p><strong><?= $trans['time'] ?? 'Time' ?>:</strong> ${data.time}</p>
          <p><strong><?= $trans['car'] ?? 'Car' ?>:</strong> ${data.car}</p>
          <p><strong><?= $trans['reason'] ?? 'Reason' ?>:</strong> ${data.reason}</p>
          <p><strong><?= $trans['description'] ?? 'Description' ?>:</strong> ${data.description || '—'}</p>
          <p><strong><?= $trans['status'] ?? 'Status' ?>:</strong> ${data.status}</p>
          <p><strong><?= $trans['customer'] ?? 'Customer' ?>:</strong> ${data.customer}</p>
          <p><strong><?= $trans['cost'] ?? 'Cost' ?>:</strong> ${data.cost ? parseFloat(data.cost).toFixed(2) + ' €' : '—'}</p>
        `;

        if (data.jobs && data.jobs.length > 0) {
          html += `
          <h4><?= $trans['jobs'] ?? 'Jobs' ?></h4>
          <table class="table">
            <thead>
              <tr>
                <th><?= $trans['description'] ?? 'Description' ?></th>
                <th><?= $trans['materials'] ?? 'Materials' ?></th>
                <th><?= $trans['duration'] ?? 'Duration' ?></th>
                <th><?= $trans['cost'] ?? 'Cost(€)' ?></th>
              </tr>
            </thead>
            <tbody>
          `;

          data.jobs.forEach(j => {
            html += `
            <tr>
              <td>${j.description}</td>
              <td>${j.materials}</td>
              <td>${j.duration}</td>
              <td>${parseFloat(j.cost).toFixed(2)}€</td>
            </tr>`;
          });

          html += `</tbody></table>`;
        } else {
          html += `<p><?= $trans['no_jobs'] ?? 'No jobs added yet.' ?></p>`;
        }

        details.innerHTML = html;
      });
  });
});
</script>
</body>
</html>