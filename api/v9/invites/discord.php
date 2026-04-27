<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "code" => "discord",
    "inviter" => [
        "id" => "1",
        "username" => "Astral",
        "discriminator" => "0001",
        "avatar" => null
    ],
    "expires_at" => "2026-04-27T09:44:17.206Z",
    "guild" => [
        "id" => "1",
        "name" => "Discord",
        "icon" => null,
        "splash" => null,
        "owner_id" => "1",
        "features" => []
    ],
    "channel" => [
        "id" => "1498228940656082984",
        "guild_id" => "1498228940656082984",
        "name" => "general",
        "type" => 0
    ],
    "uses" => 0
];

echo json_encode($data);