<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    [
        "id" => "123456789012345678",
        "currency" => "usd",
        "amount" => 999,
        "tax" => 0,
        "description" => "Nitro",
        "status" => 1,
        "payment_source" => [
            "id" => "123456789012345678",
            "type" => 1
        ],
        "created_at" => "2019-08-01T12:00:00.000000+00:00"
    ]
];

echo json_encode($data);