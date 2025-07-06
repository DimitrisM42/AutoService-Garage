<?php
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
  session_start();
  require 'config.php';
  $id = $_GET['id'];
  
  $stmt = $pdo->prepare("
    SELECT 
      a.id,
      a.appointment_date AS date,
      a.appointment_time AS time, 
      CONCAT(c.brand, ' ', c.model) AS car,
      a.reason,
      a.problem_description AS description,
      a.status,
      a.cost,
      u.full_name AS customer,
      m.full_name AS mechanic
    FROM appointments a
    JOIN cars c ON a.car_id = c.id
    JOIN users u ON a.customer_id = u.id
    LEFT JOIN users m ON a.mechanic_id = m.id
    WHERE a.id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  $job_stmt = $pdo->prepare("SELECT description, materials, duration, cost FROM job WHERE appointment_id = ?");
  $job_stmt->execute([$id]);
  $data['jobs'] = $job_stmt->fetchAll();

  header('Content-Type: application/json');
  echo json_encode($data ?: []);
  exit;
}

include 'includes/dash_header.php';

if ($_SESSION['role'] !== 'secretary') {
  header("Location: dashboard.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['appointment_id'])) {
  $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
  $stmt->execute([$_POST['status'], $_POST['appointment_id']]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment_id'])) {
  $id = $_POST['cancel_appointment_id'];
  $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
  $stmt->execute([$id]);
  $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($appointment && $appointment['status'] === 'CREATED') {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'CANCELLED' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_appointments.php");
    exit;
  } else {
    $error = $trans['cannot_cancel'] ?? "You cannot cancel this appointment.";
  }
}
$stmt = $pdo->query("
  SELECT a.*, u.full_name AS customer_name, m.full_name AS mechanic_name, c.model, c.brand
  FROM appointments a
  JOIN users u ON a.customer_id = u.id
  LEFT JOIN users m ON a.mechanic_id = m.id
  JOIN cars c ON a.car_id = c.id
");

$appointments = $stmt->fetchAll();


// Pagination
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search
$where = [];
$params = [];

if (!empty($_GET['search_term'])) {
    $where[] = '(u.full_name LIKE :search_term OR cu.vat_number LIKE :search_term)';
    $params['search_term'] = '%' . $_GET['search_term'] . '%';
}

if (!empty($_GET['date_from'])) {
    $where[] = 'a.appointment_date >= :date_from';
    $params['date_from'] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where[] = 'a.appointment_date <= :date_to';
    $params['date_to'] = $_GET['date_to'];
}

if (!empty($_GET['search_status'])) {
    $where[] = 'a.status = :search_status';
    $params['search_status'] = $_GET['search_status'];
}

$sql = "
  SELECT a.*, u.full_name AS customer_name, m.full_name AS mechanic_name, c.model, c.brand
  FROM appointments a
  JOIN cars c ON a.car_id = c.id
  JOIN customers cu ON a.customer_id = cu.user_id
  JOIN users u ON cu.user_id = u.id
  LEFT JOIN users m ON a.mechanic_id = m.id
";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $start, $limit";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$count_sql = "
  SELECT COUNT(*) 
  FROM appointments a
  JOIN cars c ON a.car_id = c.id
  JOIN customers cu ON a.customer_id = cu.user_id
  JOIN users u ON cu.user_id = u.id
  LEFT JOIN users m ON a.mechanic_id = m.id
";

if (!empty($where)) {
    $count_sql .= ' WHERE ' . implode(' AND ', $where);
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php'; 
  include 'includes/mobile_bar.php';

  require 'config.php';
  require_once 'functions.php';
  updateMissedAppointments($pdo);

  // update για τους mech
  $mechanic_stmt = $pdo->query("SELECT user_id FROM mechanics");
  $mechanic_ids = $mechanic_stmt->fetchAll(PDO::FETCH_COLUMN);

  foreach ($mechanic_ids as $mech_id) {
    updateAppointmentsToCompleted($pdo, $mech_id);
  }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appointment_id'])) {

  $appointment_id = $_POST['appointment_id'];

  if ($_POST['action'] === 'arrived') {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'IN_PROGRESS' WHERE id = ?");
    $stmt->execute([$appointment_id]);
  } elseif ($_POST['action'] === 'not_arrived') {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'CANCELLED' WHERE id = ?");
    $stmt->execute([$appointment_id]);
  }

}
  
  ?>
  <div class="content-area">
    <h2><?= $trans['appointments'] ?? 'Appointments' ?></h2>

    <?php if (isset($error)){
      echo '<p class="form-message error"> ' . $error . ' </p>';
    } ?>

    <form method="GET" class="search-form">
      <div style="display: flex; flex-wrap: wrap; gap: 10px;">
        <div class="search-container">
          <label><?= $trans['search'] ?? 'Search' ?>:</label>
          <input type="text" name="search_term" placeholder="<?= $trans['search_placeholder_appointments'] ?? 'Search by Name or VAT' ?>" value="<?= htmlspecialchars($_GET['search_term'] ?? '') ?>">
        </div>
        <div class="search-container">
          <label><?= $trans['date_from'] ?? 'Date From' ?>:</label>
          <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="search-container">
          <label><?= $trans['date_to'] ?? 'Date To' ?>:</label>
          <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>
        <div class="search-container">
          <select name="search_status">
            <option value=""><?= $trans['status'] ?? 'Status' ?></option>
            <option value="CREATED" <?= ($_GET['search_status'] ?? '') === 'CREATED' ? 'selected' : '' ?>><?= $trans['created'] ?? 'CREATED' ?></option>
            <option value="IN_PROGRESS" <?= ($_GET['search_status'] ?? '') === 'IN_PROGRESS' ? 'selected' : '' ?>><?= $trans['in_progress'] ?? 'IN PROGRESS' ?></option>
            <option value="COMPLETED" <?= ($_GET['search_status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>><?= $trans['completed'] ?? 'COMPLETED' ?></option>
            <option value="CANCELLED" <?= ($_GET['search_status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>><?= $trans['cancelled'] ?? 'CANCELLED' ?></option>
          </select>
        </div>
        <button type="submit" style="margin-top: 14px;"><?= $trans['search'] ?? 'Search' ?></button>
        <?php if (!empty($_GET)) { ?>
            <a href="manage_appointments.php" style="margin-left:10px; margin-top: 25px;"><?= $trans['reset'] ?? 'Reset'?></a>
          <?php } ?>
      </div>
    </form>
    
    <div class="row-container">
      <div class="column-container">
        <div class="limit-select">
          <label for="limit"><?= $trans['results_per_page'] ?? 'Results per page:'?></label>
          <select id="limit-select">
            <?php foreach ([2, 5, 10, 20] as $opt): ?>
              <option value="<?= $opt ?>" <?= ($limit == $opt ? 'selected' : '') ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th><?= $trans['date'] ?? 'Date' ?></th>
              <th><?= $trans['customer'] ?? 'Customer' ?></th>
              <th><?= $trans['actions'] ?? 'Actions' ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($appointments as $a): ?>
              <tr class="row-clickable" data-id="<?= $a['id'] ?>">
                <td><?= date("d/m/Y", strtotime($a['appointment_date'])) ?></td>
                <td><?= htmlspecialchars($a['customer_name']) ?></td>
                <td class="actions">
                  <a class="edit-button" href="edit_appointment.php?id=<?= $a['id'] ?>"><?= $trans['edit'] ?? 'Edit' ?></a>
                  <form method="POST"   onsubmit="return confirm('<?= $trans['cancel_confirmation'] ?? 'Are you sure you want to cancel this appointment?' ?>');">
                    <input type="hidden" name="cancel_appointment_id" value="<?= $a['id'] ?>">
                    <button type="submit" class="cancel-button"><?= $trans['cancel'] ?? 'Cancel' ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" title="First Page"><i class="fa-solid fa-angles-left"></i></a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" title="Previous Page"><i class="fa-solid fa-angle-left"></i></a>
          <?php endif; ?>

          <span style="margin: 0 10px;"><?= $trans['page'] ?? 'Page'?> <?= $page ?></span>

          <?php if ($page < $total_pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" title="Next Page"><i class="fa-solid fa-angle-right"></i></a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" title="Last Page"><i class="fa-solid fa-angles-right"></i></a>
          <?php endif; ?>
        </div>
        
        <a href="add_appointment.php" class="button"><?= $trans['book_new_appointment'] ?? 'Book New Appointment' ?></a>
      </div>

      <div class="details-panel" id="detailsPanel">
        <h3><?= $trans['details'] ?? 'Details' ?></h3>
        <p><?= $trans['select_appointment_details'] ?? 'Select an appointment to view details' ?></p>
      </div>
    </div>
  </div>
</div>

<script>

  function DetailsOnClick() {
    document.querySelectorAll('.row-clickable').forEach(row => {
      row.addEventListener('click', () => {
        document.querySelectorAll('.row-clickable').forEach(r => r.classList.remove('active'));
        row.classList.add('active');

        const appointmentId = row.dataset.id;

        fetch(`manage_appointments.php?action=details&id=${appointmentId}`)
          .then(response => response.json())
          .then(data => {
            const details = document.getElementById('detailsPanel');

            if (!data || Object.keys(data).length === 0) {
              panel.innerHTML = "<h3><?= $trans['details'] ?? 'Details' ?></h3><p><?= $trans['no_data_found'] ?? 'No data found for this appointment.' ?></p>";
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
              <p><strong><?= $trans['mechanic'] ?? 'Mechanic' ?>:</strong> ${data.mechanic || '—'}</p>
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
                      <th><?= $trans['cost'] ?? 'Cost (€)' ?></th>
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
                    <td>${parseFloat(j.cost).toFixed(2)} €</td>
                  </tr>
                `;
              });
              html += `</tbody></table>`;

            } else {
              html += `<p><?= $trans['no_jobs'] ?? 'No jobs added yet.' ?></p>`;
            }

            const currentTime = new Date();
            const today = currentTime.toISOString().split('T')[0];
            
            const [hh, mm] = data.time.split(':');
            const appointmentS = new Date(data.date);
            appointmentS.setHours(parseInt(hh), parseInt(mm), 0, 0);

            const appointmentE = new Date(appointmentS.getTime() + 2 * 60 * 60 * 1000);

            if(data.status === 'CREATED' && data.date === today && currentTime >= appointmentS && currentTime <= appointmentE) {
                html += 
                `<div class="row-container">
                  <form method="POST">
                    <input type="hidden" name="appointment_id" value="${data.id}">
                    <input type="hidden" name="action" value="arrived">
                    <button type="submit" class="arrived-button"><?= $trans['arrived'] ?? 'Arrived' ?></button>
                  </form>
                  <form method="POST">
                    <input type="hidden" name="appointment_id" value="${data.id}">
                    <input type="hidden" name="action" value="not_arrived">
                    <button type="submit" class="not-arrived-button"><?= $trans['not_arrived'] ?? 'Not Arrived' ?></button>
                  </form>
                </div>`;
              }
              else if (data.status === 'IN_PROGRESS') {
                html += `<br><a href="manage_jobs.php?id=${data.id}"class="button"><?= $trans['manage_jobs'] ?? 'Manage Jobs' ?></a>`;
              }

              

            details.innerHTML = html;
          });
      });
    });
  }

  DetailsOnClick();

  const limitSelect = document.getElementById('limit-select');
  if (limitSelect) {
    limitSelect.addEventListener('change', function () {
      const newLimit = this.value;

      fetch(`${window.location.pathname}?limit=${newLimit}`)
        .then(res => res.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');

          const newTbody = doc.querySelector('.table tbody');
          const currentTbody = document.querySelector('.table tbody');
          if (newTbody && currentTbody) {
            currentTbody.innerHTML = newTbody.innerHTML;
          }

          const newPagination = doc.querySelector('.pagination');
          const currentPagination = document.querySelector('.pagination');
          if (newPagination && currentPagination) {
            currentPagination.innerHTML = newPagination.innerHTML;
          }

          DetailsOnClick();
        });
    });
  }


</script>
</body>
</html>
