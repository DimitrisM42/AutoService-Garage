<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once 'langs.php';
require 'config.php';
require_once 'functions.php';


$session_timeout = 30 * 60; //------------------------------------------------------------------

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

$hrole = $_SESSION['role'];
$hname = $_SESSION['full_name'];
$current_page = basename($_SERVER['PHP_SELF']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  
</head>
<body>
    <div class="header-container">
        <h1>AutoService Garage</h1>
        
        
        <div style="display: flex; flex-direction: row; gap: 18px; ">
          <div class="toggle-container">
            <input type="checkbox" id="dark-mode-toggle">
            <label for="dark-mode-toggle" class="toggle-label">
              <i class="fas fa-sun"></i>
              <i class="fas fa-moon"></i>
              <div class="ball"></div>
            </label>
          </div>

          <div style="display: flex; flex-direction: row; gap: 5px; font-size: 20px;">
            <a href="?lang=en" style="text-decoration: none;"><img src="images/united-kingdom.png" height="25px"></a>
             | 
            <a href="?lang=el" style="text-decoration: none;"><img src="images/greece.png" height="25px"></a>
          </div>
        </div> 

        <a href="logout.php" class="mobile-logout-button"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>


    <script>
      const toggle = document.getElementById('dark-mode-toggle');
      const body = document.body;

      if (localStorage.getItem('theme') === 'dark') {
        body.classList.add('dark-mode');
        toggle.checked = true;
      }

      toggle.addEventListener('change', () => {
        if (toggle.checked) {
          body.classList.add('dark-mode');
          localStorage.setItem('theme', 'dark');
        } else {
          body.classList.remove('dark-mode');
          localStorage.setItem('theme', 'light');
        }
      });
    </script>

