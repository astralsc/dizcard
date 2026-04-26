<?php
http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../../config/db.php';
include __DIR__ . '/../../../config/config.php';
$isEnabled = $isRegisterEnabled ?? false;
if (!$isEnabled) {
    http_response_code(429);
    echo json_encode(["date_of_birth" => ["Registration is temporarily disabled. Please try again later."]]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

$errors = [];

if (empty($username)) {
    $errors['username'][] = 'Username is required.';
} elseif (strlen($username) < 3 || strlen($username) > 32) {
    $errors['username'][] = 'Username must be between 3 and 32 characters.';
}

if (empty($email)) {
    $errors['email'][] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'][] = 'Invalid email address.';
}

if (empty($password)) {
    $errors['password'][] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'][] = 'Password must be at least 8 characters.';
}

if (!empty($errors)) {
    http_response_code(429);
    echo json_encode($errors);
    exit;
}

$stmt = $DBReq->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => ["Prepare (select) failed: " . $DBReq->error]]);
    exit;
}

$stmt->bind_param('ss', $email, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(429);
    echo json_encode(["email" => ["An account with that email or username already exists."]]);
    $stmt->close();
    exit;
}
$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$token = bin2hex(random_bytes(32));
$createdAt = date('Y-m-d H:i:s');

$stmt = $DBReq->prepare(
    "INSERT INTO users (username, email, `password`, token, created_at) 
     VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => ["Prepare (insert) failed: " . $DBReq->error]]);
    exit;
}

$stmt->bind_param('sssss', $username, $email, $hashedPassword, $token, $createdAt);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => ["Execute failed: " . $stmt->error]]);
    $stmt->close();
    exit;
}
$stmt->close();

echo json_encode(["token" => $token]);