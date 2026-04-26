<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "backup" => "True",
    "login_instance_id" => "530b03b7-bfe2-4bab-998f-40ddfa659036",
    "mfa" => "True",
    "sms" => "False",
    "ticket" => "OTA5MjgxNTY1ODk5NjQ1MDM4.HQnXy6.fl91KokTMFpuuX7pxiSmQ5D4dYSnDvJ8aNIoH4",
    "totp" => "True",
    "user_id" => "909281565899645038",
    "webauthn" => "(null)"
];

/*$data = [
    "login" => "MTQ5NzUzNTcyOTIyNjQ4NTkxNA.G6za0t.vF31uzw8mHfEQahHPHCgbTugNJVt5ioVscfEFQ",
    "user_id" => "1497535729226485914",
    "user_settings" => [
        "locale" => "en-US",
        "theme" => "dark"
    ]
];*/

echo json_encode($data);