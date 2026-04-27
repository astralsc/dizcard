<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "message" => "Unknown User",
    "code" => "10013"
];

echo json_encode($data);