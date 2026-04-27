<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    'r' => 1,
    'server_timing' => [
        'location_startswith' => null,
        'name' => [
            'cfCacheStatus' => true,
            'cfEdge'        => true,
            'cfExtPri'      => true,
            'cfL4'          => true,
            'cfOrigin'      => true,
            'cfSpeedBrain'  => true,
        ],
    ],
    'token'   => 'test',
    'version' => '2024.11.0'
];

echo json_encode($data);