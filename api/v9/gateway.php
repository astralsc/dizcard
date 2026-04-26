<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "url" => "wss://localhost:8081"
];

echo json_encode($data);