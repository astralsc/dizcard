<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "code" => "discord",
    "temporary" => false,
    "revoked" => false,

    "inviter" => [
        "username" => "Astral",
        "discriminator" => "0001",
        "id" => "1497473397611828340",
        "avatar" => "ceb2ddd922df98eb0b1b5a55f2023f84",
        "bot" => false,
        "flags" => 0,
        "premium" => true
    ],

    "max_age" => 86400,
    "max_uses" => 0,
    "uses" => 0,

    "guild" => [
        "id" => "200000000000000001",
        "name" => "Astral's Server",
        "icon" => null,
        "splash" => null,
        "owner_id" => "1497473397611828340",
        "features" => []
    ],

    "channel" => [
        "id" => "1497968643244300336",
        "name" => "general",
        "guild_id" => "1497968643244300336",
        "type" => 0
    ]
];

echo json_encode($data);