<?php include 'includes/dash_header.php'; ?>

<div class="layout-container">
  <?php include 'includes/dash_sidebar.php';
    include 'includes/mobile_bar.php';
    include 'config.php'; 

    $user_id = $_SESSION['user_id'];
    
    if ($hrole === 'customer') {
      
      
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE customer_id = ?");
      $stmt->execute([$user_id]);
      $totalAppointments = $stmt->fetchColumn();
  
      
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE customer_id = ? AND appointment_date >= CURDATE() AND status IN ('CREATED')");
      $stmt->execute([$user_id]);
      $upcomingAppointments = $stmt->fetchColumn();
  

      $stmt = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE customer_id = ?");
      $stmt->execute([$user_id]);
      $totalCars = $stmt->fetchColumn();


      $stmt = $pdo->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE customer_id = ? 
      AND (appointment_date > CURDATE() OR (appointment_date = CURDATE() AND appointment_time >= CURTIME())) 
      AND status = 'CREATED' ORDER BY appointment_date, appointment_time LIMIT 1");



      $stmt->execute([$user_id]);
      $nextAppointment = $stmt->fetch(PDO::FETCH_ASSOC);

      
      $stmt = $pdo->prepare("SELECT appointment_date, appointment_time, status FROM appointments WHERE customer_id = ? AND appointment_date <= CURDATE()
      ORDER BY appointment_date DESC, appointment_time DESC LIMIT 5");

      $stmt->execute([$user_id]);
      $historyAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC); ?>

      <div class="content-area">

        <div class="dashboard-overview">
          <div class="card">
            <h3><?= $trans['total_appointments'] ?? 'Total Appointments'?></h3>
            <p><?php echo $totalAppointments; ?></p>
          </div>
          <div class="card">
            <h3><?= $trans['upcoming'] ?? 'Upcoming'?></h3>
            <p><?php echo $upcomingAppointments; ?></p>
          </div>
          <div class="card">
            <h3><?= $trans['my_cars'] ?? 'My cars'?></h3>
            <p><?php echo $totalCars; ?></p>
          </div>

          <?php if ($nextAppointment): ?>
            <div class="card highlight">
              <h3><?= $trans['next_appointment'] ?? 'Next Appointment' ?></h3>
              <p><?= date("d/m/Y", strtotime($nextAppointment['appointment_date'])) . ' at ' . date("H:i", strtotime($nextAppointment['appointment_time'])) ?></p>
            </div>
          <?php endif; ?>

        </div>

        <br>
        
        
        <h3 class="title"><?= $trans['appointment_history'] ?? 'Appointment History' ?></h3>
        <table class="table">
        
          <thead>
            <th><?= $trans['date'] ?? 'Date'?></th>
            <th><?= $trans['time'] ?? 'Time'?></th>
            <th><?= $trans['status'] ?? 'Status'?></th>
          </thead>

          <?php foreach ($historyAppointments as $app): ?>
            <tr>
              <td><?= date("d/m/Y", strtotime($app['appointment_date'])) ?></td>
              <td><?= date("H:i", strtotime($app['appointment_time'])) ?></td>
              <td><?= $app['status'] ?></td>
            </tr>
          <?php endforeach; ?>
        </table>

      </div>

      
    </div>
  </div>

 <?php } elseif ($hrole === 'mechanic') { 
  
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE mechanic_id = ?");
    $stmt->execute([$user_id]);
    $totalAppointments = $stmt->fetchColumn();

    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE mechanic_id = ? AND appointment_date >= CURDATE() AND status IN ('CREATED')");
    $stmt->execute([$user_id]);
    $upcomingAppointments = $stmt->fetchColumn();


    $stmt = $pdo->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE mechanic_id = ? 
    AND (appointment_date > CURDATE() OR (appointment_date = CURDATE() AND appointment_time >= CURTIME())) 
    AND status = 'CREATED' ORDER BY appointment_date, appointment_time LIMIT 1");



    $stmt->execute([$user_id]);
    $nextAppointment = $stmt->fetch(PDO::FETCH_ASSOC);

    
    $stmt = $pdo->prepare("SELECT appointment_date, appointment_time, status FROM appointments WHERE mechanic_id = ? AND appointment_date <= CURDATE()
    ORDER BY appointment_date DESC, appointment_time DESC LIMIT 5");

    $stmt->execute([$user_id]);
    $historyAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC); ?>

  <div class="content-area">

  <div class="dashboard-overview">
    <div class="card">
      <h3><?= $trans['total_appointments'] ?? 'Total Appointments'?></h3>
      <p><?php echo $totalAppointments; ?></p>
    </div>
    <div class="card">
      <h3><?= $trans['upcoming'] ?? 'Upcoming'?></h3>
      <p><?php echo $upcomingAppointments; ?></p>
    </div>

    <?php if ($nextAppointment){ ?>
      <div class="card highlight">
        <h3><?= $trans['next_appointment'] ?? 'Next Appointment'?></h3>
        <p><?= date("d/m/Y", strtotime($nextAppointment['appointment_date'])) . ' at ' . date("H:i", strtotime($nextAppointment['appointment_time'])) ?></p>
      </div>
    <?php } ?>

  </div>


  <br>
  <h3><?= $trans['appointment_history'] ?? 'Appointment History'?></h3>
  <table class="table">

    <thead>
      <th><?= $trans['date'] ?? 'Date'?></th>
      <th><?= $trans['time'] ?? 'Time'?></th>
      <th><?= $trans['status'] ?? 'Status'?></th>
    </thead>

    <?php foreach ($historyAppointments as $app): ?>
      <tr>
        <td><?= date("d/m/Y", strtotime($app['appointment_date'])) ?></td>
        <td><?= date("H:i", strtotime($app['appointment_time'])) ?></td>
        <td><?= $app['status'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  

  </div>


  </div>
  </div>
    


  <?php } elseif ($hrole === 'secretary') { 
    $stmt = $pdo->prepare("SELECT customer_id, appointment_date, appointment_time, status FROM appointments WHERE appointment_date <= CURDATE()
    ORDER BY appointment_date DESC, appointment_time DESC LIMIT 8");

    $stmt->execute();
    $historyAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
    $totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();


    $activeCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1")->fetchColumn();


    $activeMechanics = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mechanic' AND is_active = 1")->fetchColumn();


    $todayAppointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
    
    ?>


    <div class="content-area">

      
        <div class="dashboard-overview">
          <div class="card">
            <h3><?= $trans['total_appointments'] ?? 'Total Appointments'?></h3>
            <p><?= $totalAppointments ?></p>
          </div>
          <div class="card">
            <h3><?= $trans['active_customers'] ?? 'Active Customers'?></h3>
            <p><?= $activeCustomers ?></p>
          </div>
          <div class="card">
            <h3><?= $trans['active_mechanics'] ?? 'Active Mechanics'?></h3>
            <p><?= $activeMechanics ?></p>
          </div>
          <div class="card">
            <h3><?= $trans['appointments_today'] ?? 'Appointments today'?></h3>
            <p><?= $todayAppointments ?></p>
          </div>
        </div>

        
        
        <h3 class="title"><?= $trans['appointment_history'] ?? 'Appointment History'?></h3>
        <table class="table">
        
          <thead>
            <th><?= $trans['customer'] ?? 'Customer'?></th>
            <th><?= $trans['date'] ?? 'Date'?></th>
            <th><?= $trans['time'] ?? 'Time'?></th>
            <th><?= $trans['status'] ?? 'Status'?></th>
          </thead>

          <?php foreach ($historyAppointments as $app): ?>
            <tr>
              <td><?= $app['customer_id'] ?></td>
              <td><?= date("d/m/Y", strtotime($app['appointment_date'])) ?></td>
              <td><?= date("H:i", strtotime($app['appointment_time'])) ?></td>
              <td><?= $app['status'] ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
        

      

      
    </div>
  </div>

 <?php } ?>




</body>
</html>
