<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "token" => "test",
    "user_settings" => [
        "locale" => "en-US",
        "theme" => "midnight"
    ]
];

echo json_encode($data);