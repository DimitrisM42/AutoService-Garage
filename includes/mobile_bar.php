
<div class="mobile-menu-container">
    
    <div class="mobile-menu-button-container">
        <?php if ($hrole === 'customer'): ?>
            <a href="dashboard.php" class="mobile-menu-button <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-columns"></i> <p><?= $trans['dashboard'] ?? 'Dashboard' ?></p>
            </a>
            <a href="my_appointments.php" class="mobile-menu-button <?= $current_page == 'my_appointments.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> <p><?= $trans['appointments'] ?? 'Appointments' ?></p>
            </a>
            <a href="my_cars.php" class="mobile-menu-button <?= $current_page == 'my_cars.php' ? 'active' : '' ?>">
            <i class="fas fa-car"></i> <p><?= $trans['cars'] ?? 'Cars' ?></p>
            </a>
            <a href="my_profile.php" class="mobile-menu-button <?= $current_page == 'my_profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i> <p><?= $trans['profile'] ?? 'Profile' ?></p>
            </a>

        <?php elseif ($hrole === 'mechanic'): ?>
            <a href="dashboard.php" class="mobile-menu-button <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-columns"></i> <p><?= $trans['dashboard'] ?? 'Dashboard' ?></p>
            </a>
            <a href="assigned_appointments.php" class="mobile-menu-button <?= $current_page == 'assigned_appointments.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> <p><?= $trans['appointments'] ?? 'Appointments' ?></p>
            </a>
            <a href="my_profile.php" class="mobile-menu-button <?= $current_page == 'my_profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i> <p><?= $trans['profile'] ?? 'Profile' ?></p>
            </a>

        <?php elseif ($hrole === 'secretary'): ?>
            <a href="dashboard.php" class="mobile-menu-button <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-columns"></i> <p><?= $trans['dashboard'] ?? 'Dashboard' ?></p>
            </a>
            <a href="manage_users.php" class="mobile-menu-button <?= $current_page == 'manage_users.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> <p><?= $trans['users'] ?? 'Users' ?></p>
            </a>
            <a href="manage_cars.php" class="mobile-menu-button <?= $current_page == 'manage_cars.php' ? 'active' : '' ?>">
            <i class="fas fa-car"></i> <p><?= $trans['cars'] ?? 'Cars' ?></p>
            </a>
            <a href="manage_appointments.php" class="mobile-menu-button <?= $current_page == 'manage_appointments.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> <p><?= $trans['appointments'] ?? 'Appointments' ?></p>
            </a>
            <a href="activate_accounts.php" class="mobile-menu-button <?= $current_page == 'activate_accounts.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice"></i> <p><?= $trans['accounts'] ?? 'Accounts' ?></p>
            </a>
        <?php endif; ?>
        </div>


    </div> 
  </div>
