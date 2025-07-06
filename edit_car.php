<?php 
include 'includes/dash_header.php'; 


if ($_SESSION['role'] !== 'customer' && $_SESSION['role'] !== 'secretary') {
    header("Location: dashboard.php");
    exit();
  }

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";


if (!isset($_GET['id'])) {
    header("Location: my_cars.php");
    exit();
}

$car_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT customer_id FROM cars WHERE id = ?");
$stmt->execute([$car_id]);
$c_id = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SESSION['role'] === 'customer') {
  $customer_id = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'secretary' && isset($c_id)) {
  $customer_id = $c_id['customer_id'];
} else {
  exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $serial = trim($_POST['serial_number']);
  $brand = trim($_POST['brand']);
  $model = trim($_POST['model']);
  $type = $_POST['type'];
  $engine = $_POST['engine_type'];
  $doors = (int)$_POST['door_count'];
  $wheels = (int)$_POST['wheel_count'];
  $prod_date = $_POST['production_date'];
  $year = (int)$_POST['acquisition_year'];

  $errors = [];

  if (empty($serial)) {
    $errors[] = $trans['error_serial_empty'] ?? "Serial number cannot be empty.";
  }

  if (empty($brand)) {
    $errors[] = $trans['error_brand_empty'] ?? "Brand cannot be empty.";
  }

  if (empty($model)) {
    $errors[] = $trans['error_model_empty'] ?? "Model cannot be empty.";
  }

  if ($doors < 2 || $doors > 6) {
    $errors[] = $trans['error_doors_range'] ?? "Doors must be between 2 and 6.";
  }

  if ($wheels < 2 || $wheels > 18) {
    $errors[] = $trans['error_wheels_range'] ?? "Wheels must be between 2 and 18.";
  }

  if (empty($serial) || empty($brand) || empty($model) || empty($type) || empty($engine) || empty($doors) || empty($wheels) || empty($prod_date) || empty($year)) {
    $errors[] = $trans['all_fields_required'] ?? 'All fields are required.';
  }

  if($prod_date > date('Y-m-d')) {
    $errors[] = $trans['wrong_prod_date'] ?? "Wrong production date.";
  }

  if($year > date('Y') || $year < $prod_date) {
    $errors[] = $trans['wrong_year'] ?? "Wrong acquisition year.";
  }

  $checkSerial = $pdo->prepare("SELECT id FROM cars WHERE serial_number = ? AND id != ?");
  $checkSerial->execute([$serial, $car_id]);
  if ($checkSerial->rowCount() > 0) {
    $errors[] = $trans['error_serial_exists'] ?? "This serial number is already registered to another car.";
  }

  
  if (empty($errors)) {
    $stmt = $pdo->prepare("UPDATE cars SET serial_number = ?, brand = ?, model = ?, type = ?, engine_type = ?, door_count = ?, wheel_count = ?, production_date = ?, acquisition_year = ? WHERE id = ? AND customer_id = ?");
    $stmt->execute([$serial, $brand, $model, $type, $engine, $doors, $wheels, $prod_date, $year, $car_id, $customer_id]);

    $success = $trans['car_updated'] ?? 'Car updated successfully!';
  } else {
    $error = implode("<br>", $errors); 
  }
}

if($_SESSION['role'] == 'secretary') {
   $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
}else {
  $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND customer_id = ?");
  $stmt->execute([$car_id, $user_id]);
  $car = $stmt->fetch();
}



if (!$car) {
  $error = "Car not found.";
}
?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php'; 
  include 'includes/mobile_bar.php';?>
  <div class="content-area">

  <h2 class="form-title"><?= $trans['edit_car'] ?? 'Edit Car' ?></h2>

  <?php if ($error){ ?>
    <p class="form-message error"><?= $error ?></p>
  <?php }elseif ($success){ ?>
    <p class="form-message success"><?= $trans['car_updated'] ?? 'Car updated successfully!' ?></p>
  <?php } ?>

  <?php if ($car){ ?>
    <form method="POST" action="" class="generic-form">
      <div class="row-container">

        <div class="column-container">
          <div class="form-group">
            <label><?= $trans['serial_number'] ?? 'Serial Number:' ?></label>
            <input type="text" name="serial_number" value="<?= htmlspecialchars($car['serial_number']) ?>" required>
          </div>

          <div class="form-group">
            <label><?= $trans['brand'] ?? 'Brand:' ?></label>
            <input type="text" name="brand" value="<?= htmlspecialchars($car['brand']) ?>" required>
          </div>

          <div class="form-group">
            <label><?= $trans['model'] ?? 'Model:' ?></label>
            <input type="text" name="model" value="<?= htmlspecialchars($car['model']) ?>" required>
          </div>

          <div class="form-group">
            <label><?= $trans['doors'] ?? 'Doors:' ?></label>
            <input type="number" name="door_count" value="<?= htmlspecialchars($car['door_count']) ?>" required>
          </div>

          <div class="form-group">
            <label><?= $trans['wheels'] ?? 'Wheels:' ?></label>
            <input type="number" name="wheel_count" value="<?= htmlspecialchars($car['wheel_count']) ?>" required>
          </div>
        </div>

        <div class="column-container">
          <div class="form-group">
            <label><?= $trans['type'] ?? 'Type:' ?></label>
            <select name="type" required>
              <option value="sedan" <?= $car['type'] == 'sedan' ? 'selected' : '' ?>><?= $trans['sedan'] ?? 'Sedan' ?></option>
              <option value="truck" <?= $car['type'] == 'truck' ? 'selected' : '' ?>><?= $trans['truck'] ?? 'Truck' ?></option>
              <option value="bus" <?= $car['type'] == 'bus' ? 'selected' : '' ?>><?= $trans['bus'] ?? 'Bus' ?></option>
            </select>
          </div>

          <div class="form-group">
            <label><?= $trans['engine_type'] ?? 'Engine Type:' ?></label>
            <select name="engine_type" required>
              <option value="electric" <?= $car['engine_type'] == 'electric' ? 'selected' : '' ?>><?= $trans['electric'] ?? 'Electric' ?></option>
              <option value="diesel" <?= $car['engine_type'] == 'diesel' ? 'selected' : '' ?>><?= $trans['diesel'] ?? 'Diesel' ?></option>
              <option value="gas" <?= $car['engine_type'] == 'gas' ? 'selected' : '' ?>><?= $trans['gas'] ?? 'Gas' ?></option>
              <option value="hybrid" <?= $car['engine_type'] == 'hybrid' ? 'selected' : '' ?>><?= $trans['hybrid'] ?? 'Hybrid' ?></option>
            </select>
          </div>

          <div class="form-group">
            <label><?= $trans['production_date'] ?? 'Production Date:' ?></label>
            <input type="date" name="production_date" value="<?= htmlspecialchars($car['production_date']) ?>" required>
          </div>

          <div class="form-group">
            <label><?= $trans['acquisition_year'] ?? 'Acquisition Year:' ?></label>
            <input type="number" name="acquisition_year" value="<?= htmlspecialchars($car['acquisition_year']) ?>" min="1900" max="<?= date('Y') ?>" required>
          </div>
        </div>
      </div>

      <button type="submit" class="form-button"><?= $trans['save_changes'] ?? 'Save Changes' ?></button>
    </form>
  <?php } ?>
</div>
</div>
</body>
</html>
