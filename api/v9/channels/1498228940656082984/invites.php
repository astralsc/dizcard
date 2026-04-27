<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "type" => 0,
    "code" => "rdQAVdu2",
    "inviter" => [
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
    "max_age" => 604800,
    "created_at" => "2026-04-27T07:35:34.167982+00:00",
    "expires_at" => "2026-05-04T07:35:34+00:00",
    "id" => "1498226083776954380",
    "guild" => [
        "id" => "1498226078072705095",
        "name" => "gb6r's server",
        "splash" => null,
        "banner" => null,
        "description" => null,
        "icon" => null,
        "features" => [],
        "verification_level" => 0,
        "vanity_url_code" => null,
        "nsfw_level" => 0,
        "nsfw" => false,
        "premium_subscription_count" => 0,
        "premium_tier" => 0
    ],
    "guild_id" => "1498226078072705095",
    "channel" => [
        "id" => "1498226078542729328",
        "type" => 0,
        "name" => "general"
    ],
    "profile" => [
        "id" => "1498226078072705095",
        "name" => "gb6r's server",
        "icon_hash" => null,
        "member_count" => 1,
        "online_count" => 1,
        "description" => null,
        "banner_hash" => null,
        "game_application_ids" => [],
        "game_activity" => [],
        "tag" => null,
        "badge" => 0,
        "badge_color_primary" => null,
        "badge_color_secondary" => null,
        "badge_hash" => null,
        "traits" => [],
        "features" => [],
        "visibility" => 0,
        "custom_banner_hash" => null,
        "premium_subscription_count" => 0,
        "premium_tier" => 0
    ],
    "uses" => 0,
    "max_uses" => 0,
    "temporary" => false
];

echo json_encode($data);