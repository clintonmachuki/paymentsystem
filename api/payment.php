<?php
require_once '../include/db.php';
require_once '../include/security.php';

checkLoggedIn();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $amount = $_POST['amount'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT balance FROM Users WHERE user_id = ?");
        $stmt->bind_param("s", $sender_id);
        $stmt->execute();
        $stmt->bind_result($balance);
        $stmt->fetch();
        $stmt->close();

        if ($balance < $amount) {
            throw new Exception("Insufficient balance.");
        }

        $stmt = $conn->prepare("UPDATE Users SET balance = balance - ? WHERE user_id = ?");
        $stmt->bind_param("ds", $amount, $sender_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE Users SET balance = balance + ? WHERE user_id = ?");
        $stmt->bind_param("ds", $amount, $receiver_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO Transactions (sender_id, receiver_id, amount) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $sender_id, $receiver_id, $amount);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo "Payment successful.";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Payment failed: " . $e->getMessage();
    }

    $conn->close();
}
?>
