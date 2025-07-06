<?php
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
  session_start();
  require 'config.php';
  $id = $_GET['id'];

  $selected_user = $pdo->prepare("
    SELECT 
      u.id, u.full_name, u.username, u.email, u.role, u.id_number, u.is_active,
      c.address,
      c.vat_number,
      m.specialty
    FROM users u
    LEFT JOIN customers c ON u.id = c.user_id
    LEFT JOIN mechanics m ON u.id = m.user_id
    WHERE u.id = ?
    LIMIT 1
  ");
  $selected_user->execute([$id]);
  $data = $selected_user->fetch(PDO::FETCH_ASSOC);


  header('Content-Type: application/json');
  echo json_encode($data ?: []);
  exit;
}

include 'includes/dash_header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'secretary') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
  $userIdToDelete = $_POST['delete_user_id'];

  
  $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
  $stmt->execute([$userIdToDelete]);
  $userData = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($userData) {
      $role = $userData['role'];

      if ($role === 'secretary') {
        return;
      }

      if ($role === 'customer') {
        $pdo->prepare("DELETE FROM appointments WHERE customer_id = ?")->execute([$userIdToDelete]);
        $pdo->prepare("DELETE FROM cars WHERE customer_id = ?")->execute([$userIdToDelete]);
        $pdo->prepare("DELETE FROM customers WHERE user_id = ?")->execute([$userIdToDelete]);
      }

      if ($role === 'mechanic') {
        $pdo->prepare("UPDATE appointments SET mechanic_id = NULL WHERE mechanic_id = ?")->execute([$userIdToDelete]);
        $pdo->prepare("DELETE FROM mechanics WHERE user_id = ?")->execute([$userIdToDelete]);
      }

      
      $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userIdToDelete]);
  }
}

// pagination
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$sql = "SELECT id, full_name, username, email, role, is_active FROM users";

if (!empty($search)) {
    $sql .= " WHERE username LIKE :search OR full_name LIKE :search OR id_number LIKE :search";
}
$sql .= " ORDER BY full_name ASC LIMIT $start, $limit";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll();

// total pages
$count_sql = "SELECT COUNT(*) FROM users";
if (!empty($search)) {
    $count_sql .= " WHERE username LIKE :search OR full_name LIKE :search OR id_number LIKE :search";
}
$count_stmt = $pdo->prepare($count_sql);
if (!empty($search)) {
    $count_stmt->execute(['search' => "%$search%"]);
} else {
    $count_stmt->execute();
}
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $limit);


// excel csv import
use PhpOffice\PhpSpreadsheet\IOFactory;
require 'vendor/autoload.php';

