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
        requester_id
    FROM MoneyRequests
    WHERE request_id = ? AND sender_id = ? AND status = 'pending'
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

// Cancel the request
$stmt = $conn->prepare("UPDATE MoneyRequests SET status = 'cancelled' WHERE request_id = ?");
$stmt->bind_param("s", $request_id);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success', 'message' => 'Request cancelled successfully']);

$conn->close();
?>
