<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in

require_once '../include/header.php';
require_once '../include/db.php'; // Ensure you include the database connection

// Fetch user settings
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT theme FROM UserSettings WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($theme);
$stmt->fetch();
$stmt->close();
$conn->close(); // Close the connection after the query

// Set the default theme if not set
if (empty($theme)) {
    $theme = 'light'; // Default to light theme
}
?>

<h2>Settings</h2>

<form id="settings-form">
    <label for="theme">Select Theme:</label>
    <select id="theme" name="theme">
        <option value="light" <?php echo $theme === 'light' ? 'selected' : ''; ?>>Light Mode</option>
        <option value="dark" <?php echo $theme === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
    </select>
    <button type="submit">Save Settings</button>
</form>

<div id="response-message"></div>

<script>
document.getElementById('settings-form').addEventListener('submit', function(event) {
    event.preventDefault();

    const theme = document.getElementById('theme').value;

    fetch('../api/save_settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ theme: theme }),
    })
    .then(response => response.json())
    .then(data => {
        const responseMessage = document.getElementById('response-message');
        responseMessage.textContent = data.message;
        responseMessage.style.color = data.status === 'success' ? 'green' : 'red';

        // Optionally reload the page to apply changes
        if (data.status === 'success') {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const responseMessage = document.getElementById('response-message');
        responseMessage.textContent = 'An error occurred. Please try again.';
        responseMessage.style.color = 'red';
    });
});
</script>

<?php
require_once '../include/footer.php';
?>
