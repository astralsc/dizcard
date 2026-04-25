<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "recipient_id" => "1"
];

echo json_encode($data);