<?php 
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
    session_start();
    require 'config.php';

    $car_id = $_GET['id'];

    $selected_car = $pdo->prepare("SELECT * FROM cars WHERE id = ? LIMIT 1");
    $selected_car->execute([$car_id]);
    $data = $selected_car->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($data ?: []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_car_id'])) {
  session_start();
  require 'config.php';

  $car_id = $_POST['delete_car_id'];
  $user_id = $_SESSION['user_id'];
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

      header("Location: my_cars.php");
      exit;
  }
}

include 'includes/dash_header.php';

$user_id = $_SESSION['user_id'];
$search_term = $_GET['search_term'] ?? '';

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;


//search
$params = [];
$params['user_id'] = $_SESSION['user_id'];


$sql = "
  SELECT cars.*, users.full_name 
  FROM cars 
  JOIN customers ON cars.customer_id = customers.user_id
  JOIN users ON customers.user_id = users.id
  WHERE customers.user_id = :user_id
";

if (!empty($search_term)) {
    $sql .= " AND (
        cars.serial_number LIKE :search 
        OR cars.brand LIKE :search 
        OR cars.model LIKE :search 
        OR users.full_name LIKE :search
    )";
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
  WHERE customers.user_id = :user_id
";

if (!empty($search_term)) {
  $count_sql .= " AND (
    cars.serial_number LIKE :search 
    OR cars.brand LIKE :search 
    OR cars.model LIKE :search 
    OR users.full_name LIKE :search
  )";
}

$count_stmt = $pdo->prepare($count_sql);
$params['user_id'] = $_SESSION['user_id'];
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

 ?>

<div class="layout-container">
<?php include 'includes/dash_sidebar.php';
include 'includes/mobile_bar.php';



if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: dashboard.php");
    exit();
}


?>

  <div class="content-area">

    <h2><?= $trans['my_cars'] ?? 'My Cars'?></h2>

    <form method="GET" action="my_cars.php" class="search-form">
      <input type="text" name="search_term" placeholder="<?= $trans['search_placeholder'] ?? 'Search by Serial, Brand or Model' ?>" value="<?= htmlspecialchars($_GET['search_term'] ?? '') ?>">
      <button type="submit"><?= $trans['search'] ?? 'Search'?></button>
      <?php if (!empty($_GET['search_term'])): ?>
        <a href="my_cars.php" style="margin-left:10px;"><?= $trans['reset'] ?? 'Reset' ?></a>
      <?php endif; ?>
    </form>

    <div class="row-container">
      <div class="column-container">
        <?php if (empty($cars)): ?>
          <p><?= $trans['no_cars'] ?? "You haven't registered any cars yet." ?></p>
        <?php else: ?>
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
                <th><?= $trans['actions'] ?? 'Actions' ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cars as $car): ?>
                <tr class="row-clickable" data-id="<?= $car['id'] ?>">
                  <td><?= htmlspecialchars($car['brand']) ?></td>
                  <td><?= htmlspecialchars($car['model']) ?></td>
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
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" title="<?= $trans['first_page'] ?? 'First Page' ?>"><i class="fa-solid fa-angles-left"></i></a>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" title="<?= $trans['prev_page'] ?? 'Previous Page' ?>"><i class="fa-solid fa-angle-left"></i></a>
            <?php endif; ?>

            <span style="margin: 0 10px;"><?= $trans['page'] ?? 'Page' ?> <?= $page ?></span>

            <?php if ($page < $total_pages): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" title="<?= $trans['next_page'] ?? 'Next Page' ?>"><i class="fa-solid fa-angle-right"></i></a>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" title="<?= $trans['last_page'] ?? 'Last Page' ?>"><i class="fa-solid fa-angles-right"></i></a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (isset($error)) {
          echo '<p class="form-message error">' . $error . '</p>';
        } ?>

        <a href="add_car.php" class="button"><?= $trans['add_car'] ?? 'Add New Car' ?></a>
      </div>

      <?php if (!empty($cars)): ?>
        <div class="details-panel" id="detailsPanel">
          <h3><?= $trans['car_details'] ?? 'Car Details' ?></h3>
          <p><?= $trans['select_car'] ?? 'Select a car to view details.' ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>


    <script>
      function DetailsOnClick() {
      document.querySelectorAll('.row-clickable').forEach(row => {
        row.addEventListener('click', () => {
          document.querySelectorAll('.row-clickable').forEach(r => r.classList.remove('active'));
          row.classList.add('active');

          const carId = row.dataset.id;

          fetch(`my_cars.php?action=details&id=${carId}`)
            .then(response => response.json())
            .then(data => {
              const panel = document.getElementById('detailsPanel');

              if (!data || Object.keys(data).length === 0) {
                panel.innerHTML = '<h3>Car Details</h3><p>No data found for this car.</p>';
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
</div>
</body>
</html>