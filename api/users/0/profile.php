<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "mutual_guilds" => [
        [
            "id" => "1",
            "nick" => "Astral",
            "roles" => [
                "1451568372113211394",
                "1452561750187622407",
                "1451765400554438948",
            ],
        ]
    ],
    "mutual_friends" => [],
    "user" => [
        "username" => "astralsc",
        "discriminator" => "0001",
        "id" => "1",
        "avatar" => null,
        "bot" => false,
        "flags" => 0,
        "premium" => true,
    ],
    "connected_accounts" => [],
    "premium_since" => "2026-04-26T10:49:21.800Z",
    "premium_type" => 2,
    "user_profile" => [
        "accent_color" => 0,
        "banner" => "",
        "bio" => "",
        "emoji" => null,
        "popout_animation_particle_type" => null,
        "profile_effect" => null,
        "pronouns" => "",
        "theme_colors" => [],
    ]
];

echo json_encode($data);