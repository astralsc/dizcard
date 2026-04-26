<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "token" => "test",
    "settings" => (object)[]
];

echo json_encode($data);