<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in
require_once '../include/header.php';
require_once '../include/db.php';

// Fetch user details from the database
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, email, balance FROM Users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $balance);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<h2>My Account</h2>

<p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
<p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
<p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
<p><strong>Current Balance:</strong> $<?php echo number_format($balance, 2); ?></p>

<?php
require_once '../include/footer.php';
?>
