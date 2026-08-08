<?php

http_response_code(204);
header('Content-Type: application/json');

include __DIR__ . '/../../../../config/db.php';

$h = getallheaders();
$token = trim($h['Authorization'] ?? $h['authorization'] ?? '');

if (!$token) {
    http_response_code(401);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

if (!preg_match('#channels/([0-9]+)/typing$#', $path, $m)) {
    http_response_code(400);
    exit;
}

$stmt = $DBReq->prepare(
    "SELECT id FROM users WHERE token=? LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    exit;
}

$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($userId);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(401);
    exit;
}

$stmt->close();

$data = json_encode([
    'channel_id' => $m[1],
    'user_id' => (string)$userId
]);

$fp = fsockopen(
    '127.0.0.1',
    8082,
    $errno,
    $errstr,
    2
);

if ($fp) {
    $request =
        "POST /typing HTTP/1.1\r\n" .
        "Host: 127.0.0.1:8082\r\n" .
        "Content-Type: application/json\r\n" .
        "Content-Length: " . strlen($data) . "\r\n" .
        "Connection: close\r\n\r\n" .
        $data;

    fwrite($fp, $request);
    fclose($fp);
}