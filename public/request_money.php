<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in

require_once '../include/header.php';
require_once '../include/db.php';

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Fetch pending money requests
$stmt = $conn->prepare("
    SELECT 
        r.request_id,
        r.sender_id,
        r.amount,
        r.request_date,
        u.username AS sender_username
    FROM MoneyRequests r
    JOIN Users u ON r.sender_id = u.user_id
    WHERE r.requester_id = ? AND r.status = 'pending'
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$pending_requests = $stmt->get_result();
$stmt->close();

// Fetch completed money requests
$stmt = $conn->prepare("
    SELECT 
        r.request_id,
        r.sender_id,
        r.amount,
        r.request_date,
        u.username AS sender_username
    FROM MoneyRequests r
    JOIN Users u ON r.sender_id = u.user_id
    WHERE r.requester_id = ? AND r.status = 'completed'
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$completed_requests = $stmt->get_result();
$stmt->close();

// Fetch canceled money requests
$stmt = $conn->prepare("
    SELECT 
        r.request_id,
        r.sender_id,
        r.amount,
        r.request_date,
        u.username AS sender_username
    FROM MoneyRequests r
    JOIN Users u ON r.sender_id = u.user_id
    WHERE r.requester_id = ? AND r.status = 'cancelled'
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$canceled_requests = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<h2>Request Money</h2>

<form id="request-money-form">
    <label for="sender_id">Sender ID:</label>
    <input type="text" id="sender_id" name="sender_id" required>

    <label for="amount">Amount:</label>
    <input type="number" id="amount" name="amount" step="0.01" required>

    <button type="submit">Request Money</button>
</form>

<div id="response-message"></div>

<!-- Display Pending Requests -->
<h3>Pending Requests</h3>
<table>
    <thead>
        <tr>
            <th>Request Date</th>
            <th>Sender</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $pending_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                <td><?php echo htmlspecialchars($row['sender_username']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Display Completed Requests -->
<h3>Completed Requests</h3>
<table>
    <thead>
        <tr>
            <th>Request Date</th>
            <th>Sender</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $completed_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                <td><?php echo htmlspecialchars($row['sender_username']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Display Canceled Requests -->
<h3>Canceled Requests</h3>
<table>
    <thead>
        <tr>
            <th>Request Date</th>
            <th>Sender</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $canceled_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                <td><?php echo htmlspecialchars($row['sender_username']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>



<script>
    document.getElementById('request-money-form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        const formData = new FormData(this);

        fetch('../api/request_money.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const responseMessage = document.getElementById('response-message');
            responseMessage.textContent = data.message;
            responseMessage.style.color = data.status === 'success' ? 'green' : 'red';

            if (data.status === 'success') {
                // Refresh the page on success
                window.location.reload();
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
