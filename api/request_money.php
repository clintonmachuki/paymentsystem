<?php
require_once '../include/db.php';
require_once '../include/security.php';

// Ensure the user is logged in
checkLoggedIn();

// Get POST data
$sender_id = $_POST['sender_id'] ?? '';
$amount = $_POST['amount'] ?? '';

// Validate input
if (empty($sender_id) || empty($amount) || !is_numeric($amount) || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$requester_id = $_SESSION['user_id']; // Logged-in user ID

// Check if the sender exists
$stmt = $conn->prepare("SELECT user_id FROM Users WHERE user_id = ?");
$stmt->bind_param("s", $sender_id);
$stmt->execute();
$result = $stmt->get_result();
$sender = $result->fetch_assoc();
$stmt->close();

if (!$sender) {
    echo json_encode(['status' => 'error', 'message' => 'Sender not found']);
    exit;
}

// Insert the request into the database
$request_id = bin2hex(random_bytes(10)); // Generate a random request ID
$stmt = $conn->prepare("INSERT INTO MoneyRequests (request_id, requester_id, sender_id, amount, request_date) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("ssss", $request_id, $requester_id, $sender_id, $amount);
$stmt->execute();
$stmt->close();

$conn->close();

echo json_encode(['status' => 'success', 'message' => 'Money request sent successfully']);
?>
