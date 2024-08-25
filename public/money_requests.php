<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in

require_once '../include/header.php';
require_once '../include/db.php';

$user_id = $_SESSION['user_id'];

// Fetch pending money requests
$stmt = $conn->prepare("
    SELECT 
        r.request_id,
        r.requester_id,
        r.amount,
        r.request_date,
        u.username AS requester_username
    FROM MoneyRequests r
    JOIN Users u ON r.requester_id = u.user_id
    WHERE r.sender_id = ? AND r.status = 'pending'
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$pending_requests = $stmt->get_result();
$stmt->close();

// Fetch completed money requests
$stmt = $conn->prepare("
    SELECT 
        r.request_id,
        r.requester_id,
        r.amount,
        r.request_date,
        u.username AS requester_username
    FROM MoneyRequests r
    JOIN Users u ON r.requester_id = u.user_id
    WHERE r.sender_id = ? AND r.status = 'completed'
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$completed_requests = $stmt->get_result();
$stmt->close();

// Fetch canceled money requests
$stmt = $conn->prepare("
    SELECT 
        r.request_id,
        r.requester_id,
        r.amount,
        r.request_date,
        u.username AS requester_username
    FROM MoneyRequests r
    JOIN Users u ON r.requester_id = u.user_id
    WHERE r.sender_id = ? AND r.status = 'cancelled'
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$canceled_requests = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<h2>Money Requests</h2>

<h3>Pending Requests</h3>
<table>
    <thead>
        <tr>
            <th>Request Date</th>
            <th>Requester</th>
            <th>Amount</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $pending_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                <td><?php echo htmlspecialchars($row['requester_username']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
                <td>
                    <button class="complete-request" data-request-id="<?php echo htmlspecialchars($row['request_id']); ?>">Complete Request</button>
                    <button class="cancel-request" data-request-id="<?php echo htmlspecialchars($row['request_id']); ?>">Cancel Request</button>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<h3>Completed Requests</h3>
<table>
    <thead>
        <tr>
            <th>Request Date</th>
            <th>Requester</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $completed_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                <td><?php echo htmlspecialchars($row['requester_username']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<h3>Canceled Requests</h3>
<table>
    <thead>
        <tr>
            <th>Request Date</th>
            <th>Requester</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $canceled_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                <td><?php echo htmlspecialchars($row['requester_username']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div id="response-message"></div>

<script>
    // Handle complete request button
    document.querySelectorAll('.complete-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.getAttribute('data-request-id');

            fetch('../api/complete_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ request_id: requestId }),
            })
            .then(response => response.json())
            .then(data => {
                const responseMessage = document.getElementById('response-message');
                responseMessage.textContent = data.message;
                responseMessage.style.color = data.status === 'success' ? 'green' : 'red';

                if (data.status === 'success') {
                    // Reload the page to refresh the request lists
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
    });

    // Handle cancel request button
    document.querySelectorAll('.cancel-request').forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.getAttribute('data-request-id');

            fetch('../api/cancel_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ request_id: requestId }),
            })
            .then(response => response.json())
            .then(data => {
                const responseMessage = document.getElementById('response-message');
                responseMessage.textContent = data.message;
                responseMessage.style.color = data.status === 'success' ? 'green' : 'red';

                if (data.status === 'success') {
                    // Reload the page to refresh the request lists
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
    });
</script>

