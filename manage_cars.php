<?php
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
  require 'config.php';
  $id = $_GET['id'];

  $stmt = $pdo->prepare("
    SELECT 
      c.*,
      u.full_name AS owner_name,
      cu.vat_number,
      cu.address
    FROM cars c
    JOIN customers cu ON c.customer_id = cu.user_id
    JOIN users u ON cu.user_id = u.id
    WHERE c.id = ?
  ");
  $stmt->execute([$id]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  header('Content-Type: application/json');
  echo json_encode($data ?: []);
  exit;
}

include 'includes/dash_header.php';

if ($_SESSION['role'] !== 'secretary') {
  header("Location: dashboard.php");
  exit();
}

$stmt = $pdo->query("
  SELECT cars.*, users.full_name 
  FROM cars 
  JOIN customers ON cars.customer_id = customers.user_id
  JOIN users ON customers.user_id = users.id
");

$cars = $stmt->fetchAll();

// pagination
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

//search
$search_term = $_GET['search_term'] ?? '';

$sql = "
  SELECT cars.*, users.full_name 
  FROM cars 
  JOIN customers ON cars.customer_id = customers.user_id
  JOIN users ON customers.user_id = users.id
";

$params = [];

if (!empty($search_term)) {
    $sql .= " WHERE 
        cars.serial_number LIKE :search 
        OR cars.brand LIKE :search 
        OR cars.model LIKE :search 
        OR users.full_name LIKE :search
    ";
    $params['search'] = '%' . $search_term . '%';
}

$sql .= " ORDER BY cars.id DESC LIMIT $start, $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

$count_sql = "
  SELECT COUNT(*) 
  FROM cars 
  JOIN customers ON cars.customer_id = customers.user_id
  JOIN users ON customers.user_id = users.id
";

if (!empty($search_term)) {
  $count_sql .= " WHERE 
    cars.serial_number LIKE :search 
    OR cars.brand LIKE :search 
    OR cars.model LIKE :search 
    OR users.full_name LIKE :search
  ";
}

$count_stmt = $pdo->prepare($count_sql);
if (!empty($search_term)) {
  $count_stmt->execute(['search' => '%' . $search_term . '%']);
} else {
  $count_stmt->execute();
}
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_car_id'])) {
  session_start();
  require 'config.php';

  $car_id = $_POST['delete_car_id'];
  $user_role = $_SESSION['role'];

  $stmt = $pdo->prepare("SELECT customer_id FROM cars WHERE id = ?");
  $stmt->execute([$car_id]);
  $car = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$car) {
      $error = "Car not found.";
  } else {
      
      $deleteAppointments = $pdo->prepare("DELETE FROM appointments WHERE car_id = ?");
      $deleteAppointments->execute([$car_id]);

      $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
      $stmt->execute([$car_id]);

      header("Location: manage_cars.php");
      exit;
  }
}



// excel csv

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
require 'vendor/autoload.php';

if (isset($_POST['import_cars']) && isset($_FILES['car_file']) && $_FILES['car_file']['error'] === 0) {
    $fileTmp = $_FILES['car_file']['tmp_name'];
    $fileName = $_FILES['car_file']['name'];
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    $imported = 0;
    $skipped = 0;

    $importCarsSuccess = '';
    $importCarsError = '';

    try {
        if ($extension === 'csv') {
            $reader = new Csv();
            $reader->setDelimiter(',');
            $reader->setEnclosure('"');
            $reader->setSheetIndex(0);
            $spreadsheet = $reader->load($fileTmp);
        } elseif ($extension === 'xlsx') {
            $spreadsheet = IOFactory::load($fileTmp);
        } else {
            throw new Exception("Unsupported file type.");
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headersRow = array_shift($rows);
        $headers = [];
        foreach ($headersRow as $col => $header) {
            $headers[$col] = strtolower(trim($header));
        }

        $valid_types = ['sedan', 'truck', 'bus'];
        $valid_engines = ['electric', 'diesel', 'gas', 'hybrid'];

        foreach ($rows as $line) {
            $row = [];
            foreach ($headers as $col => $field) {
                $row[$field] = trim($line[$col] ?? '');
            }

            $serial_number = $row['serial_number'] ?? '';
            $brand = $row['brand'] ?? '';
            $model = $row['model'] ?? '';
            $type = strtolower($row['type'] ?? '');
            $engine_type = strtolower($row['engine_type'] ?? '');
            $door_count = $row['door_count'] ?? '';
            $wheel_count = $row['wheel_count'] ?? '';
            $production_date = $row['production_date'] ?? '';
            $year = $row['aquisition_year'] ?? '';
            $customer_id = $row['customer_id'] ?? '';

            $checkCustomer = $pdo->prepare("SELECT user_id FROM customers WHERE user_id = ?");
            $checkCustomer->execute([$customer_id]);
            if ($checkCustomer->rowCount() === 0) {
                $skipped++;
                continue;
            }

            if (
                !$serial_number || !$brand || !$model || !$type || !$engine_type ||
                !is_numeric($door_count) || !is_numeric($wheel_count) ||
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $production_date) ||
                !is_numeric($year) || !is_numeric($customer_id) ||
                !in_array($type, $valid_types) || !in_array($engine_type, $valid_engines)
            ) {
                $skipped++;
                continue;
            }

            $check = $pdo->prepare("SELECT id FROM cars WHERE serial_number = ?");
            $check->execute([$serial_number]);
            if ($check->rowCount() > 0) {
                $skipped++;
                continue;
            }

            $insert = $pdo->prepare("
                INSERT INTO cars 
                (serial_number, brand, model, type, engine_type, door_count, wheel_count, production_date, acquisition_year, customer_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $serial_number,
                $brand,
                $model,
                $type,
                $engine_type,
                $door_count,
                $wheel_count,
                $production_date,
                $year,
                $customer_id
            ]);

            $imported++;
        }

        $importCarsSuccess = "$imported cars imported. $skipped skipped.";
    } catch (Exception $e) {
        $importCarsError = "Import failed: " . $e->getMessage();
    }
}

