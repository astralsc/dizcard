<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "global" => "False",
    "message" => "You are being rate limited.",
    "retry_after" => 8092.843
];

echo json_encode($data);