<?php

// Informações da API Bonifiq.
$apiUrlBonifiq = "https://api.bonifiq.com.br/v1/pvt/";
$credentialName = "APIUSER-Cloviscala-6e749e525c2e4a66990811ac4d782ebe";
$credentialToken = "BHN4U95FMAFGMRUXMVH7LLPSSCJ52V";
$tokenBonifiq = base64_encode($credentialName . ":" . $credentialToken);

// Informações da API BotConversa
$apiUrlBotConversa = "https://backend.botconversa.com.br/api/v1/webhooks-automation/catch/101561/y92VYXPSPXvG/";
$tokenBotConversa = "7fecea83-5b88-463d-bf98-b82fdf5d1129";

// Obtenha dados POST
$postData = json_decode(file_get_contents('php://input'), true);

file_put_contents('data.txt', json_encode($postData, true), FILE_APPEND);


if ($postData === null) {
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid JSON data";
    exit();
}

// Extraia os dados relevantes do POST
$points = $postData['Customer']['PointsBalance'];
$customerId = $postData['Customer']['Id'];

// Função para obter dados do cliente usando cURL
function getCustomerData($apiUrlGetCustomer, $tokenBonifiq) {
    $ch = curl_init($apiUrlGetCustomer);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $tokenBonifiq
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        header("HTTP/1.1 500 Internal Server Error");
        echo 'Error:' . curl_error($ch);
        curl_close($ch);
        exit();
    }
    curl_close($ch);
    echo $response;
    return $response;
}

// URL da API Bonifiq para obter informações do cliente
$apiUrlGetCustomer = $apiUrlBonifiq . 'Customer/' . $customerId;

// Obtendo dados do cliente da API
$getCustomerResponse = getCustomerData($apiUrlGetCustomer, $tokenBonifiq);

$customer = json_decode($getCustomerResponse, true);

if ($customer === null) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Failed to decode customer data";
    exit();
}

// Extraia os dados do cliente
$customerEmail = $customer['Result']['Email'];
$customerName = $customer['Result']['Name'];
$customerDocument = $customer['Result']['Document'];
$customerPhone = $customer['Result']['Phone'];

$customerStructure = [
    "Name" => $customerName,
    "Email" => $customerEmail,
    "Phone" => $customerPhone,
    "Document" => $customerDocument,
    "PointsBalance" => $points,
];

// Função para enviar dados ao Bot Conversa usando cURL
function sendBotConversa($customer, $apiUrlBotConversa, $tokenBotConversa) {
    $ch = curl_init($apiUrlBotConversa);

    $payloadBotConversa = [
        'name' => $customer['Name'],
        'email' => $customer['Email'],
        'phone' => $customer['Phone'],
        'document' => $customer['Document'],
        'points' => $customer['PointsBalance'],
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payloadBotConversa));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    curl_close($ch);

    return $response;
}

$botconversa = sendBotConversa($customerStructure, $apiUrlBotConversa, $tokenBotConversa);

header("Content-Type: text/plain");
echo $botconversa;

?>
