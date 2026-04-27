<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "guild_affinities" => []
];

echo json_encode($data);