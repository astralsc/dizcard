<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    [
        'id'         => 'rotterdam',
        'name'       => 'rotterdam',
        'optimal'    => false,
        'deprecated' => false,
        'custom'     => true,
    ]
];

echo json_encode($data);