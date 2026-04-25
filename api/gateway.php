<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "url" => "wss://discordapp.com"
];

echo json_encode($data);