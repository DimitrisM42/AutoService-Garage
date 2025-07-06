<div class="sidebar-container">
  <div class="sidebar-profile-container">
    <img src="images/user.png" height="50px">
    <h2><?php echo htmlspecialchars($hname); ?></h2>
    <h4><?php echo strtoupper($hrole); ?></h4>
  </div>

  <div class="sidebar-button-container">
    <?php if ($hrole === 'customer'): ?>
      <a href="dashboard.php" class="sidebar-button <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-table-columns"></i> <?= $trans['dashboard'] ?? 'Dashboard' ?>
      </a>
      <a href="my_appointments.php" class="sidebar-button <?= $current_page == 'my_appointments.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i> <?= $trans['my_appointments'] ?? 'My Appointments' ?>
      </a>
      <a href="my_cars.php" class="sidebar-button <?= $current_page == 'my_cars.php' ? 'active' : '' ?>">
        <i class="fas fa-car"></i> <?= $trans['my_cars'] ?? 'My Cars' ?>
      </a>
      <a href="my_profile.php" class="sidebar-button <?= $current_page == 'my_profile.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user"></i> <?= $trans['my_profile'] ?? 'My Profile' ?>
      </a>

    <?php elseif ($hrole === 'mechanic'): ?>
      <a href="dashboard.php" class="sidebar-button <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-table-columns"></i> <?= $trans['dashboard'] ?? 'Dashboard' ?>
      </a>
      <a href="assigned_appointments.php" class="sidebar-button <?= $current_page == 'assigned_appointments.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i> <?= $trans['appointments'] ?? 'Appointments' ?>
      </a>
      <a href="my_profile.php" class="sidebar-button <?= $current_page == 'my_profile.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-user"></i> <?= $trans['my_profile'] ?? 'My Profile' ?>
      </a>

    <?php elseif ($hrole === 'secretary'): ?>
      <a href="dashboard.php" class="sidebar-button <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-table-columns"></i> <?= $trans['dashboard'] ?? 'Dashboard' ?>
      </a>
      <a href="manage_users.php" class="sidebar-button <?= $current_page == 'manage_users.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i> <?= $trans['manage_users'] ?? 'Manage Users' ?>
      </a>
      <a href="manage_cars.php" class="sidebar-button <?= $current_page == 'manage_cars.php' ? 'active' : '' ?>">
        <i class="fas fa-car"></i> <?= $trans['manage_cars'] ?? 'Manage Cars' ?>
      </a>
      <a href="manage_appointments.php" class="sidebar-button <?= $current_page == 'manage_appointments.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i> <?= $trans['appointments'] ?? 'Appointments' ?>
      </a>
      <a href="activate_accounts.php" class="sidebar-button <?= $current_page == 'activate_accounts.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-file-invoice"></i> <?= $trans['activate_accounts'] ?? 'Activate Accounts' ?>
      </a>
    <?php endif; ?>

    <a href="logout.php" class="sidebar-button logout-button">
      <i class="fa-solid fa-right-from-bracket"></i> <?= $trans['logout'] ?? 'Logout' ?>
    </a>
  </div>
</div>
