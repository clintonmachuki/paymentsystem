<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in
require_once '../include/header.php';
require_once '../include/db.php';

// Get the transaction ID from the query string
$transaction_id = $_GET['id'] ?? null;

if ($transaction_id) {
    // Fetch the transaction details from the database
    $stmt = $conn->prepare("
        SELECT 
            t.sender_id, 
            t.receiver_id, 
            t.amount, 
            t.transaction_type, 
            t.transaction_date, 
            u1.username AS sender_username,
            u2.username AS receiver_username
        FROM Transactions t
        LEFT JOIN Users u1 ON t.sender_id = u1.user_id
        LEFT JOIN Users u2 ON t.receiver_id = u2.user_id
        WHERE t.transaction_id = ?
    ");
    $stmt->bind_param("s", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $transaction = $result->fetch_assoc();
        $stmt->close();
    } else {
        echo "Transaction not found.";
        $conn->close();
        require_once '../include/footer.php';
        exit;
    }
} else {
    echo "Invalid transaction ID.";
    require_once '../include/footer.php';
    exit;
}

$conn->close();
?>

<h2>Transaction Details</h2>

<table>
    <tr>
        <th>Date:</th>
        <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
    </tr>
    <tr>
        <th>Type:</th>
        <td>
            <?php
            if ($transaction['transaction_type'] == 'credit') {
                echo '<span class="transaction-type deposit">&#9650; Deposit</span>';  // Green arrow up
            } else {
                echo '<span class="transaction-type withdrawal">&#9660; Withdrawal</span>';  // Red arrow down
            }
            ?>
        </td>
    </tr>
    <tr>
        <th>Amount:</th>
        <td><?php echo number_format($transaction['amount'], 2); ?></td>
    </tr>
    <tr>
        <th>From:</th>
        <td><?php echo htmlspecialchars($transaction['sender_username']); ?></td>
    </tr>
    <tr>
        <th>To:</th>
        <td><?php echo htmlspecialchars($transaction['receiver_username']); ?></td>
    </tr>
</table>

<?php
require_once '../include/footer.php';
?>
