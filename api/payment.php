<?php
require_once '../include/db.php';
require_once '../include/security.php';

// Ensure the user is logged in
checkLoggedIn();

// Get POST data
$receiver_id = $_POST['receiver_id'] ?? '';
$amount = $_POST['amount'] ?? '';

// Validate input
if (empty($receiver_id) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$sender_id = $_SESSION['user_id']; // Logged-in user ID

if ($receiver_id === $sender_id) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot send money to yourself']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Fetch sender's and receiver's balances
    $stmt = $conn->prepare("SELECT user_id, balance FROM Users WHERE user_id = ? OR user_id = ?");
    $stmt->bind_param("ss", $sender_id, $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize an array to store balances
    $balances = [];
    
    while ($row = $result->fetch_assoc()) {
        $balances[$row['user_id']] = $row['balance'];
    }
    $stmt->close();

    // Ensure both user IDs are in the result
    if (!isset($balances[$sender_id]) || !isset($balances[$receiver_id])) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'User(s) not found']);
        exit;
    }

    // Check if the sender has enough balance
    if ($balances[$sender_id] < $amount) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Insufficient balance']);
        exit;
    }

    // Update balances
    $stmt = $conn->prepare("UPDATE Users SET balance = balance - ? WHERE user_id = ?");
    $stmt->bind_param("ds", $amount, $sender_id);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("UPDATE Users SET balance = balance + ? WHERE user_id = ?");
    $stmt->bind_param("ds", $amount, $receiver_id);
    $stmt->execute();
    $stmt->close();

    // Record the transaction
    $transaction_id = bin2hex(random_bytes(10)); // Generate a random transaction ID
    $stmt = $conn->prepare("INSERT INTO Transactions (transaction_id, sender_id, receiver_id, amount, transaction_type) VALUES (?, ?, ?, ?, 'debit')");
    $stmt->bind_param("ssss", $transaction_id, $sender_id, $receiver_id, $amount);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Payment processed successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
}

$conn->close();
?>
