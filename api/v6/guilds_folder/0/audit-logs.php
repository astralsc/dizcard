<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    'audit_log_entries' => [],
    'users' => [],
    'webhooks' => []
];

echo json_encode($data);