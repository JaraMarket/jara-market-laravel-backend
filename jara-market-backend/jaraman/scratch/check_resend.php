<?php

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$envData = $dotenv->load();

$apiKey = $envData['RESEND_API_KEY'] ?? null;

if (!$apiKey) {
    echo "No API key found in .env\n";
    exit(1);
}

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/domains');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$headers = array();
$headers[] = 'Authorization: Bearer ' . $apiKey;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

echo $result;
