<?php
require_once '../include/header.php';
require_once '../include/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $user_id = str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO Users (user_id, username, password, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user_id, $username, $password, $email);

    if ($stmt->execute()) {
        echo "Registration successful. Your User ID is: " . $user_id;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<form action="register.php" method="POST">
    <label for="username">Username:</label><br>
    <input type="text" id="username" name="username" required><br>
    <label for="email">Email:</label><br>
    <input type="email" id="email" name="email" required><br>
    <label for="password">Password:</label><br>
    <input type="password" id="password" name="password" required><br>
    <input type="submit" value="Register">
</form>

<?php
require_once '../include/footer.php';
?>
