<?php
require_once '../include/security.php';
checkLoggedIn();  // Ensure the user is logged in

require_once '../include/header.php';
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

<?php
require_once '../include/footer.php';
?>

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
        })
        .catch(error => {
            console.error('Error:', error);
            const responseMessage = document.getElementById('response-message');
            responseMessage.textContent = 'An error occurred. Please try again.';
            responseMessage.style.color = 'red';
        });
    });
</script>
