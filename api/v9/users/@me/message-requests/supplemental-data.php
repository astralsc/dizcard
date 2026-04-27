<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "channel_id" => "1496275062892924939",
    "message_preview" => "null"
];

echo json_encode($data);