<?php
require_once '../include/header.php';
require_once '../include/db.php';

?>

<form action="../api/register.php" method="POST">
    <label for="username">Username:</label><br>
    <input type="text" id="username" name="username" required><br>
    <label for="email">Email:</label><br>
    <input type="email" id="email" name="email" required><br>
    <label for="password">Password:</label><br>
    <input type="password" id="password" name="password" required><br>
    <input type="submit" value="Register">
</form>

<?php
require_once '../include/footer.php';
?>
