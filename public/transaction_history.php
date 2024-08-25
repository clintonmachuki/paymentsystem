<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in

require_once '../include/header.php';
require_once '../include/db.php';

// Fetch user transactions from the database with usernames
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        t.transaction_id, 
        t.sender_id, 
        t.receiver_id, 
        t.amount, 
        t.transaction_date, 
        u1.username AS sender_username,
        u2.username AS receiver_username
    FROM Transactions t
    LEFT JOIN Users u1 ON t.sender_id = u1.user_id
    LEFT JOIN Users u2 ON t.receiver_id = u2.user_id
    WHERE t.sender_id = ? OR t.receiver_id = ?
    ORDER BY t.transaction_date DESC
");
$stmt->bind_param("ss", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<h2>Transaction History</h2>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Amount</th>
            <th>From/To</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr onclick="window.location.href='transaction_detail.php?id=<?php echo htmlspecialchars($row['transaction_id'], ENT_QUOTES, 'UTF-8'); ?>'">
                <td><?php echo htmlspecialchars($row['transaction_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php
                    // Determine whether it's a credit or debit
                    if ($row['receiver_id'] == $user_id) {
                        echo '<span class="transaction-type deposit">&#9650;</span>';  // Green arrow up
                    } else {
                        echo '<span class="transaction-type withdrawal">&#9660;</span>';  // Red arrow down
                    }
                    ?>
                </td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
                <td>
                    <?php
                    // Display the relevant username based on whether the user is the sender or receiver
                    if ($row['sender_id'] == $user_id) {
                        echo 'To: ' . htmlspecialchars($row['receiver_username'], ENT_QUOTES, 'UTF-8');
                    } else {
                        echo 'From: ' . htmlspecialchars($row['sender_username'], ENT_QUOTES, 'UTF-8');
                    }
                    ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php
require_once '../include/footer.php';
?>
