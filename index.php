<?php
session_start();

require_once 'langs.php';

if (isset($_SESSION['user_id']) && ($_SESSION['role'] === 'secretary' || $_SESSION['role'] === 'mechanic')) {
  header("Location: dashboard.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoService Garage</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  </head>
  <body>
    <nav class="navbar">
      <img src="images/AutoServiceGarageLogo.png" width="40px" id="logo">
      <ul>
        <li><a href="#home"><?= $trans['home'] ?? 'Home' ?></a></li>
        <li><a href="#services"><?= $trans['services'] ?? 'Services' ?></a></li>
        <li><a href="#about"><?= $trans['about_us'] ?? 'About Us' ?></a></li>

      </ul>
      
        <?php
        if (isset($_SESSION['user_id'])) { ?>
          <div class="profile-tab" onclick="toggleDropdown()">
          <img src="images/user.png" width="35px" style="border-radius: 50%; margin-right: 10px">
          <?php echo $_SESSION['full_name']; 
          
        }
        else { ?>
          <div class="profile-tab" onclick="gotoLogin()">
            <img src="images/user.png" width="35px" style="border-radius: 50%; margin-right: 10px">
            <a><?= $trans['login_register'] ?? 'Login/Register' ?></a>
          </div>
        <?php } ?>

      </div>
    </nav>

    <div class="menu-icon" onclick="toggleMobileMenu()">
    <i class="fa-solid fa-bars"></i>
    </div>
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
      <a href="#home" onclick="toggleMobileMenu()"><?= $trans['home'] ?? 'Home' ?></a>
      <a href="#services" onclick="toggleMobileMenu()"><?= $trans['services'] ?? 'Services' ?></a>
      <a href="#about" onclick="toggleMobileMenu()"><?= $trans['about_us'] ?? 'About Us' ?></a>

      <?php
        if (isset($_SESSION['user_id'])) { ?>
          <div class="mobile-profile-tab">
          <img src="images/user.png" width="35px" style="border-radius: 50%;">
          <?php echo $_SESSION['full_name'];?> 
          <div class="mobile-dropdown-menu">
            <a href="dashboard.php"><?= $trans['dashboard'] ?? 'Dashboard' ?></a>
            <a href="logout.php"><?= $trans['logout'] ?? 'Logout' ?></a>

          </div>
          </div>
          

          
        <?php }
        else { ?>
          <div class="mobile-profile-tab" onclick="gotoLogin()">
          <img src="images/user.png" width="35px" style="border-radius: 50%;">
          <a><?= $trans['login_register'] ?? 'Login/Register' ?></a>
          </div>
        <?php } ?> 
    </div>

    <?php
      if (isset($_SESSION['user_id'])) { ?>

        <div id="dropdown-menu" class="dropdown-menu">
          <a href="dashboard.php"><?= $trans['dashboard'] ?? 'Dashboard' ?></a>
          <a href="logout.php"><?= $trans['logout'] ?? 'Logout' ?></a>
        </div>

    <?php } ?>


    <!-- Home -->

    <section id="home" class="home-section">
      <div class="appearable-op-left">
        <h1>AUTOSERVICE GARAGE</h1>
        <p><?= $trans['home_intro'] ?? 'A streamlined solution for scheduling and managing your vehicle’s 
                                        maintenance and repair appointments ensuring reliable service and
                                         optimal performance with minimal hassle.' ?></p>

          <?php
            if (isset($_SESSION['user_id'])) {
              echo '<a href="my_appointments.php"><button>' . ($trans['book_appointment'] ?? 'BOOK APPOINTMENT') . '</button></a>';
            } else {
              echo '<a href="register.php"><button>' . ($trans['book_appointment'] ?? 'BOOK APPOINTMENT') . '</button></a>';
            }
          ?>

      </div>
    </section>  

    <!-- Services -->

    <section id="services" class="services-section">
      
      <div class="container-column">
        <h1 class="title"><?= $trans['services'] ?? 'Services' ?></h1>
          
        <div id="cards">
            <div class="card">
                <img src="images/repair-icon.png" width="300px" height="164px">
                <h1><?= $trans['repair'] ?? 'Repair' ?></h1>
            </div>
            <div class="card">
                <img src="images/service-icon.png" width="300px" height="164px">
                <h1><?= $trans['service'] ?? 'Service' ?></h1>
            </div>
            
             
        </div>
      </div>
    </section>

  <!-- About Us -->

  <section id="about" class="about-section">
    <div>
      <h1 class="title"><?= $trans['about_us'] ?? 'About Us' ?></h1>
      <p><?= $trans['about_paragraph_1'] ?? 'We are a dedicated team of experienced professionals committed to delivering high-quality
        vehicle maintenance and repair services. With a focus on reliability, efficiency, and customer satisfaction, 
        we strive to ensure every vehicle we service performs at its best. 
        Our goal is to provide a seamless experience built on trust, transparency, and technical expertise.' ?></p>
      <p><?= $trans['about_paragraph_2'] ?? 'Over the years, our workshop has evolved into a fully equipped facility, capable of handling a wide range of mechanical needs 
        from regular maintenance to complex custom fabrications. We continuously invest in advanced tools and technology 
        to stay ahead in a demanding industry.' ?></p>

      <p><?= $trans['about_heading_unique'] ?? 'What sets us apart:' ?></p>
      <ul>
        <li><?= $trans['adv_1'] ?? 'Highly trained technicians with hands-on experience.' ?></li>
        <li><?= $trans['adv_2'] ?? 'Modern diagnostic and machining equipment.' ?></li>
        <li><?= $trans['adv_3'] ?? 'Honest communication and transparent pricing.' ?></li>
        <li><?= $trans['adv_4'] ?? 'Fast, reliable turnaround times.' ?></li>
        <li><?= $trans['adv_5'] ?? 'Personalized service tailored to each customer’s needs.' ?></li>
      </ul>

      <p><?= $trans['about_paragraph_3'] ?? 'Whether you need precision machining, part fabrication, or full-scale mechanical repairs, 
        we are here to deliver results you can rely on every time.' ?></p>
    </div>
  </section>

<footer class="site-footer">
  <div class="footer-content">
    <p>&copy; 2025 AutoService Garage. All rights reserved.</p>
    <div class="social-links">
      <a href="https://www.facebook.com">
        <i class="fa-brands fa-facebook"></i>
      </a>
      <a href="https://www.instagram.com">
        <i class="fa-brands fa-instagram"></i>
      </a>
      <a href="https://www.twitter.com">
        <i class="fa-brands fa-x-twitter" style="color:white;"></i>
      </a>
    </div>
    <div style="display: flex; flex-direction: row; gap: 5px; font-size: 20px;">
      <a href="?lang=en" style="text-decoration: none;"><img src="images/united-kingdom.png" height="25px"></a>
        | 
      <a href="?lang=el" style="text-decoration: none;"><img src="images/greece.png" height="25px"></a>
    </div>
  </div>
</footer>



  <script>
    function toggleDropdown() {
      const menu = document.getElementById('dropdown-menu');
      menu.classList.toggle('show');
    }

    document.addEventListener('click', function(event) {
      const profileTab = document.querySelector('.profile-tab');
      const menu = document.getElementById('dropdown-menu');
      if (!profileTab.contains(event.target)) {
        menu.classList.remove('show');
      }
    });

    function gotoLogin() {
      window.location.href = 'login.php';
    }

    const handleOnMouseMove = e => {
      const { currentTarget: target } = e;

      const rect = target.getBoundingClientRect(),
          x = e.clientX - rect.left,
          y = e.clientY - rect.top;

          target.style.setProperty("--mouse-x", `${x}px`);
          target.style.setProperty("--mouse-y", `${y}px`);
    }

    for (const card of document.querySelectorAll(".card")) {
      card.onmousemove = e => handleOnMouseMove(e);
    }


    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const menuElements = document.querySelectorAll('#mobileMenu a');
        if (menu.style.opacity == 1) {
            menu.style.pointerEvents = 'none';
            menuElements.forEach(element => {
              element.style.pointerEvents = 'none';
            });
            menu.style.opacity = 0;
            menu.style.transform = 'translateX(-20px)';
        } else {
            menu.style.pointerEvents = 'auto';
            menuElements.forEach(element => {
              element.style.pointerEvents = 'auto';
            });
            menu.style.opacity = 1;
            menu.style.transform = 'translateX(0px)';
        }
    }

  </script>

  </body>
</html>
