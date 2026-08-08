<?php

http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../../config/db.php';
include __DIR__ . '/../../../config/config.php';

$isEnabled = $isLoginEnabled ?? false;

if (!$isEnabled) {
    http_response_code(429);

    echo json_encode([
        "date_of_birth" => [
            "Login is temporarily disabled. Please try again later."
        ]
    ]);

    exit;
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        "error" => [
            "Invalid JSON."
        ]
    ]);

    exit;
}

$email = trim(
    $input['login'] ?? ''
);

$password =
    $input['password'] ?? '';

$errors = [];

if (empty($email)) {
    $errors['email'][] =
        'Email is required.';
}

if (empty($password)) {
    $errors['password'][] =
        'Password is required.';
}

if (!empty($errors)) {
    http_response_code(429);

    echo json_encode($errors);

    exit;
}

$stmt = $DBReq->prepare(
    "SELECT
        id,
        password,
        token,
        settings
     FROM users
     WHERE email = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        "error" => [
            "Prepare failed: " .
            $DBReq->error
        ]
    ]);

    exit;
}

$stmt->bind_param(
    's',
    $email
);

if (!$stmt->execute()) {
    http_response_code(500);

    echo json_encode([
        "error" => [
            "Execute failed: " .
            $stmt->error
        ]
    ]);

    $stmt->close();

    exit;
}

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    http_response_code(429);

    echo json_encode([
        "email" => [
            "Invalid email."
        ]
    ]);

    exit;
}

if (!password_verify(
    $password,
    $user['password']
)) {
    http_response_code(429);

    echo json_encode([
        "password" => [
            "Invalid password."
        ]
    ]);

    exit;
}

$settings = [];

if (!empty($user['settings'])) {

    $settings = json_decode(
        $user['settings'],
        true
    );

    if (!is_array($settings)) {
        $settings = [];
    }
}

if (
    !isset($settings['status']) ||
    !in_array(
        $settings['status'],
        [
            'online',
            'idle',
            'dnd',
            'invisible'
        ],
        true
    )
) {
    $settings['status'] = 'online';
}

$settingsJson = json_encode(
    $settings
);

if ($settingsJson === false) {
    http_response_code(500);

    echo json_encode([
        "error" => [
            "Settings encode failed: " .
            json_last_error_msg()
        ]
    ]);

    exit;
}

$newToken = bin2hex(
    random_bytes(32)
);

$stmt = $DBReq->prepare(
    "UPDATE users
     SET token = ?,
         settings = ?
     WHERE id = ?"
);

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        "error" => [
            "Prepare token update failed: " .
            $DBReq->error
        ]
    ]);

    exit;
}

$stmt->bind_param(
    'ssi',
    $newToken,
    $settingsJson,
    $user['id']
);

if (!$stmt->execute()) {
    http_response_code(500);

    echo json_encode([
        "error" => [
            "Token/settings update failed: " .
            $stmt->error
        ]
    ]);

    $stmt->close();

    exit;
}

$stmt->close();

echo json_encode([
    "token" => $newToken,
    "settings" => $settings
]);

exit;