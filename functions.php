<?php

function updateAppointmentsToCompleted(PDO $pdo, $mechanic_id) {
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'COMPLETED' 
        WHERE mechanic_id = ? 
            AND status = 'IN_PROGRESS' 
            AND TIMESTAMP(appointment_date, ADDTIME(appointment_time, '02:00:00')) <= NOW()
    ");

    $stmt->execute([$mechanic_id]);
}

function updateMissedAppointments(PDO $pdo) {
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');

    $stmt = $pdo->prepare("
        UPDATE appointments
        SET status = 'CANCELLED'
        WHERE status = 'CREATED'
          AND (
              appointment_date < ?
              OR (
                  appointment_date = ?
                  AND ADDTIME(appointment_time, '02:00:00') < ?
              )
          )
    ");
    $stmt->execute([$currentDate, $currentDate, $currentTime]);
}

function updateAppointmentCost($pdo, $appointment_id) {
    $stmt = $pdo->prepare("SELECT SUM(cost) FROM job WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $totalCost = $stmt->fetchColumn() ?? 0;

    $update = $pdo->prepare("UPDATE appointments SET cost = ? WHERE id = ?");
    $update->execute([$totalCost, $appointment_id]);
}


?>
