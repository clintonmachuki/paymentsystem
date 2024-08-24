<?php
require_once '../include/security.php';
checkLoggedIn();
require_once '../include/header.php';
?>

<form action="/PaymentSystem/api/payment.php" method="POST">
    <label for="receiver_id">Receiver's User ID:</label><br>
    <input type="text" id="receiver_id" name="receiver_id" required><br>
    <label for="amount">Amount:</label><br>
    <input type="number" id="amount" name="amount" step="0.01" required><br>
    <input type="submit" value="Send Payment">
</form>

<?php
require_once '../include/footer.php';
?>
