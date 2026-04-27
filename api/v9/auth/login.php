<?php
http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../../config/db.php';
include __DIR__ . '/../../../config/config.php';
$isEnabled = $isLoginEnabled ?? false;
if (!$isEnabled) {
    http_response_code(429);
    echo json_encode(["date_of_birth" => ["Login is temporarily disabled. Please try again later."]]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$captcha_key = $input['captcha_key'] ?? '(null)';
$email = $input['login'] ?? '';
$password = $input['password'] ?? '';
$undelete = $input['undelete'] ?? false;

$errors = [];
if (empty($email)) {
    $errors['email'][] = 'Email is required.';
}
if (empty($password)) {
    $errors['password'][] = 'Password is required.';
}
if (!empty($errors)) {
    http_response_code(429);
    echo json_encode($errors);
    exit;
}

$stmt = $DBReq->prepare("SELECT id, password, token, settings FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(429);
    echo json_encode(["email" => ["Invalid email."]]);
    exit;
}
if (!password_verify($password, $user['password'])) {
    http_response_code(429);
    echo json_encode(["password" => ["Invalid password."]]);
    exit;
}

$newToken = bin2hex(openssl_random_pseudo_bytes(32));

$stmt = $DBReq->prepare("UPDATE users SET token = ? WHERE id = ?");
$stmt->bind_param('si', $newToken, $user['id']);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Session token failed: " . $stmt->error]);
    exit;
}

$stmt->close();

$settings = !empty($user['settings']) ? json_decode($user['settings'], true) : (object)[];

$data = [
    "token" => $newToken,
    "settings" => $settings
];

http_response_code(200);
echo json_encode($data);
exit;
