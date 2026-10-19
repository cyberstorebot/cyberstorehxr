<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$data = json_decode(file_get_contents('php://input'), true);
$gateway = $data['gateway'] ?? 'stripe';
$cardData = $data['card'] ?? '';

if (empty($cardData)) {
    echo json_encode(['status' => 'error', 'message' => 'Cartão ausente']);
    exit;
}

$parts = explode('|', $cardData);
if (count($parts) < 4) {
    echo json_encode(['status' => 'error', 'message' => 'Formato inválido. Use CARTAO|MM|YY|CVV']);
    exit;
}

$number = $parts[0];
$month = $parts[1];
$year = $parts[2];
$cvv = $parts[3];

if ($gateway === 'stripe') {
    $apiKey = 'pk_live_51TrmHoLJuAfa1w8I3yQ5RB0K46lz4VMOdCJRQRb1oy9Z9PwA5jBa8OQbZDetU5SJUMa6zqIzR57Kg7wx24msCCnn00yHqpN4St';
    
    $ch = curl_init('https://api.stripe.com/v1/payment_methods');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'type' => 'card',
        'card[number]' => $number,
        'card[exp_month]' => $month,
        'card[exp_year]' => $year,
        'card[cvc]' => $cvv,
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo ($httpCode == 200) ? json_encode(['status' => 'LIVE']) : json_encode(['status' => 'DIE']);

} elseif ($gateway === 'mp') {
    $accessToken = 'APP_USR-4934588586838432-XXXXXXXX-241983636';
    
    $ch = curl_init('https://api.mercadopago.com/v1/payment_methods/card_tokens');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'card' => [
            'number' => $number,
            'expiration_month' => $month,
            'expiration_year' => $year,
            'security_code' => $cvv,
        ]
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo ($httpCode == 200 || $httpCode == 201) ? json_encode(['status' => 'LIVE']) : json_encode(['status' => 'DIE']);
}
?>
