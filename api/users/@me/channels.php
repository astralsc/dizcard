<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "guild_id" => null,
    "id" => "1",
    "is_private" => true,
    "last_message_id" => 0,
    "recipients" => [
        [
            "avatar" => "6491404be627636976361c06f119ba96",
            "bot" => false,
            "discriminator" => "0000",
            "flags" => 0,
            "id" => "1",
            "premium" => true,
            "username" => "Discord"
        ]
    ],
    "type" => 1
];

echo json_encode($data);