<?php
require_once '../include/db.php';

$callbackJSONData = file_get_contents('php://input');
$callbackData = json_decode($callbackJSONData);

// Log or print the raw callback data for debugging
file_put_contents('callback_log.txt', $callbackJSONData);

if (isset($callbackData->Body->stkCallback->ResultCode) && $callbackData->Body->stkCallback->ResultCode == 0) {
    $amount = $callbackData->Body->stkCallback->CallbackMetadata->Item[0]->Value;
    $phoneNumber = $callbackData->Body->stkCallback->CallbackMetadata->Item[4]->Value;

    // Update user balance
    $stmt = $conn->prepare("UPDATE Users SET balance = balance + ? WHERE phone_number = ?");
    $stmt->bind_param("ds", $amount, $phoneNumber);
    $stmt->execute();
    $stmt->close();

    // Record transaction
    $stmt = $conn->prepare("INSERT INTO Transactions (sender_id, receiver_id, amount) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $phoneNumber, $phoneNumber, $amount);
    $stmt->execute();
    $stmt->close();

    echo "Transaction successful.";
} else {
    $errorMessage = isset($callbackData->Body->stkCallback->ResultDesc) ? $callbackData->Body->stkCallback->ResultDesc : 'Transaction failed';
    echo "Transaction failed: " . $errorMessage;
}
?>
