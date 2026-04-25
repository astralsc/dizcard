<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "token" => "MTQ3NTM2MzM5MDE0MTYxMjUxOQ.adifow.S7GQjOdyQbtUzU1qIzHuEy21NATzgxuaPN0RRg"
];

echo json_encode($data);