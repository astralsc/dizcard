<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    [
        "code" => "umjiJKl3WzH2ufzC",

        "inviter" => [
            "id" => "1497473397611828340",
            "username" => "Astral",
            "discriminator" => "0001",
            "avatar" => "ceb2ddd922df98eb0b1b5a55f2023f84"
        ],

        "expires_at" => null,

        "guild" => [
            "id" => "1497968643244300336",
            "name" => "Test",
            "icon" => null,
            "splash" => null,
            "owner_id" => "1497473397611828340",
            "features" => []
        ],

        "channel" => [
            "id" => "1497968643244300336",
            "guild_id" => "1497968643244300336",
            "name" => "general",
            "type" => 0
        ],

        "uses" => 0
    ],

    [
        "code" => "6AKtm6LFUmOFhgE3",

        "inviter" => [
            "id" => "1497473397611828340",
            "username" => "Astral",
            "discriminator" => "0001",
            "avatar" => "ceb2ddd922df98eb0b1b5a55f2023f84"
        ],

        "expires_at" => "2026-08-09T05:22:11.706Z",

        "guild" => [
            "id" => "1497968643244300336",
            "name" => "Test",
            "icon" => null,
            "splash" => null,
            "owner_id" => "1497473397611828340",
            "features" => []
        ],

        "channel" => [
            "id" => "1497968643244300336",
            "guild_id" => "1497968643244300336",
            "name" => "general",
            "type" => 0
        ],

        "uses" => 0
    ]
];

echo json_encode($data);