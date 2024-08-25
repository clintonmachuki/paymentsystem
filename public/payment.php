<?php
require_once '../include/db.php';
require_once '../include/security.php';

// Ensure the user is logged in
checkLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Payment</title>
    <link rel="stylesheet" href="../styles/style.css"> <!-- Add your CSS file -->
</head>
<body>
    <?php include '../include/header.php'; ?>

    <h2>Make a Payment</h2>

    <form id="payment-form">
        <label for="receiver_id">Receiver ID:</label>
        <input type="text" id="receiver_id" name="receiver_id" required>

        <label for="amount">Amount:</label>
        <input type="number" id="amount" name="amount" step="0.01" required>

        <button type="submit">Submit Payment</button>
    </form>

    <div id="response-message"></div>

    <?php include '../include/footer.php'; ?>

    <script>
        document.getElementById('payment-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent the default form submission

    const formData = new FormData(this);

    fetch('../api/payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const responseMessage = document.getElementById('response-message');
        responseMessage.textContent = data.message;

        if (data.status === 'success') {
            responseMessage.style.color = 'green';
            // Redirect to the transaction detail page with the transaction ID
            window.location.href = `transaction_detail.php?id=${data.transaction_id}`;
        } else {
            responseMessage.style.color = 'red';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('response-message').textContent = 'An error occurred. Please try again.';
        document.getElementById('response-message').style.color = 'red';
    });
});

    </script>
</body>
</html>
