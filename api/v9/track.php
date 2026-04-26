<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    'id'   => '12793115722212178955',
    'name' => 'Jason Citron Simulator 2024'
];

echo json_encode($data);