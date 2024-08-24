<?php
require_once '../include/header.php';
?>

<form action="/PaymentSystem/api/login.php" method="POST">
    <label for="username">Username:</label><br>
    <input type="text" id="username" name="username" required><br>
    <label for="password">Password:</label><br>
    <input type="password" id="password" name="password" required><br>
    <input type="submit" value="Login">
</form>

<?php
require_once '../include/footer.php';
?>
