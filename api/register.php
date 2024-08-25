<?php
require_once '../include/db.php';

// Get POST data
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields']);
    exit;
}

// Hash the password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Check if the username already exists
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
    exit;
}

// Check if the email already exists
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM Users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    exit;
}

// Insert user into database
$stmt = $conn->prepare("INSERT INTO Users (user_id, username, email, password_hash) VALUES (?, ?, ?, ?)");
$user_id = bin2hex(random_bytes(5)); // Generate a random user ID
$stmt->bind_param("ssss", $user_id, $username, $email, $password_hash);

if ($stmt->execute()) {
    // Redirect to login page
    header("Location: ../public/login.php");
    exit;  // Ensure that no further code is executed
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
