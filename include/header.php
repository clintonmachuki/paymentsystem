<?php
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System</title>
    <link rel="stylesheet" href="/PaymentSystem/public/styles.css">
</head>
<body>
    <header>
        <h1>Payment System</h1>
        <nav>
            <ul>
                <li><a href="/PaymentSystem/public/register.php">Register</a></li>
                <li><a href="/PaymentSystem/public/login.php">Login</a></li>';
if (isset($_SESSION['user_id'])) {
    echo '
                <li><a href="/PaymentSystem/public/payment.php">Make a Payment</a></li>
                <li><a href="/PaymentSystem/public/my_account.php">My Account</a></li>
                <li><a href="/PaymentSystem/public/logout.php">Logout</a></li>';
}
echo '
            </ul>
        </nav>
    </header>
    <main>
';
?>
