<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "id" => "1497895329406654148",
    "name" => "EMPTY_USERNAME's server",
    "region" => "everything",
    "owner_id" => "1497473397611828340",
    "joined_at" => "2026-04-26T09:41:16.080Z",
    "afk_channel_id" => null,
    "afk_timeout" => 300,
    "verification_level" => 0,
    "default_message_notifications" => 0,
    "explicit_content_filter" => 0,
    "roles" => [
        [
            "id" => "1497895329406654148",
            "name" => "@everyone",
            "permissions" => 104193089,
            "position" => 0,
            "color" => 0,
            "hoist" => false,
            "managed" => false,
            "mentionable" => false
        ]
    ],
    "emojis" => [],
    "features" => [],
    "widget_enabled" => false,
    "channels" => [
        [
            "id" => "1497895329473763013",
            "type" => 4,
            "name" => "Text Channels"
        ],
        [
            "id" => "1497895329406654148",
            "type" => 0,
            "name" => "general"
        ]
    ],
    "member_count" => 1
];

echo json_encode($data);