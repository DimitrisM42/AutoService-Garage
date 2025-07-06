<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['el', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['el', 'en'])) {
    $lang = $_SESSION['lang'];
    $lang_file = __DIR__ . "/lang/$lang.php";
    
    if (file_exists($lang_file)) {
        $trans = require $lang_file;
    } else {
        $trans = [];
    }
} else {
    $trans = [];
}
