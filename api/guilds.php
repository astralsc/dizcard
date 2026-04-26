<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "namespace" => "adasdsadad",
    "region" => "2017"
];

echo json_encode($data);