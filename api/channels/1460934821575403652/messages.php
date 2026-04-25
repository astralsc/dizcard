<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "content" => "messagetesting1233",
    "mentions" => []
];

echo json_encode($data);