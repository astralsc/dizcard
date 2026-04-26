<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "id" => "1497909642016069562",
    "name" => "sdaaadadad",
    "region" => "everything",
    "owner_id" => "1497473397611828340",
    "joined_at" => "2026-04-26T10:38:08.472Z",
    "afk_channel_id" => null,
    "afk_timeout" => 300,
    "verification_level" => 0,
    "default_message_notifications" => 0,
    "explicit_content_filter" => 0,
    "roles" => [
        [
            "id" => "1497909642016069562",
            "name" => "@everyone",
            "permissions" => 104193089,
            "position" => 0,
            "color" => 0,
            "hoist" => false,
            "managed" => false,
            "mentionable" => false,
        ]
    ],
    "emojis" => [],
    "features" => [],
    "application_id" => null,
    "widget_enabled" => false,
    "widget_channel_id" => null,
    "system_channel_id" => null,
    "channels" => [
        [
            "id" => "1497909642083178427",
            "type" => 4,
            "name" => "Text Channels",
            "position" => 0,
            "guild_id" => "1497909642016069562",
            "permission_overwrites" => [],
        ],
        [
            "id" => "1497909642016069562",
            "type" => 0,
            "name" => "general",
            "position" => 0,
            "parent_id" => "1497909642083178427",
            "guild_id" => "1497909642016069562",
            "topic" => null,
            "nsfw" => false,
            "last_message_id" => "0",
            "rate_limit_per_user" => 0,
            "permission_overwrites" => [],
        ],
        [
            "id" => "1497909642083178428",
            "type" => 4,
            "name" => "Voice Channels",
            "position" => 1,
            "guild_id" => "1497909642016069562",
            "permission_overwrites" => [],
        ],
        [
            "id" => "1497909642083178429",
            "type" => 2,
            "name" => "General",
            "position" => 0,
            "parent_id" => "1497909642083178428",
            "guild_id" => "1497909642016069562",
            "user_limit" => 0,
            "bitrate" => 64000,
            "permission_overwrites" => [],
        ],
    ],
    "members" => [
        [
            "user" => [
                "username" => "Astralll",
                "discriminator" => "1997",
                "id" => "1497473397611828340",
                "avatar" => null,
                "bot" => false,
                "flags" => 0,
                "premium" => true,
            ],
            "nick" => null,
            "roles" => [],
            "joined_at" => "2026-04-26T10:38:08.472Z",
            "deaf" => false,
            "mute" => false,
        ]
    ],
    "presences" => [
        [
            "status" => "dnd",
            "game_id" => null,
            "activities" => [],
            "user" => [
                "username" => "Astralll",
                "discriminator" => "1997",
                "id" => "1497473397611828340",
                "avatar" => null,
                "bot" => false,
                "flags" => 0,
                "premium" => true,
            ],
        ]
    ],
    "member_count" => 1,
    "voice_states" => [],
    "large" => false,
    "unavailable" => false
];

echo json_encode($data);