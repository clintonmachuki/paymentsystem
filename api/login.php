<?php
require_once '../include/db.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Get POST data
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Check for empty fields
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields']);
    exit;
}

// Prepare and execute the SQL query
$stmt = $conn->prepare("SELECT user_id, password_hash FROM Users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];
    $stored_password_hash = $user['password_hash'];

    // Verify the password
    if (password_verify($password, $stored_password_hash)) {
        // Start a session and set session variables
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['logged_in'] = true;

        // Redirect to my_account.php
        header("Location: ../public/my_account.php");
        exit;  // Ensure that no further code is executed after the redirect
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}

$stmt->close();
$conn->close();
?>
