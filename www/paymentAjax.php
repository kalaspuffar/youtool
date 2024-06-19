<?php
require_once(__DIR__ . '/../include/head.php');

$data = json_decode(file_get_contents('php://input'));

if (isset($data->op) && isset($data->months) && $data->op == 'create-paypal-order') {
    global $PAYPAL_ENDPOINT;
    
    $auth = generatePayPalAccessToken();
    
    setlocale(LC_MONETARY, 'en_US');

    $queryData = [
        "purchase_units" => [
            [
                "amount" => [
                    "currency_code" => "USD",
                    "value" => number_format($MONTHLY_PRICE * $data->months, 2, '.', '')
                ],
            ]
        ],
        "intent" => "CAPTURE",
        "payment_source" => [
            "paypal" => [
                "experience_context" => [
                    "payment_method_preference" =>  "IMMEDIATE_PAYMENT_REQUIRED",
                    "payment_method_selected" =>  "PAYPAL",
                    "brand_name" => "YouTool.app",
                    "locale" => "en-US",
                    "shipping_preference" =>  "NO_SHIPPING",
                    "user_action" => "PAY_NOW",
                ]
            ]
        ]
    ];

    $postData = json_encode($queryData);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  =>
                "Content-Type: application/json" . "\r\n" .
                "Authorization: Bearer " . $auth . "\r\n" .
                //'PayPal-Mock-Response: {"mock_application_codes": "MISSING_REQUIRED_PARAMETER"}' . "\r\n" .
                //'PayPal-Mock-Response: {"mock_application_codes": "PERMISSION_DENIED"}' . "\r\n" .
                //'PayPal-Mock-Response: {"mock_application_codes": "INTERNAL_SERVER_ERROR"}' . "\r\n" .
                "User-Agent: YouTool/0.1\r\n",
            'content' => $postData
        ]
    ]);

    $result = @file_get_contents($PAYPAL_ENDPOINT . '/v2/checkout/orders', false, $context);

    if ($result === false) {
        echo '{"status":"FAILURE", "message": "Unable to create order for payment."}';
        exit;
    }

    $resultJSON = json_decode($result);

    $months = $data->months;

    $stmt = $mysqli->prepare('INSERT INTO payment (id, userId, quantity, price) VALUES (?,?,?,?)');
    $stmt->bind_param("siii", $resultJSON->id, $user['id'], $months, $MONTHLY_PRICE);
    $stmt->execute();
    echo $result;
    exit;
}

if (isset($data->op) && isset($data->orderId) && $data->op == 'approve-paypal-order') {
    global $PAYPAL_ENDPOINT;
    
    $auth = generatePayPalAccessToken();

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  =>
                "Content-Type: application/json" . "\r\n" .
                "Authorization: Bearer " . $auth . "\r\n" .
                //'PayPal-Mock-Response: {"mock_application_codes": "INSTRUMENT_DECLINED"}' . "\r\n" .
                //'PayPal-Mock-Response: {"mock_application_codes": "TRANSACTION_REFUSED"}' . "\r\n" .               
                //'PayPal-Mock-Response: {"mock_application_codes": "INTERNAL_SERVER_ERROR"}' . "\r\n" .
                "User-Agent: YouTool/0.1\r\n",
        ]
    ]);

    $result = @file_get_contents($PAYPAL_ENDPOINT . '/v2/checkout/orders/' . $data->orderId . '/capture', false, $context);

    if ($result === false) {
        echo '{"status":"FAILURE", "message": "Unable to complete payment."}';
        exit;
    }

    $resultJSON = json_decode($result);

    $payed = $resultJSON->purchase_units[0]->payments->captures[0]->amount->value;
    $status = $resultJSON->status;
    $email = $resultJSON->payer->email_address;
    $id = $resultJSON->id;

    $stmt = $mysqli->prepare('UPDATE payment SET payed = ?, status = ?, paymentDate = NOW(), email = ?, response = ? WHERE id = ? AND userId = ?');
    $stmt->bind_param("issssi", $payed, $status, $email, $result, $id, $user['id']);
    $stmt->execute();

    $stmt = $mysqli->prepare('SELECT quantity FROM payment WHERE quantity * price = payed AND id = ? AND userId = ?');
    $stmt->bind_param("si", $id, $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $quantityRes = $res->fetch_assoc();

    $months = $quantityRes['quantity'];

    $stmt = $mysqli->prepare('UPDATE users SET payed_until = ' .
        'IF(NOW() < payed_until, DATE_ADD(payed_until, INTERVAL ? MONTH), DATE_ADD(NOW(), INTERVAL ? MONTH)) '.
        ' WHERE id = ? ');
    $stmt->bind_param("iii", $months, $months, $user['id']);
    $stmt->execute();

    echo $result;
    exit;
}


function generatePayPalAccessToken() {
    global $PAYPAL_CLIENT_ID, $PAYPAL_CLIENT_SECRET, $PAYPAL_ENDPOINT;

    $auth = base64_encode($PAYPAL_CLIENT_ID . ':' . $PAYPAL_CLIENT_SECRET);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  =>
                "Content-Type: application/x-www-form-urlencoded" . "\r\n" .
                "Authorization: Basic " . $auth . "\r\n" .
                "User-Agent: YouTool/0.1\r\n",
            'content' => 'grant_type=client_credentials'
        ]
    ]);

    $result = file_get_contents($PAYPAL_ENDPOINT . '/v1/oauth2/token', false, $context);
    if ($result === false) {
        return false;
    }
    $reply = json_decode($result);
    return $reply->access_token;
}