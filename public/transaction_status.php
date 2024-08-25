<?php
require_once '../api/generate_token.php'; // Update the path if necessary
require_once '../include/db.php'; // Adjust path if necessary
require_once '../include/header.php'; // Adjust path if necessary

// Function to check the status of a transaction
function checkTransactionStatus($checkoutRequestID) {
    $accessToken = generateAccessToken();
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';

    $shortcode = '174379'; // Replace with your actual shortcode
    $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; // Replace with your actual passkey
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);

    $curl_post_data = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkoutRequestID
    ];

    $data_string = json_encode($curl_post_data);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response);
}

// Get checkoutRequestID from URL parameter
$checkoutRequestID = $_GET['checkoutRequestID'] ?? '';

if (!$checkoutRequestID) {
    die('No transaction ID provided.');
}

// Check transaction status
$statusResponse = checkTransactionStatus($checkoutRequestID);

// Default status
$status = 'Processing';

if (isset($statusResponse->ResultCode)) {
    switch ($statusResponse->ResultCode) {
        case 0:
            if ($statusResponse->ResultDesc == 'The service request is processed successfully.') {
                // Success
                $status = 'Success';

                // Fetch the amount from PendingTransactions table
                $stmt = $conn->prepare("SELECT amount, phone_number FROM PendingTransactions WHERE checkout_request_id = ?");
                $stmt->bind_param("s", $checkoutRequestID);
                $stmt->execute();
                $stmt->bind_result($amount, $phoneNumber);
                $stmt->fetch();
                $stmt->close();

                // Get user_id from phone number
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone_number = ?");
                $stmt->bind_param("s", $phoneNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user) {
                    $user_id = $user['user_id'];

                    // Add balance to user
                    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE user_id = ?");
                    $stmt->bind_param("ds", $amount, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    // Add transaction record
                    $transaction_id = uniqid(); // Generate unique transaction ID
                    $stmt = $conn->prepare("INSERT INTO transactions (transaction_id, sender_id, receiver_id, amount) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sssd", $transaction_id, $user_id, $user_id, $amount); // Use $user_id for both sender and receiver
                    $stmt->execute();
                    $stmt->close();

                    // Update pending transaction with the transaction_id
                    $stmt = $conn->prepare("UPDATE PendingTransactions SET status = 'completed', transaction_id = ? WHERE checkout_request_id = ?");
                    $stmt->bind_param("ss", $transaction_id, $checkoutRequestID);
                    $stmt->execute();
                    $stmt->close();

                    // Redirect to transaction detail page
                    header("Location: transaction_detail.php?id=" . urlencode($transaction_id));
                    exit;
                } else {
                    $status = 'Failed: User not found';
                }
            } else {
                $status = 'Failed: ' . $statusResponse->ResultDesc;
            }
            break;
        case 1032:
            $status = 'Cancelled: ' . $statusResponse->ResultDesc;
            break;
        case 1037:
            $status = 'Phone Offline: ' . $statusResponse->ResultDesc;
            break;
        case 2001:
            $status = 'Invalid PIN: ' . $statusResponse->ResultDesc;
            break;
        default:
            $status = 'Failed: ' . $statusResponse->ResultDesc;
            break;
    }
} else if (isset($statusResponse->errorCode)) {
    $status = 'Error: ' . $statusResponse->errorMessage;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .status-container {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-width: 600px;
            margin: 0 auto;
        }
        .status-container h1 {
            text-align: center;
        }
        .status {
            font-size: 18px;
            color: #333;
        }
        .success {
            color: green;
        }
        .failed {
            color: red;
        }
        .processing {
            color: orange;
        }
    </style>
</head>
<body>
    <div class="status-container">
        <h1>Transaction Status</h1>
        <p class="status <?php echo strtolower($status); ?>">
            Status: <?php echo htmlspecialchars($status); ?>
        </p>
    </div>
    <script>
        function updateStatus() {
            const checkoutRequestID = '<?php echo $checkoutRequestID; ?>';
            fetch('get_transaction_status.php?checkoutRequestID=' + checkoutRequestID)
                .then(response => response.json())
                .then(data => {
                    const statusElement = document.querySelector('.status');
                    statusElement.innerHTML = `Status: ${data.status}`;
                    
                    if (data.status === 'Success') {
                        statusElement.classList.remove('failed', 'processing');
                        statusElement.classList.add('success');
                        
                        // Redirect to the transaction detail page
                        if (data.transaction_id) {
                            window.location.href = 'transaction_detail.php?id=' + encodeURIComponent(data.transaction_id);
                        }
                    } else if (data.status === 'Processing') {
                        statusElement.classList.remove('success', 'failed');
                        statusElement.classList.add('processing');
                    } else {
                        statusElement.classList.remove('success', 'processing');
                        statusElement.classList.add('failed');
                        if (data.message) {
                            statusElement.innerHTML += `<br>${data.message}`;
                        }
                    }
                })
                .catch(error => console.error('Error fetching status:', error));
        }

        setInterval(updateStatus, 5000); // Check status every 5 seconds
    </script>
</body>
</html>
