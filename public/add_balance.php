<?php
require_once '../include/security.php';
require_once '../include/header.php';
require_once '../include/db.php';
require_once '../api/mpesa_express.php';  // Import the MPesa Express function

checkLoggedIn();  // Ensure the user is logged in

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];

    // Validate the amount
    if ($amount <= 0) {
        echo "Amount must be greater than zero.";
    } else {
        // Fetch user's phone number from the database
        $stmt = $conn->prepare("SELECT phone_number FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->bind_result($phoneNumber);
        $stmt->fetch();
        $stmt->close();

        // Check if phone number is present and properly formatted
        if (empty($phoneNumber) || !preg_match('/^2547\d{8}$/', $phoneNumber)) {
            echo "Invalid or missing phone number.";
        } else {
            $accountReference = 'ACCOUNT_REFERENCE';  // A reference for the transaction
            $transactionDesc = 'Adding Balance';

            // Initiate STK Push
            $response = initiateSTKPush($phoneNumber, $amount, $accountReference, $transactionDesc);

            if (isset($response->ResponseCode) && $response->ResponseCode == '0') {
                $checkoutRequestID = $response->CheckoutRequestID;

                // Record pending transaction
                $stmt = $conn->prepare("INSERT INTO PendingTransactions (checkout_request_id, amount, status, phone_number) VALUES (?, ?, 'pending', ?)");
                $stmt->bind_param("sds", $checkoutRequestID, $amount, $phoneNumber);  // Bind the amount as decimal
                $stmt->execute();
                $stmt->close();

                // Redirect to the transaction status page
                header('Location: transaction_status.php?checkoutRequestID=' . urlencode($checkoutRequestID));
                exit();
            } else {
                // Handle errors
                $errorMessage = isset($response->errorMessage) ? $response->errorMessage : 'Unknown error';
                echo "Failed to initiate STK Push: " . htmlspecialchars($errorMessage);
            }
        }
    }
}
?>

<h2>Add Balance</h2>

<form action="add_balance.php" method="POST">
    <label for="amount">Amount:</label><br>
    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required><br>
    <input type="submit" value="Add Balance">
</form>

<?php
require_once '../include/footer.php';
?>
