<?php
session_start();

function checkLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /PaymentSystem/public/login.php");
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: /PaymentSystem/public/login.php");
    exit();
}
?>
