<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in
require_once '../include/header.php';
require_once '../include/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];

    if ($amount <= 0) {
        echo "Amount must be greater than zero.";
    } else {
        $conn->begin_transaction();

        try {
            // Update user balance
            $stmt = $conn->prepare("UPDATE Users SET balance = balance + ? WHERE user_id = ?");
            $stmt->bind_param("ds", $amount, $user_id);
            $stmt->execute();
            $stmt->close();

            // Record the transaction
            $stmt = $conn->prepare("INSERT INTO Transactions (sender_id, receiver_id, amount, transaction_type) VALUES (?, ?, ?, 'credit')");
            $stmt->bind_param("ssd", $user_id, $user_id, $amount);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo "Balance added successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            echo "Failed to add balance: " . $e->getMessage();
        }

        $conn->close();
    }
}
?>

<h2>Add Balance</h2>

<form action="add_balance.php" method="POST">
    <label for="amount">Amount:</label><br>
    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required><br>
    <input type="submit" value="Add Balance">
</form>

<?php
require_once '../include/footer.php';
?>
