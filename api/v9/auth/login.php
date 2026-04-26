<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "backup" => "True",
    "login_instance_id" => "test",
    "mfa" => "True",
    "sms" => "False",
    "ticket" => "test",
    "totp" => "True",
    "user_id" => "1",
    "webauthn" => "(null)"
];

/*$data = [
    "login" => "test",
    "user_id" => "1",
    "user_settings" => [
        "locale" => "en-US",
        "theme" => "dark"
    ]
];*/

echo json_encode($data);