// end xlsx csv

?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php';
  include 'includes/mobile_bar.php'; ?>
  <div class="content-area">
    <h2><?= $trans['all_cars'] ?? 'All Registered Cars' ?></h2>

    <form method="GET" action="manage_cars.php" class="search-form">
        <input type="text" name="search_term" placeholder="<?= $trans['search_car_placeholder'] ?? 'Search by Serial, Brand, Model or Owner' ?>">
        <button type="submit"><?= $trans['search'] ?? 'Search' ?></button>
        <?php if (!empty($_GET['search_term'])){ ?>
          <a href="manage_cars.php" style="margin-left:10px;">Reset</a>
        <?php } ?>   
    </form>

    <form method="POST" enctype="multipart/form-data" class="import-form">
      <label><?= $trans['import_cars_label'] ?? 'Import cars (CSV/Excel):' ?></label>
      <input type="file" name="car_file" required>
      <button type="submit" name="import_cars" class="form-button"><?= $trans['import'] ?? 'Import' ?></button>
    </form>

    <?php if (!empty($importCarsError)): ?>
      <p class="form-message error"><?= $importCarsError ?></p>
    <?php elseif (!empty($importCarsSuccess)): ?>
      <p class="form-message success"><?= $importCarsSuccess ?></p>
    <?php endif; ?>


    <div class="row-container">

      <div class="column-container">
        <div class="limit-select">
          <label for="limit"><?= $trans['results_per_page'] ?? 'Results per page:' ?></label>
          <select id="limit-select">
            <?php foreach ([2, 5, 10, 20] as $opt): ?>
              <option value="<?= $opt ?>" <?= ($limit == $opt ? 'selected' : '') ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th><?= $trans['brand'] ?? 'Brand' ?></th>
              <th><?= $trans['model'] ?? 'Model' ?></th>
              <th><?= $trans['owner'] ?? 'Owner' ?></th>
              <th><?= $trans['actions'] ?? 'Actions' ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cars as $car): ?>
              <tr class="row-clickable" data-id="<?= $car['id'] ?>">
                <td><?= htmlspecialchars($car['brand']) ?></td>
                <td><?= htmlspecialchars($car['model']) ?></td>
                <td><?= htmlspecialchars($car['full_name']) ?></td>
                <td class="actions">
                  <a class="edit-button" href="edit_car.php?id=<?= $car['id'] ?>"><?= $trans['edit'] ?? 'Edit' ?></a>
                  <form method="POST" onsubmit="return confirm('<?= $trans['confirm_delete_car'] ?? 'Are you sure you want to delete this car?' ?>');">
                    <input type="hidden" name="delete_car_id" value="<?= $car['id'] ?>">
                    <button type="submit" class="delete-button"><?= $trans['delete'] ?? 'Delete' ?></button>
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

      </div>

      <div class="details-panel" id="detailsPanel">
        <h3><?= $trans['details'] ?? 'Details' ?></h3>
        <p><?= $trans['select_car_details'] ?? 'Select a car to view details' ?></p>

      </div>

    </div>

  </div>


<script>

  function DetailsOnClick() {
  document.querySelectorAll('.row-clickable').forEach(row => {
    row.addEventListener('click', () => {
      document.querySelectorAll('.row-clickable').forEach(r => r.classList.remove('active'));
      row.classList.add('active');

      const carId = row.dataset.id;

      fetch(`manage_cars.php?action=details&id=${carId}`)
        .then(response => response.json())
        .then(data => {
          const panel = document.getElementById('detailsPanel');

          if (!data || Object.keys(data).length === 0) {
            panel.innerHTML = '<h3>Details</h3><p>No data found for this car.</p>';
            return;
          }

          panel.innerHTML = `
            <h3><?= $trans['car_details'] ?? 'Car Details' ?></h3>
            <p><strong><?= $trans['serial_number'] ?? 'Serial Number' ?>:</strong> ${data.serial_number}</p>
            <p><strong><?= $trans['model'] ?? 'Model' ?>:</strong> ${data.model}</p>
            <p><strong><?= $trans['brand'] ?? 'Brand' ?>:</strong> ${data.brand}</p>
            <p><strong><?= $trans['type'] ?? 'Type' ?>:</strong> ${data.type}</p>
            <p><strong><?= $trans['engine'] ?? 'Engine' ?>:</strong> ${data.engine_type}</p>
            <p><strong><?= $trans['doors'] ?? 'Doors' ?>:</strong> ${data.door_count}</p>
            <p><strong><?= $trans['wheels'] ?? 'Wheels' ?>:</strong> ${data.wheel_count}</p>
            <p><strong><?= $trans['production_date'] ?? 'Production Date' ?>:</strong> ${data.production_date}</p>
            <p><strong><?= $trans['acquisition_year'] ?? 'Acquisition Year' ?>:</strong> ${data.acquisition_year}</p>
            <p><strong><?= $trans['owner'] ?? 'Owner' ?>:</strong> ${data.owner_name}</p>
          `;

        })
        .catch(err => {
          console.error('Error loading car details:', err);
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
</div>
</body>
</html>