if (isset($_POST['import_users']) && isset($_FILES['user_file']) && $_FILES['user_file']['error'] === 0) {
    $fileTmp = $_FILES['user_file']['tmp_name'];
    $fileName = $_FILES['user_file']['name'];
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    $imported = 0;
    $skipped = 0;
    $importUsersSuccess = '';
    $importUsersError = '';

    try {
        if (!in_array($extension, ['csv', 'xlsx'])) {
          throw new Exception("Unsupported file type.");
        }

        if ($extension === 'csv') {
          $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
          $reader->setDelimiter(',');
          $reader->setEnclosure('"');
          $reader->setSheetIndex(0);
          $spreadsheet = $reader->load($fileTmp);
        } else {
          $spreadsheet = IOFactory::load($fileTmp);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); 

        
        $headersRow = array_shift($rows);
        $headers = [];
        foreach ($headersRow as $col => $header) {
            $headers[$col] = strtolower(trim($header));
        }

        foreach ($rows as $line) {
            $row = [];
            foreach ($headers as $col => $field) {
                $row[$field] = trim($line[$col] ?? '');
            }

            $full_name = $row['full_name'] ?? '';
            $email = $row['email'] ?? '';
            $username = $row['username'] ?? '';
            $id_number = $row['id_number'] ?? '';
            $role = strtolower($row['role'] ?? '');

            if (!$full_name || !$email || !$username || !$id_number || !in_array($role, ['customer', 'mechanic', 'secretary'])) {
                $skipped++;
                continue;
            }

            $address = trim($row['address'] ?? '');
            $vat = trim($row['vat_number'] ?? '');
            $specialty = trim($row['specialty'] ?? '');

            if ($role === 'customer' && (!$address || !$vat)) {
                $skipped++;
                continue;
            }
            if ($role === 'mechanic' && !$specialty) {
                $skipped++;
                continue;
            }

            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? OR id_number = ?");
            $check->execute([$email, $username, $id_number]);
            if ($check->rowCount() > 0) {
                $skipped++;
                continue;
            }

            $insert = $pdo->prepare("INSERT INTO users (full_name, email, username, id_number, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $insert->execute([$full_name, $email, $username, $id_number, $role]);
            $newUserId = $pdo->lastInsertId();

            if ($role === 'customer') {
                $pdo->prepare("INSERT INTO customers (user_id, address, vat_number) VALUES (?, ?, ?)")->execute([$newUserId, $address, $vat]);
            } elseif ($role === 'mechanic') {
                $pdo->prepare("INSERT INTO mechanics (user_id, specialty) VALUES (?, ?)")->execute([$newUserId, $specialty]);
            }

            $imported++;
        }

        $importUsersSuccess = "$imported users imported. $skipped skipped.";
    } catch (Exception $e) {
        $importUsersError = "Import failed: " . $e->getMessage();
    }
}

// end xlsx csv

?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php'; 
  include 'includes/mobile_bar.php';?>
  <div class="content-area">

    <h2><?= $trans['manage_users'] ?? 'Manage Users' ?></h2>
    
    <form method="GET" action="manage_users.php" class="search-form">
      <input type="text" name="search" placeholder="<?= $trans['search_placeholder_users'] ?? 'Search by name, username or ID...' ?>" value="<?= htmlspecialchars($search) ?>">
      <button type="submit"><?= $trans['search'] ?? 'Search' ?></button>
      <?php if (!empty($search)){ ?>
        <a href="manage_users.php" style="margin-left:10px;"><?= $trans['reset'] ?? 'Reset' ?></a>
      <?php } ?>
    </form>

    <form method="POST" enctype="multipart/form-data" class="import-form">
      <label for="user_file"><?= $trans['import_users_label'] ?? 'Import users (CSV/Excel):' ?></label>
      <input type="file" name="user_file" accept=".csv, .xlsx" required>
      <button type="submit" name="import_users" class="form-button"><?= $trans['import'] ?? 'Import' ?></button>
    </form>

    <?php if (!empty($importUsersError)): ?>
      <p class="form-message error"><?= $importUsersError ?></p>
    <?php elseif (!empty($importUsersSuccess)): ?>
      <p class="form-message success"><?= $importUsersSuccess ?></p>
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
              <th><?= $trans['full_name'] ?? 'Full Name' ?></th>
              <th><?= $trans['role'] ?? 'Role' ?></th>
              <th><?= $trans['actions'] ?? 'Actions' ?></th>
            </tr>
          </thead>
          <tbody>
              <?php foreach ($users as $user): ?>
                <tr class="row-clickable" data-id="<?= $user['id'] ?>">
                  <td><?= htmlspecialchars($user['full_name']) ?></td>
                  <td><?= ucfirst($user['role']) ?></td>
                  <td class="actions">
                    <a class="edit-button" href="edit_user.php?id=<?= $user['id'] ?>"><?= $trans['edit'] ?? 'Edit' ?></a>
                    <?php if ($user['role'] !== 'secretary'){ ?>
                      <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="delete-button"><?= $trans['delete'] ?? 'Delete' ?></button>
                      </form>
                    <?php } ?>
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

          <span style="margin: 0 10px;">Page <?= $page ?></span>

          <?php if ($page < $total_pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" title="Next Page"><i class="fa-solid fa-angle-right"></i></a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" title="Last Page"><i class="fa-solid fa-angles-right"></i></a>
          <?php endif; ?>
        </div>

      </div>
    
      <div class="details-panel" id="detailsPanel">
        <h3><?= $trans['details'] ?? 'Details' ?></h3>
        <p><?= $trans['select_user'] ?? 'Select a user to view details' ?></p>
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

    const userId = row.dataset.id;

    fetch(`manage_users.php?action=details&id=${userId}`)
      .then(response => response.json())
      .then(data => {
        const details = document.getElementById('detailsPanel');

        if (!data || Object.keys(data).length === 0) {
          details.innerHTML = '<h3>Details</h3><p>No data found for this user.</p>';
          return;
        }

        let html = `
          <h3><?= $trans['details'] ?? 'Details' ?></h3>
          <p><strong><?= $trans['full_name'] ?? 'Full Name' ?>:</strong> ${data.full_name}</p>
          <p><strong><?= $trans['username'] ?? 'Username' ?>:</strong> ${data.username}</p>
          <p><strong><?= $trans['email'] ?? 'Email' ?>:</strong> ${data.email}</p>
          <p><strong><?= $trans['id_number'] ?? 'ID Number' ?>:</strong> ${data.id_number}</p>
          <p><strong><?= $trans['status'] ?? 'Status' ?>:</strong> ${data.is_active == 1 ? '<?= $trans['active'] ?? 'Active' ?>' : '<?= $trans['inactive'] ?? 'Inactive' ?>'}</p>
          <p><strong><?= $trans['role'] ?? 'Role' ?>:</strong> ${data.role}</p>
        `;

        
        if (data.role === 'customer') {
          html += `<p><strong><?= $trans['address'] ?? 'Address' ?>:</strong> ${data.address || '—'}</p>
            <p><strong><?= $trans['vat_number'] ?? 'Vat Number' ?>:</strong> ${data.vat_number || '—'}</p>
            <br>
            <a href="export_cust.php?id=${data.id}&type=excel" class="button"><?= $trans['export_excel'] ?? 'Export Excel' ?></a>
            <br>
            <a href="export_cust.php?id=${data.id}&type=pdf" class="button" target="_blank"><?= $trans['export_pdf'] ?? 'Export PDF' ?></a>
          `;


        }


        if (data.role === 'mechanic') {
          html += 
              `<p><strong><?= $trans['specialty'] ?? 'Specialty' ?>:</strong> ${data.specialty || '—'}</p>
          `;

        }

        details.innerHTML = html;
      })
      .catch(err => {
        document.getElementById('detailsPanel').innerHTML = `<p style="color:red;">Error loading details</p>`;
        console.error(err);
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
