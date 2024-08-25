<?php
require_once '../include/db.php';
require_once '../include/security.php';

// Ensure the user is logged in
checkLoggedIn();

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'] ?? '';

if (empty($request_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id']; // Logged-in user ID

// Fetch the request details
$stmt = $conn->prepare("
    SELECT 
        r.requester_id,
        r.amount
    FROM MoneyRequests r
    WHERE r.request_id = ? AND r.sender_id = ? AND r.status = 'pending'
");
$stmt->bind_param("ss", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    echo json_encode(['status' => 'error', 'message' => 'Request not found or already processed']);
    exit;
}

$requester_id = $request['requester_id'];
$amount = $request['amount'];

// Check if the sender has sufficient balance
$stmt = $conn->prepare("SELECT balance FROM Users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user['balance'] < $amount) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient balance']);
    exit;
}

// Process the transaction
$conn->begin_transaction();

try {
    // Deduct the amount from the sender
    $stmt = $conn->prepare("UPDATE Users SET balance = balance - ? WHERE user_id = ?");
    $stmt->bind_param("ds", $amount, $user_id);
    $stmt->execute();
    $stmt->close();

    // Credit the amount to the requester
    $stmt = $conn->prepare("UPDATE Users SET balance = balance + ? WHERE user_id = ?");
    $stmt->bind_param("ds", $amount, $requester_id);
    $stmt->execute();
    $stmt->close();

    // Mark the request as completed
    $stmt = $conn->prepare("UPDATE MoneyRequests SET status = 'completed' WHERE request_id = ?");
    $stmt->bind_param("s", $request_id);
    $stmt->execute();
    $stmt->close();

    // Generate a unique transaction ID
    $transaction_id = bin2hex(random_bytes(10)); // Generate a random transaction ID

    // Record the transaction for the debit (sender)
    $stmt = $conn->prepare("
        INSERT INTO Transactions (transaction_id, sender_id, receiver_id, amount, transaction_date)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssss", $transaction_id, $user_id, $requester_id, $amount);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Request completed successfully']);
} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed']);
}

$conn->close();
?>
