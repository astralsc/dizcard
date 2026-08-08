<?php
header('Content-Type: application/json');
include __DIR__ . '/../../../config/db.php';

$in = json_decode(file_get_contents('php://input'), true);
$h = getallheaders();
$token = trim($h['Authorization'] ?? $h['authorization'] ?? '');

if (!$token) exit(http_response_code(401));

$hasStatus = isset($in['status']);
$hasTheme = isset($in['theme']);

if (!$hasStatus && !$hasTheme) exit(http_response_code(400));

if ($hasStatus && !in_array($in['status'], ['online','idle','dnd','invisible'], true))
    exit(http_response_code(400));

if ($hasTheme && !in_array($in['theme'], ['dark','light'], true))
    exit(http_response_code(400));

$stmt = $DBReq->prepare("SELECT settings FROM users WHERE token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($data);

if (!$stmt->fetch()) {
    $stmt->close();
    exit(http_response_code(401));
}
$stmt->close();

$s = json_decode($data ?: '{}', true) ?: [];

if ($hasStatus) {
    $s['status'] = $in['status'];
    if ($in['status'] != 'invisible') $s['last_status'] = $in['status'];
}

if ($hasTheme) $s['theme'] = $in['theme'];

$json = json_encode($s);

$stmt = $DBReq->prepare("UPDATE users SET settings=? WHERE token=?");
$stmt->bind_param('ss', $json, $token);
$stmt->execute();
$stmt->close();

http_response_code(204);