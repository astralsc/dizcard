<?php
http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

preg_match('#/channels/([0-9]+)/messages#', $_SERVER['REQUEST_URI'], $match);
$channelId = $match[1] ?? '1';

$token = trim(getallheaders()['Authorization'] ?? '');

if (!$token) {
    http_response_code(401);
    exit(json_encode(["message" => "Unauthorized"]));
}

$stmt = $DBReq->prepare("SELECT id, username, discriminator FROM users WHERE token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(401);
    exit(json_encode(["message" => "Unauthorized"]));
}

$uploadDir = __DIR__ . '/../../../../uploads/attachments/';
$uploadUrl = 'http://discordapp.com/uploads/attachments/';

if ($method === 'POST') {
    $content = '';
    if (isset($_POST['content'])) {
        $content = trim((string)$_POST['content']);
    } else {
        $json = json_decode(file_get_contents('php://input'), true);
        $content = trim((string)($json['content'] ?? ''));
    }
    $nonce = $_POST['nonce'] ?? null;
    $messageId = time() . mt_rand(100000, 999999);
    $createdAt = date('Y-m-d H:i:s');
    $attachments = [];

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        exit(json_encode(["message" => "Could not create upload directory"]));
    }

    foreach ($_FILES as $file) {
        $names = is_array($file['name']) ? $file['name'] : [$file['name']];
        $tmps = is_array($file['tmp_name']) ? $file['tmp_name'] : [$file['tmp_name']];
        $sizes = is_array($file['size']) ? $file['size'] : [$file['size']];
        $types = is_array($file['type']) ? $file['type'] : [$file['type']];
        $errors = is_array($file['error']) ? $file['error'] : [$file['error']];

        foreach ($names as $i => $name) {
            if (($errors[$i] ?? 1) !== UPLOAD_ERR_OK) continue;

            $tmp = $tmps[$i];
            if (!is_uploaded_file($tmp)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $filename = $messageId . '-' . bin2hex(random_bytes(12));
            if ($ext) $filename .= ".$ext";

            $destination = $uploadDir . $filename;
            if (!move_uploaded_file($tmp, $destination)) continue;

            $type = $types[$i] ?: 'application/octet-stream';
            $info = @getimagesize($destination);

            $attachment = [
                "id" => $messageId . '-' . ($i + 1),
                "filename" => $filename,
                "size" => (int)$sizes[$i],
                "url" => $uploadUrl . $filename,
                "proxy_url" => $uploadUrl . $filename,
                "content_type" => $type
            ];

            if ($info) {
                $attachment["width"] = $info[0];
                $attachment["height"] = $info[1];
                $attachment["original_content_type"] = $type;
            }

            $attachments[] = $attachment;
        }
    }

    if ($content === '' && !$attachments) {
        http_response_code(400);
        exit(json_encode(["message" => "Content or attachment is required"]));
    }

    $stmt = $DBReq->prepare(
        "INSERT INTO messages (id, channel_id, author_id, content, nonce, created_at)
         VALUES (?,?,?,?,?,?)"
    );

    if (!$stmt) {
        http_response_code(500);
        exit(json_encode(["message" => "Database prepare failed", "error" => $DBReq->error]));
    }

    $authorId = (string)$user['id'];
    $stmt->bind_param('ssssss', $messageId, $channelId, $authorId, $content, $nonce, $createdAt);

    if (!$stmt->execute()) {
        http_response_code(500);
        exit(json_encode(["message" => "Database execute failed", "error" => $stmt->error]));
    }

    $stmt->close();

    echo json_encode([
        "type" => 0,
        "guild_id" => null,
        "id" => $messageId,
        "content" => $content,
        "channel_id" => (string)$channelId,
        "author" => [
            "id" => (string)$user['id'],
            "username" => $user['username'],
            "discriminator" => (string)$user['discriminator'],
            "avatar" => null,
            "bot" => false,
            "flags" => 0,
            "premium" => true
        ],
        "attachments" => $attachments,
        "embeds" => [],
        "mentions" => [],
        "mention_everyone" => false,
        "mention_roles" => [],
        "nonce" => $nonce,
        "edited_timestamp" => null,
        "timestamp" => gmdate('Y-m-d\TH:i:s.v\Z'),
        "flags" => 0,
        "components" => [],
        "reactions" => [],
        "tts" => false,
        "pinned" => false
    ]);

    exit;
}

if ($method === 'GET') {

    $stmt = $DBReq->prepare(
        "SELECT m.*, u.username, u.discriminator
         FROM messages m
         JOIN users u ON u.id=m.author_id
         WHERE m.channel_id=?
         ORDER BY m.created_at ASC"
    );

    if (!$stmt) {
        http_response_code(500);
        exit(json_encode(["message" => "Database prepare failed", "error" => $DBReq->error]));
    }

    $stmt->bind_param('s', $channelId);
    $stmt->execute();

    $result = $stmt->get_result();
    $messages = [];

    while ($row = $result->fetch_assoc()) {
        $messageId = (string)$row['id'];
        $attachments = [];

        foreach (glob($uploadDir . $messageId . '-*') ?: [] as $filePath) {
            if (!is_file($filePath)) continue;

            $filename = basename($filePath);
            $mime = function_exists('mime_content_type')
                ? mime_content_type($filePath)
                : 'application/octet-stream';

            $attachment = [
                "id" => $messageId . '-' . (count($attachments) + 1),
                "filename" => $filename,
                "size" => (int)filesize($filePath),
                "url" => $uploadUrl . $filename,
                "proxy_url" => $uploadUrl . $filename,
                "content_type" => $mime
            ];

            $info = @getimagesize($filePath);

            if ($info) {
                $attachment["width"] = $info[0];
                $attachment["height"] = $info[1];
                $attachment["original_content_type"] = $mime;
            }

            $attachments[] = $attachment;
        }

        $messages[] = [
            "type" => 0,
            "guild_id" => null,
            "id" => $messageId,
            "content" => $row['content'],
            "channel_id" => (string)$row['channel_id'],
            "author" => [
                "id" => (string)$row['author_id'],
                "username" => $row['username'],
                "discriminator" => (string)$row['discriminator'],
                "avatar" => null,
                "bot" => false,
                "flags" => 0,
                "premium" => true
            ],
            "attachments" => $attachments,
            "embeds" => [],
            "mentions" => [],
            "mention_everyone" => false,
            "mention_roles" => [],
            "nonce" => $row['nonce'],
            "edited_timestamp" => !empty($row['edited_at'])
                ? gmdate('Y-m-d\TH:i:s.v\Z', strtotime($row['edited_at']))
                : null,
            "timestamp" => gmdate('Y-m-d\TH:i:s.v\Z', strtotime($row['created_at'])),
            "flags" => 0,
            "components" => [],
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