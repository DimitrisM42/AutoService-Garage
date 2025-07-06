<?php
include 'includes/dash_header.php'; ?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php';
  include 'includes/mobile_bar.php';


  if ($_SESSION['role'] !== 'customer' && $_SESSION['role'] !== 'secretary') {
    header("Location: dashboard.php");
    exit();
  }

  $user_id = $_SESSION['user_id'];
  $success = false;
  $errors = [];

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $serial = $_POST['serial_number'];
      $brand = $_POST['brand'];
      $model = $_POST['model'];
      $type = $_POST['type'];
      $engine = $_POST['engine_type'];
      $doors = $_POST['door_count'];
      $wheels = $_POST['wheel_count'];
      $prod_date = $_POST['production_date'];
      $year = $_POST['acquisition_year'];
      $customer_id = $_SESSION['user_id'];

      
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

      

      $stmt = $pdo->prepare("SELECT * FROM cars WHERE serial_number = ?");
      $stmt->execute([$serial]);
      if ($stmt->rowCount() > 0) {
          $errors[] = $trans['serial_exists'] ?? "This serial number already exists.";
      }

      if (empty($errors)) {
          $stmt = $pdo->prepare("INSERT INTO cars (serial_number, model, brand, type, engine_type, door_count, wheel_count, production_date, acquisition_year, customer_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
          $stmt->execute([$serial, $model, $brand, $type, $engine, $doors, $wheels, $prod_date, $year, $customer_id]);
          $success = true;
      }
  }
?>

<div class="content-area">

  <h2 class="form-title"><?= $trans['add_car'] ?? 'Add New Car' ?></h2>

  <?php if (!empty($errors)) { ?>
    <div class="form-message error"><?= implode('<br>', $errors) ?></div>
  <?php } elseif ($success) { ?>
    <div class="form-message success">
      <?= $trans['car_added_success'] ?? 'Car added successfully!' ?>
      <a href="my_cars.php"><?= $trans['back_to_cars'] ?? 'Go back to My Cars' ?></a>
    </div>
  <?php } ?>

  <form method="POST" action="" class="generic-form">
    <div class="form-group">
      <label><?= $trans['car_details'] ?? 'Car Details:' ?></label>
      <input type="text" name="serial_number" placeholder="<?= $trans['serial_number'] ?? 'Serial Number' ?>" required />
      <input type="text" name="brand" placeholder="<?= $trans['brand'] ?? 'Brand' ?>" required />
      <input type="text" name="model" placeholder="<?= $trans['model'] ?? 'Model' ?>" required />
      <input type="number" name="door_count" placeholder="<?= $trans['number_of_doors'] ?? 'Number of Doors' ?>" required />
      <input type="number" name="wheel_count" placeholder="<?= $trans['number_of_wheels'] ?? 'Number of Wheels' ?>" required />
    </div>

    <div class="form-group">
      <label><?= $trans['type'] ?? 'Type' ?>:</label>
      <select name="type" required>
        <option value="sedan"><?= $trans['sedan'] ?? 'Sedan' ?></option>
        <option value="truck"><?= $trans['truck'] ?? 'Truck' ?></option>
        <option value="bus"><?= $trans['bus'] ?? 'Bus' ?></option>
      </select>
    </div>

    <div class="form-group">
      <label><?= $trans['engine_type'] ?? 'Engine Type' ?>:</label>
      <select name="engine_type" required>
        <option value="electric"><?= $trans['electric'] ?? 'Electric' ?></option>
        <option value="diesel"><?= $trans['diesel'] ?? 'Diesel' ?></option>
        <option value="gas"><?= $trans['gas'] ?? 'Gas' ?></option>
        <option value="hybrid"><?= $trans['hybrid'] ?? 'Hybrid' ?></option>
      </select>
    </div>

    <div class="form-group">
      <label><?= $trans['production_date'] ?? 'Production Date' ?>:</label>
      <input type="date" name="production_date" required />
    </div>

    <div class="form-group">
      <label><?= $trans['acquisition_year'] ?? 'Acquisition Year' ?>:</label>
      <input type="number" name="acquisition_year" placeholder="e.g. 2021" required />
    </div>

    <button type="submit" class="form-button"><?= $trans['add_car'] ?? 'Add Car' ?></button>
  </form>
</div>

</div>
</body>
</html>
