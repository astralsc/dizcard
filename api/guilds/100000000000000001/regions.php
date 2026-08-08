<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    [
        'id'         => 'us-west',
        'name'       => 'us-west',
        'optimal'    => false,
        'deprecated' => false,
        'custom'     => true
    ]
];

echo json_encode($data);