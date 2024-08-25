<?php
function generateAccessToken() {
    $consumerKey = 'hjKaSVVjNaDYZDT8M415YW0qJF5lukabf1QQnD2UcGOXhEDy';
    $consumerSecret = 'gtaRfPA6oQaZKdfGR17h00Pf1ObqeMbNUbi5yw8wbhrqGG4NgFneR2NhE0Jyooa9';

    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($curl);
    curl_close($curl);

    $json = json_decode($response);
    return $json->access_token;
}
?>
