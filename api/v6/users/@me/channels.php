<?php

http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$recipients = $input['recipients'] ?? [];

if (!is_array($recipients) || count($recipients) !== 1) {
    http_response_code(400);
    exit;
}

$recipient = (int)$recipients[0];
$token = trim(getallheaders()['Authorization'] ?? getallheaders()['authorization'] ?? '');

if (!$token || !$recipient) {
    http_response_code(401);
    exit;
}

$stmt = $DBReq->prepare(
    "SELECT id FROM users WHERE token=? LIMIT 1"
);
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($myId);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(401);
    exit;
}

$stmt->close();

$stmt = $DBReq->prepare(
    "SELECT id FROM users WHERE id=? LIMIT 1"
);
$stmt->bind_param('i', $recipient);
$stmt->execute();
$stmt->bind_result($recipientId);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    exit;
}

$stmt->close();

$a = min((int)$myId, $recipient);
$b = max((int)$myId, $recipient);

$stmt = $DBReq->prepare(
    "SELECT id FROM channels
     WHERE user_id_1=? AND user_id_2=?
     LIMIT 1"
);
$stmt->bind_param('ii', $a, $b);
$stmt->execute();
$stmt->bind_result($channelId);

if ($stmt->fetch()) {
    $stmt->close();

    echo json_encode([
        'id' => (string)$channelId,
        'type' => 1,
        'recipients' => [(string)$recipient],
        'last_message_id' => null
    ]);
    exit;
}

$stmt->close();

$channelId = (string)(
    (int)(microtime(true) * 1000) - 1420070400000
);

$channelId .= (string)random_int(1000, 9999);

$stmt = $DBReq->prepare(
    "INSERT INTO channels
     (id, type, user_id_1, user_id_2, created_at)
     VALUES (?, 1, ?, ?, NOW())"
);

$stmt->bind_param('sii', $channelId, $a, $b);

if (!$stmt->execute()) {
    http_response_code(500);
    exit;
}

$stmt->close();

echo json_encode([
    'id' => $channelId,
    'type' => 1,
    'recipients' => [(string)$recipient],
    'last_message_id' => null
]);