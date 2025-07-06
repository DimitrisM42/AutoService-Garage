<?php 
session_start();
require 'config.php';


if (isset($_GET['fetch_cars'])) {

  if ($_SESSION['role'] === 'customer') {
      $customer_id = $_SESSION['user_id'];
  } elseif ($_SESSION['role'] === 'secretary' && isset($_GET['customer_id'])) {
      $customer_id = (int)$_GET['customer_id'];
  } else {
      exit;
  }

  $stmt = $pdo->prepare("SELECT id, brand, model FROM cars WHERE customer_id = ?");
  $stmt->execute([$customer_id]);
  $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

  header('Content-Type: application/json');
  echo json_encode($cars);
  exit;
}


include 'includes/dash_header.php';


if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role !== 'customer' && $role !== 'secretary') {
  header("Location: dashboard.php");
  exit();
}

if ($role === 'secretary') {
  $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'customer' AND is_active = 1");
  $customers = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare("SELECT id, brand, model FROM cars WHERE customer_id = ?");
  $stmt->execute([$user_id]);
  $cars = $stmt->fetchAll();
}

$success = false;
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if ($role === 'secretary') {
      $customer_id = $_POST['customer_id'];
  } else {
      $customer_id = $user_id;
  }

  $car_id = $_POST['car_id'];
  $date = $_POST['appointment_date'];
  $time = $_POST['appointment_time'];
  $reason = $_POST['reason'];
  $problem = $_POST['problem_description'];

  $current_date = date('Y-m-d');

  if (empty($car_id)) {
    $errors[] = $trans['must_select_car'] ?? 'You must select a car to book an appointment.';
  }

  if (empty($date) || empty($time) || empty($reason)) {
    $errors[] = $trans['all_fields_required'] ?? 'All fields are required except problem description.';
  }

  if ($date < $current_date) {
    $errors[] = $trans['no_past_date'] ?? 'You cannot select a past date for the appointment.';
  }

  if ($date == $current_date) {
    $current_time = date('H:i');
    if ($time <= $current_time) {
      $errors[] = $trans['no_past_time'] ?? "You cannot select a past time for today.";
    }
  }


  $time_end = date('H:i', strtotime($time) + 7200);

  $stmt = $pdo->prepare("
      SELECT user_id FROM mechanics 
      WHERE user_id NOT IN (
          SELECT mechanic_id FROM appointments 
          WHERE (status = 'CREATED' OR status = 'IN_PROGRESS') AND appointment_date = ? AND (
              (appointment_time BETWEEN ? AND ?)
              OR (ADDTIME(appointment_time, '01:59:00') BETWEEN ? AND ?)
          )
      )
  ");
  $stmt->execute([$date, $time, $time_end, $time, $time_end]);
  $mechanics = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (count($mechanics) > 0) {
    $selected_mechanic = $mechanics[array_rand($mechanics)];
  } else {
    $errors[] = $trans['no_mechanics'] ?? 'No available mechanics at the selected time. Please choose another time.';
  }

  if (empty($errors)) {
    $stmt = $pdo->prepare("INSERT INTO appointments (appointment_date, appointment_time, reason, problem_description, car_id, customer_id, mechanic_id)
      VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$date, $time, $reason, $problem, $car_id, $customer_id, $selected_mechanic]);
    $success = true;
  }
}
?>

<div class="layout-container">
<?php include 'includes/dash_sidebar.php'; include 'includes/mobile_bar.php'; ?>

<div class="content-area">
<h2 class="form-title"><?=$trans['add_appointment'] ?? 'Add Appointment'?></h2>

