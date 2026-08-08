<?php

http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../config/db.php';
include __DIR__ . '/../../config/config.php';

$isEnabled = $isRegisterEnabled ?? false;

if (!$isEnabled) {
    http_response_code(429);
    echo json_encode([
        "date_of_birth" => [
            "Registration is temporarily disabled. Please try again later."
        ]
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

$errors = [];

if (empty($username)) {
    $errors['username'][] = 'Username is required.';
} elseif (strlen($username) < 2 || strlen($username) > 32) {
    $errors['username'][] = 'Username must be between 2 and 32 characters.';
}

if (empty($email)) {
    $errors['email'][] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'][] = 'Invalid email address.';
}

if (empty($password)) {
    $errors['password'][] = 'Password is required.';
} elseif (strlen($password) < 6 || strlen($password) > 72) {
    $errors['password'][] = 'Password must be between 6 and 72 characters.';
}

if (!empty($errors)) {
    http_response_code(429);
    echo json_encode($errors);
    exit;
}

$stmt = $DBReq->prepare(
    "SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "error" => ["Prepare (select) failed: " . $DBReq->error]
    ]);
    exit;
}

$stmt->bind_param('ss', $email, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(429);
    echo json_encode([
        "email" => [
            "An account with that email or username already exists."
        ]
    ]);
    $stmt->close();
    exit;
}

$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$token = bin2hex(random_bytes(32));
$discriminator = str_pad(
    (string) random_int(0, 9999),
    4,
    '0',
    STR_PAD_LEFT
);

$settings = json_encode([
    "locale" => "en-US",
    "theme" => "dark",
    "status" => "online",
    "inline_embed_media" => true,
    "inline_attachment_media" => true,
    "render_embeds" => true,
    "render_reactions" => true,
    "show_current_game" => true,
    "default_guilds_restricted" => false,
    "explicit_content_filter" => 0,
    "friend_source_flags" => [
        "all" => true
    ],
    "guild_positions" => [],
    "guild_folders" => [],
    "restricted_guilds" => [],
    "message_display_compact" => false,
    "convert_emoticons" => true,
    "animate_emoji" => true,
    "developer_mode" => false,
    "detect_platform_accounts" => true,
    "disable_games_tab" => false,
    "enable_tts_command" => true
]);

$createdAt = date('Y-m-d H:i:s');

$stmt = $DBReq->prepare(
    "INSERT INTO users
    (username, discriminator, email, password, token, settings, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "error" => ["Prepare (insert) failed: " . $DBReq->error]
    ]);
    exit;
}

$stmt->bind_param(
    'sssssss',
    $username,
    $discriminator,
    $email,
    $hashedPassword,
    $token,
    $settings,
    $createdAt
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        "error" => ["Execute failed: " . $stmt->error]
    ]);
    $stmt->close();
    exit;
}

$stmt->close();

echo json_encode([
    "token" => $token
]);