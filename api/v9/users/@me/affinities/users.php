<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "user_affinities" => [],
    "inverse_user_affinities" => []
];

echo json_encode($data);