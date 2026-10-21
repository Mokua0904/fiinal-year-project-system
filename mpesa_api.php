<?php
// Turn on ALL errors so we can see what is breaking
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force the output to be JSON
header('Content-Type: application/json');

// 1. Check if cURL is enabled on your XAMPP
if (!function_exists('curl_init')) {
    echo json_encode(['success' => false, 'message' => 'FATAL: cURL is not enabled in your PHP.ini']);
    exit;
}

// 2. Get the data from JavaScript
$input = json_decode(file_get_contents('php://input'), true);
$phone = $input['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Error: No phone number received from frontend.']);
    exit;
}

// 3. Safaricom Sandbox Credentials
$consumerKey = 'q7rtOusU2JygzvNaLbA3DmvtzDCPaFcfXKvnEdhj1GIOsrAg'; 
$consumerSecret = 'GLoRtye9Ac8yfrUJYWRhvxAHbwkdpkD9QW7IOzQZvrTJySYLXmSi6ZiCWIcK6qt9';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; 
$shortcode = '174379'; 

// Format phone (0700... -> 254700...)
$phone = preg_replace('/^0/', '254', $phone);

// 4. Try to get Access Token
$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL for localhost
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // Bypass SSL for localhost

$result = curl_exec($curl);

// CHECK FOR CURL ERRORS
if ($result === false) {
    echo json_encode([
        'success' => false, 
        'message' => 'cURL Error (Token): ' . curl_error($curl)
    ]);
    curl_close($curl);
    exit;
}
curl_close($curl);

$response = json_decode($result);

// CHECK IF WE GOT A TOKEN
if (!isset($response->access_token)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Safaricom rejected credentials. Did you put your real keys in?',
        'safaricom_response' => $response
    ]);
    exit;
}

$accessToken = $response->access_token;

// 5. Send STK Push
$timestamp = date('YmdHis');
$password = base64_encode($shortcode . $passkey . $timestamp);
$callbackUrl = 'https://your-domain.com/mpesa_callback.php'; // Needs to be public URL

$stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $stkUrl);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
$curl_post_data = [
    'BusinessShortCode' => $shortcode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => 1, // Hardcoded 1 for testing
    'PartyA' => $phone,
    'PartyB' => $shortcode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackUrl,
    'AccountReference' => 'Eckose Order',
    'TransactionDesc' => 'Payment for goods'
];
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($curl);

// CHECK FOR CURL ERRORS
if ($result === false) {
    echo json_encode([
        'success' => false, 
        'message' => 'cURL Error (STK Push): ' . curl_error($curl)
    ]);
    curl_close($curl);
    exit;
}
curl_close($curl);

$stkResponse = json_decode($result);

// 6. Final Output
if (isset($stkResponse->ResponseCode) && $stkResponse->ResponseCode === '0') {
    echo json_encode(['success' => true, 'message' => 'STK Push sent successfully']);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Safaricom STK push failed',
        'safaricom_response' => $stkResponse
    ]);
}
?>