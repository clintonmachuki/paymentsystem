<?php
require_once '../include/db.php'; // Adjust path if necessary
require_once '../api/generate_token.php'; // Adjust path if necessary

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
    echo json_encode(['status' => 'Error', 'message' => 'No transaction ID provided.']);
    exit();
}

// Check transaction status
$statusResponse = checkTransactionStatus($checkoutRequestID);

// Prepare the status response
$response = ['status' => 'Processing']; // Default status

if (isset($statusResponse->ResultCode)) {
    switch ($statusResponse->ResultCode) {
        case 0:
            if ($statusResponse->ResultDesc == 'The service request is processed successfully.') {
                // Success
                $response['status'] = 'Success';

                // Fetch the amount from PendingTransactions table
                $stmt = $conn->prepare("SELECT amount, phone_number FROM PendingTransactions WHERE checkout_request_id = ?");
                $stmt->bind_param("s", $checkoutRequestID);
                $stmt->execute();
                $stmt->bind_result($amount, $phoneNumber);
                $stmt->fetch();
                $stmt->close();

                // Log the phone number for debugging
                error_log("Extracted Phone Number: " . $phoneNumber);

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

                    $response['transaction_id'] = $transaction_id; // Add transaction ID to response

                } else {
                    $response['status'] = 'Failed';
                    $response['message'] = 'User not found';
                }
            } else {
                $response['status'] = 'Failed';
                $response['message'] = $statusResponse->ResultDesc;
            }
            break;
        case 1032:
            $response['status'] = 'Cancelled';
            $response['message'] = $statusResponse->ResultDesc;
            break;
        case 1037:
            $response['status'] = 'Phone Offline';
            $response['message'] = $statusResponse->ResultDesc;
            break;
        case 2001:
            $response['status'] = 'Invalid PIN';
            $response['message'] = $statusResponse->ResultDesc;
            break;
        default:
            $response['status'] = 'Failed';
            $response['message'] = $statusResponse->ResultDesc;
            break;
    }
} else if (isset($statusResponse->errorCode)) {
    $response['status'] = 'Error';
    $response['message'] = $statusResponse->errorMessage;
}

// Return the JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
