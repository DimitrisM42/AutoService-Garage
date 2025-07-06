<?php
require 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'secretary') {
    die('Access denied');
}

$customer_id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'excel';

if (!$customer_id) {
    die('Invalid request');
}

// Cust get
$stmt = $pdo->prepare("SELECT u.full_name, u.email, u.id_number, c.vat_number, c.address
                       FROM users u
                       JOIN customers c ON u.id = c.user_id
                       WHERE u.id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();
if (!$customer) die('Customer not found');

// cars get
$stmt = $pdo->prepare("SELECT * FROM cars WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$cars = $stmt->fetchAll();

// appointmets get
$stmt = $pdo->prepare("
    SELECT a.*, m.full_name AS mechanic_name, ca.brand, ca.model
    FROM appointments a
    JOIN cars ca ON a.car_id = ca.id
    LEFT JOIN users m ON a.mechanic_id = m.id
    WHERE a.customer_id = ?
    ORDER BY a.appointment_date DESC");
$stmt->execute([$customer_id]);
$appointments = $stmt->fetchAll();


//pdf
if ($type === 'pdf') {
    ob_start();
    ?>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
        }
        h2 { 
            text-align: center; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 25px; 
        }
        td, th { 
            border: 1px solid #999; 
            padding: 6px; 
        }
        th { 
            background-color: #f0f0f0; 
        }
    </style>

    <img src="images/AutoServiceGarageLogo.png" style="width: 80px; display: block; margin: 0 auto;">
    <h2>Customer History</h2>
    <h3>Customer Details</h3>
    <table>
        <tr><td><b>Full Name</b></td><td><?= $customer['full_name'] ?></td></tr>
        <tr><td><b>Email</b></td><td><?= $customer['email'] ?></td></tr>
        <tr><td><b>ID Number</b></td><td><?= $customer['id_number'] ?></td></tr>
        <tr><td><b>VAT</b></td><td><?= $customer['vat_number'] ?></td></tr>
        <tr><td><b>Address</b></td><td><?= $customer['address'] ?></td></tr>
    </table>

    <h3>Cars</h3>
    <?php if ($cars): ?>
    <table>
        <tr>
            <th>Serial Number</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Type</th>
            <th>Engine Type</th>
            <th>Doors</th>
            <th>Wheels</th>
            <th>Production Date</th>
            <th>Acquisition Year</th>
        
        
        </tr>
        <?php foreach ($cars as $car): ?>
        <tr>
            <td><?= $car['serial_number'] ?></td>
            <td><?= $car['brand'] ?></td>
            <td><?= $car['model'] ?></td>
            <td><?= $car['type'] ?></td>
            <td><?= $car['engine_type'] ?></td>
            <td><?= $car['door_count'] ?></td>
            <td><?= $car['wheel_count'] ?></td>
            <td><?= $car['production_date'] ?></td>
            <td><?= $car['acquisition_year'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p>No cars found</p>
    <?php endif; ?>

    <h3>Appointments</h3>
    <?php if ($appointments): ?>
    <table>
      <tr>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th>Reason</th>
        <th>Description</th>
        <th>Car</th>
        <th>Mechanic</th>
      </tr>
      <?php foreach ($appointments as $a): ?>
        <tr>
          <td><?= $a['appointment_date'] ?></td>
          <td><?= $a['appointment_time'] ?></td>
          <td><?= $a['status'] ?></td>
          <td><?= $a['reason'] ?></td>
          <td><?= $a['problem_description'] ?: '—' ?></td>
          <td><?= $a['brand'] . ' ' . $a['model'] ?></td>
          <td><?= $a['mechanic_name'] ?: '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p>No appointments found</p>
    <?php endif; ?>

    <?php
    $html = ob_get_clean();
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('chroot', __DIR__);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("customer_history.pdf", ["Attachment" => false]);
    exit;
}

//excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$row = 1;
$sheet->setCellValue("A$row", "Customer History");
$row += 2;
$sheet->setCellValue("A$row", "Full Name");
$sheet->setCellValue("B$row", $customer['full_name']);
$row++;
$sheet->setCellValue("A$row", "Email");
$sheet->setCellValue("B$row", $customer['email']);
$row++;
$sheet->setCellValue("A$row", "ID Number");
$sheet->setCellValue("B$row", $customer['id_number']);
$row++;
$sheet->setCellValue("A$row", "VAT Number");
$sheet->setCellValue("B$row", $customer['vat_number']);
$row++;
$sheet->setCellValue("A$row", "Address");
$sheet->setCellValue("B$row", $customer['address']);

$row += 2;
$sheet->setCellValue("A$row", "Cars:");
$row++;
$sheet->setCellValue("A$row", "Brand");
$sheet->setCellValue("B$row", "Model");
$sheet->setCellValue("C$row", "Serial Number");
foreach ($cars as $car) {
    $row++;
    $sheet->setCellValue("A$row", $car['brand']);
    $sheet->setCellValue("B$row", $car['model']);
    $sheet->setCellValue("C$row", $car['serial_number']);
}

$row += 2;
$sheet->setCellValue("A$row", "Appointments:");
$row++;
$sheet->setCellValue("A$row", "Date");
$sheet->setCellValue("B$row", "Time");
$sheet->setCellValue("C$row", "Status");
$sheet->setCellValue("D$row", "Reason");
$sheet->setCellValue("E$row", "Description");
$sheet->setCellValue("F$row", "Car");
$sheet->setCellValue("G$row", "Mechanic");

foreach ($appointments as $a) {
    $row++;
    $sheet->setCellValue("A$row", $a['appointment_date']);
    $sheet->setCellValue("B$row", $a['appointment_time']);
    $sheet->setCellValue("C$row", $a['status']);
    $sheet->setCellValue("D$row", $a['reason']);
    $sheet->setCellValue("E$row", $a['problem_description'] ?: '-');
    $sheet->setCellValue("F$row", "{$a['brand']} {$a['model']}");
    $sheet->setCellValue("G$row", $a['mechanic_name'] ?: '—');
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="customer_history.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
