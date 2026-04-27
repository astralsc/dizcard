<?php
http_response_code(200);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$content = $input['content']; // user's msg

$data = [
    "type" => 0,
    "content" => $content,
    "mentions" => [],
    "mention_roles" => [],
    "attachments" => [],
    "embeds" => [],
    "timestamp" => "2026-04-27T07:38:29.352000+00:00",
    "edited_timestamp" => null,
    "flags" => 0,
    "components" => [],
    "id" => "1498226818975666206",
    "channel_id" => "1498226078542729328",
    "author" => [
        "id" => "909281565899645038",
        "username" => "gb6r",
        "avatar" => "6491404be627636976361c06f119ba96",
        "discriminator" => "0",
        "public_flags" => 256,
        "flags" => 256,
        "banner" => "a_b5647804f10f8dfb84d051ee84872149",
        "accent_color" => 0,
        "global_name" => "Astral",
        "avatar_decoration_data" => [
            "asset" => "a_75348082d14b5097708444fca20a09e0",
            "sku_id" => "1427463138634109026",
            "expires_at" => null
        ],
        "collectibles" => [
            "nameplate" => [
                "sku_id" => "1427463138646954036",
                "asset" => "nameplates/orb/magical_mist/",
                "label" => "COLLECTIBLES_ORB_MAGIC_MISTS_NP_A11Y",
                "palette" => "cobalt"
            ]
        ],
        "display_name_styles" => [
            "font_id" => 3,
            "effect_id" => 2,
            "colors" => [7183099, 6082490]
        ],
        "banner_color" => "#000000",
        "clan" => [
            "identity_guild_id" => "1463169720743362563",
            "identity_enabled" => true,
            "tag" => "NBS",
            "badge" => "be432d9718b83c45b8225eca43004578"
        ],
        "primary_guild" => [
            "identity_guild_id" => "1463169720743362563",
            "identity_enabled" => true,
            "tag" => "NBS",
            "badge" => "be432d9718b83c45b8225eca43004578"
        ]
    ],
    "pinned" => false,
    "mention_everyone" => false,
    "tts" => false,
    "nonce" => "1498226817302003712"
];

echo json_encode($data);