<?php 
if ($role === 'customer' && empty($cars)) {
    echo '<div class="form-message error">' . ($trans['need_to_add_cars'] ?? 'You need to add a car before booking an appointment.') . 
         ' <a href="add_car.php">' . ($trans['add_car'] ?? 'Add Car') . '</a></div>';
} elseif ($role === 'secretary' && empty($customers)) {
    echo '<div class="form-message error">' . ($trans['no_customers'] ?? 'No customers registered yet.') . 
         ' <a href="manage_users.php">' . ($trans['add_customer'] ?? 'Add Customer') . '</a></div>';

?>

    
<?php }else{ ?>

  <?php if (!empty($errors)){ ?>
      <div class="form-message error">
        <?= implode('<br>', $errors) ?>
      </div>
    <?php } elseif ($success){ ?>
      <div class="form-message success">
        <?= $trans['appointment_success'] ?? 'Appointment added successfully!' ?>
        <a href="my_appointments.php"><?= $trans['view_appointments'] ?? 'View Appointments' ?></a>
      </div>

    <?php } ?>

  <form method="POST" action="" class="generic-form">

  <?php if ($role === 'secretary'){ ?>
    <div class="form-group">
      <label><?= $trans['select_customer'] ?? 'Select Customer:' ?></label>
      <select name="customer_id" id="customerSelect" required>
        <option value=""><?= $trans['select_customer_placeholder'] ?? '-- Select Customer --' ?></option>
        <?php foreach ($customers as $customer): ?>
          <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label><?= $trans['select_car'] ?? 'Select Car:' ?></label>
      <select name="car_id" id="carSelect" required>
        <option value=""><?= $trans['select_car_placeholder'] ?? '-- Select Car --' ?></option>
      </select>
    </div>
  <?php }else{ ?>
    <div class="form-group">
      <label><?= $trans['select_car'] ?? 'Select Car:' ?></label>
      <select name="car_id" required>
        <option value=""><?= $trans['select_car_placeholder'] ?? '-- Select Car --' ?></option>
        <?php foreach ($cars as $car): ?>
          <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php } ?>

  <div class="form-group">
    <label><?= $trans['date'] ?? 'Date:' ?></label>
    <input type="date" name="appointment_date" required>
  </div>

  <div class="form-group">
    <label><?= $trans['time'] ?? 'Time:' ?></label>
    <select name="appointment_time" required>
      <?php for ($hour = 8; $hour <= 14; $hour++){ ?>
        <option value="<?= sprintf('%02d:00', $hour) ?>"><?= sprintf('%02d:00', $hour) ?></option>
      <?php } ?>
    </select>
  </div>

  <div class="form-group">
    <label><?= $trans['reason'] ?? 'Reason:' ?></label>
    <select name="reason" id="reasonSelect" required>
      <option value="repair"><?= $trans['repair'] ?? 'Repair' ?></option>
      <option value="service"><?= $trans['service'] ?? 'Service' ?></option>
    </select>
  </div>

  <div class="form-group" id="problemDescription">
    <label><?= $trans['problem_description'] ?? 'Problem Description (optional):' ?></label>
    <textarea name="problem_description" rows="3" placeholder="<?= $trans['describe_issue'] ?? 'Describe the issue...' ?>"></textarea>
  </div>

  <button type="submit" class="form-button"><?= $trans['add_appointment'] ?? 'Add Appointment' ?></button>
</form>

<?php } ?>

</div>
</div>

<script>
const trans = <?= json_encode([
  'loading_cars' => $trans['loading_cars'] ?? '-- Loading cars... --',
  'select_car' => $trans['select_car_placeholder'] ?? '-- Select Car --',
  'error_loading_cars' => $trans['error_loading_cars'] ?? '-- Error loading cars --',
]) ?>;

document.getElementById('customerSelect')?.addEventListener('change', function() {
  const customerId = this.value;
  const carSelect = document.getElementById('carSelect');
  
  carSelect.innerHTML = `<option value="">${trans.loading_cars}</option>`;

  if (customerId) {
    fetch('add_appointment.php?fetch_cars=1&customer_id=' + customerId)
      .then(response => response.json())
      .then(cars => {
        carSelect.innerHTML = `<option value="">${trans.select_car}</option>`;
        cars.forEach(car => {
          const option = document.createElement('option');
          option.value = car.id;
          option.textContent = car.brand + ' ' + car.model;
          carSelect.appendChild(option);
        });
      })
      .catch(err => {
        console.error('Error loading cars:', err);
        carSelect.innerHTML = `<option value="">${trans.error_loading_cars}</option>`;
      });
  } else {
    carSelect.innerHTML = `<option value="">${trans.select_car}</option>`;
  }
});

const reasonSelect = document.getElementById('reasonSelect');
const problemDescription = document.getElementById('problemDescription');

function toggleProblemDescription() {
  if (reasonSelect.value === 'repair') {
    problemDescription.style.display = 'flex';
  } else {
    problemDescription.style.display = 'none';
  }
}
toggleProblemDescription();
reasonSelect.addEventListener('change', toggleProblemDescription);
</script>


</body>
</html>
