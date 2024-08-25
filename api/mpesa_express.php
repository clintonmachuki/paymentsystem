<?php
require_once 'generate_token.php';

function initiateSTKPush($phoneNumber, $amount, $accountReference, $transactionDesc) {
    $accessToken = generateAccessToken();
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    
    $shortcode = '174379';  // Replace with your actual shortcode
    $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';  // Replace with your actual passkey
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
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phoneNumber,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phoneNumber,
        'CallBackURL' => 'https://google.com/api/mpesa_callback.php',
        'AccountReference' => $accountReference,
        'TransactionDesc' => $transactionDesc
    ];

    $data_string = json_encode($curl_post_data);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    $response = curl_exec($curl);
    curl_close($curl);

    // Log or print the raw response for debugging
    file_put_contents('response_log.txt', $response);
    
    $decoded_response = json_decode($response);
    return $decoded_response;
}
?>
