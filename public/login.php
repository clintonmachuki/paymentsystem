<?php
require_once '../include/header.php';
?>

<form action="../api/login.php" method="POST">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    
    <button type="submit">Login</button>
</form>


<?php
require_once '../include/footer.php';
?>
