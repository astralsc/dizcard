<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "id" => "1498228940656082984",
    "name" => "gb6r's server",
    "icon" => null,
    "description" => null,
    "home_header" => null,
    "splash" => null,
    "discovery_splash" => null,
    "features" => [],
    "banner" => null,
    "owner_id" => "909281565899645038",
    "application_id" => null,
    "region" => "rotterdam",
    "afk_channel_id" => null,
    "afk_timeout" => 300,
    "system_channel_id" => "1498228941100683367",
    "system_channel_flags" => 0,
    "widget_enabled" => false,
    "widget_channel_id" => null,
    "verification_level" => 0,
    "verification_role_id" => null,
    "roles" => [
        [
            "id" => "1498228940656082984",
            "name" => "@everyone",
            "description" => null,
            "permissions" => "2248473465835073",
            "position" => 0,
            "color" => 0,
            "colors" => [
                "primary_color" => 0,
                "secondary_color" => null,
                "tertiary_color" => null
            ],
            "hoist" => false,
            "managed" => false,
            "mentionable" => false,
            "icon" => null,
            "unicode_emoji" => null,
            "flags" => 0
        ]
    ],
    "default_message_notifications" => 0,
    "mfa_level" => 0,
    "explicit_content_filter" => 0,
    "max_presences" => null,
    "max_members" => 25000000,
    "max_stage_video_channel_users" => 50,
    "max_video_channel_users" => 25,
    "vanity_url_code" => null,
    "premium_tier" => 0,
    "premium_subscription_count" => 0,
    "preferred_locale" => "en-US",
    "rules_channel_id" => null,
    "safety_alerts_channel_id" => null,
    "public_updates_channel_id" => null,
    "hub_type" => null,
    "premium_progress_bar_enabled" => false,
    "premium_progress_bar_enabled_user_updated_at" => null,
    "latest_onboarding_question_id" => null,
    "nsfw" => false,
    "nsfw_level" => 0,
    "owner_configured_content_level" => 0,
    "emojis" => [],
    "stickers" => [],
    "incidents_data" => null,
    "inventory_settings" => null,
    "official_message_color" => null,
    "embed_enabled" => false,
    "embed_channel_id" => null
];

echo json_encode($data);