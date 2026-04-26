<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    [
        'id'         => '2016',
        'name'       => '2015-2016',
        'optimal'    => false,
        'deprecated' => false,
        'custom'     => true,
    ],
    [
        'id'         => '2017',
        'name'       => '2015-2017',
        'optimal'    => false,
        'deprecated' => false,
        'custom'     => true,
    ],
    [
        'id'         => '2018',
        'name'       => '2015-2018',
        'optimal'    => false,
        'deprecated' => false,
        'custom'     => true,
    ],
    [
        'id'         => 'everything',
        'name'       => 'Everything',
        'optimal'    => false,
        'deprecated' => false,
        'custom'     => true,
    ]
];

echo json_encode($data);