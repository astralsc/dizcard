<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    'channel_id' => null,
    'enabled' => false
];

echo json_encode($data);