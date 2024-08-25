<?php
require_once '../include/db.php';
require_once '../include/security.php';

// Ensure the user is logged in
checkLoggedIn();

// Fetch user settings
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT theme FROM UserSettings WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($theme);
$stmt->fetch();
$stmt->close();

// Set the default theme if not set
if (empty($theme)) {
    $theme = 'light'; // Default to light theme
}

// Determine the stylesheet based on the theme
$theme_css = ($theme === 'dark') ? 'styles2.css' : 'styles.css';

echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System</title>
    <link id="theme-stylesheet" rel="stylesheet" href="/PaymentSystem/public/' . htmlspecialchars($theme_css, ENT_QUOTES, 'UTF-8') . '">
</head>
<body>
    <header>
        <h1>Payment System</h1>
        <nav>
            <ul>';

if (isset($_SESSION['user_id'])) {
    // User is logged in, show these links
    echo '
                <li><a href="/PaymentSystem/public/payment.php">Send Money</a></li>
                <li><a href="/PaymentSystem/public/my_account.php">My Account</a></li>
                <li><a href="/PaymentSystem/public/add_balance.php">Add Balance</a></li>
                <li><a href="/PaymentSystem/public/transaction_history.php">Transaction History</a></li>
                <li><a href="/PaymentSystem/public/request_money.php">Request Money</a></li>
                <li><a href="/PaymentSystem/public/money_requests.php">My Money Requests</a></li>
                <li><a href="/PaymentSystem/public/help.php">Help</a></li>
                <li><a href="/PaymentSystem/public/settings.php">Settings</a></li>
                <li><a href="/PaymentSystem/public/logout.php">Logout</a></li>';
} else {
    // User is not logged in, show these links
    echo '
                <li><a href="/PaymentSystem/public/login.php">Login</a></li>
                <li><a href="/PaymentSystem/public/register.php">Register</a></li>';
}

echo '
            </ul>
        </nav>
    </header>
    <main>
';
?>
