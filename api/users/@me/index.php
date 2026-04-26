<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "avatar" => null,
    "discriminator" => "0000",
    "email" => "astral@discordapp.com",
    "flags" => 0,
    "id" => "1497473397611828340",
    "token" => "MTQ5NzQ3MzM5NzYxMTgyODM0MA.adihGA.0PgAXJb2VQ3KDf3CfuE168IHvkXINBWi0QIbvQ",
    "username" => "Astral",
    "verified" => true,
    "mfa_enabled" => false,
    "claimed" => true
];

echo json_encode($data);