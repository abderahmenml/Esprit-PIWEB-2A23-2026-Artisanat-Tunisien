<?php

$url = "http://localhost:11434/api/chat";

$data = [
    "model" => "phi3:mini",
    "messages" => [
        [
            "role" => "user",
            "content" => "Bonjour"
        ]
    ],
    "stream" => false
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo curl_error($ch);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

echo "<pre>";
print_r($result);