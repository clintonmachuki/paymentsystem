<?php
require_once '../include/db.php';
require_once '../include/security.php';

// Ensure the user is logged in
checkLoggedIn();

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'] ?? 'light'; // Default to light if not set

$user_id = $_SESSION['user_id'];

// Validate theme
if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid theme']);
    exit;
}

// Update or insert user settings
$stmt = $conn->prepare("
    INSERT INTO UserSettings (user_id, theme)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE theme = ?
");
$stmt->bind_param("sss", $user_id, $theme, $theme);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully']);
?>
