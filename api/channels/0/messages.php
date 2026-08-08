<?php

http_response_code(200);
header('Content-Type: application/json');
include __DIR__ . '/../../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

preg_match('#/channels/([0-9]+)/messages#', $_SERVER['REQUEST_URI'], $match);
$channelId = $match[1] ?? '1';

$token = trim(getallheaders()['Authorization'] ?? '');

if (!$token) {
    http_response_code(401);
    exit(json_encode(["message" => "Unauthorized"]));
}

$stmt = $DBReq->prepare("SELECT id,username,discriminator FROM users WHERE token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(401);
    exit(json_encode(["message" => "Unauthorized"]));
}

if ($method === 'POST') {
    $content = trim($input['content'] ?? '');
    $nonce = $input['nonce'] ?? null;

    if (!$content) {
        http_response_code(400);
        exit(json_encode(["message" => "Content is required"]));
    }

    $messageId = (string)(round(microtime(true) * 1000) . random_int(100, 999));
    $createdAt = date('Y-m-d H:i:s');

    $stmt = $DBReq->prepare("INSERT INTO messages (id,channel_id,author_id,content,nonce,created_at) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ssisss', $messageId, $channelId, $user['id'], $content, $nonce, $createdAt);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        "type" => 0,
        "guild_id" => null,
        "id" => $messageId,
        "content" => $content,
        "channel_id" => (string)$channelId,
        "author" => [
            "username" => $user['username'],
            "discriminator" => $user['discriminator'],
            "id" => (string)$user['id'],
            "avatar" => null,
            "bot" => false,
            "flags" => 0,
            "premium" => true
        ],
        "attachments" => [],
        "embeds" => [],
        "mentions" => [],
        "mention_everyone" => false,
        "mention_roles" => [],
        "nonce" => $nonce,
        "edited_timestamp" => null,
        "timestamp" => gmdate('Y-m-d\TH:i:s.v\Z'),
        "reactions" => [],
        "tts" => false,
        "pinned" => false
    ]);
    exit;
}

if ($method === 'GET') {
    $stmt = $DBReq->prepare("SELECT m.*,u.username,u.discriminator FROM messages m JOIN users u ON u.id=m.author_id WHERE m.channel_id=? ORDER BY m.created_at ASC");
    $stmt->bind_param('s', $channelId);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            "type" => 0,
            "guild_id" => null,
            "id" => (string)$row['id'],
            "content" => $row['content'],
            "channel_id" => (string)$row['channel_id'],
            "author" => [
                "username" => $row['username'],
                "discriminator" => $row['discriminator'],
                "id" => (string)$row['author_id'],
                "avatar" => null,
                "bot" => false,
                "flags" => 0,
                "premium" => true
            ],
            "attachments" => [],
            "embeds" => [],
            "mentions" => [],
            "mention_everyone" => false,
            "mention_roles" => [],
            "nonce" => $row['nonce'],
            "edited_timestamp" => $row['edited_at'] ? gmdate('Y-m-d\TH:i:s.v\Z', strtotime($row['edited_at'])) : null,
            "timestamp" => gmdate('Y-m-d\TH:i:s.v\Z', strtotime($row['created_at'])),
            "reactions" => [],
            "tts" => false,
            "pinned" => false
        ];
    }

    $stmt->close();
    echo json_encode($messages);
    exit;
}

http_response_code(405);
echo json_encode(["message" => "Method not allowed"]);