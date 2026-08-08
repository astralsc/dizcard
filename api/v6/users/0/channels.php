<?php
http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim(getallheaders()['Authorization'] ?? '');
if (!$token) {
    http_response_code(401);
    echo json_encode([
        'message' => 'Authentication required'
    ]);
    exit;
}

$stmt = $DBReq->prepare(
    'SELECT id, username, discriminator, email, settings
     FROM users
     WHERE token=?
     LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Database error',
        'error' => $DBReq->error
    ]);
    exit;
}

$stmt->bind_param('s', $token);
$stmt->execute();

$result = $stmt->get_result();
$me = $result->fetch_assoc();

$stmt->close();

if (!$me) {
    http_response_code(401);
    echo json_encode([
        'message' => 'Invalid token'
    ]);
    exit;
}

$recipients = $input['recipients'] ?? [];

if (!is_array($recipients) || !$recipients) {
    http_response_code(400);
    echo json_encode([
        'message' => 'Recipients are required'
    ]);
    exit;
}

$recipients = array_values(
    array_unique(
        array_map('intval', $recipients)
    )
);

$recipients = array_values(
    array_filter($recipients, function ($id) use ($me) {
        return $id > 0 && $id != (int)$me['id'];
    })
);

if (!$recipients) {
    http_response_code(400);
    echo json_encode([
        'message' => 'At least one recipient is required'
    ]);
    exit;
}

$ids = array_merge(
    [(int)$me['id']],
    $recipients
);

$ids = array_values(
    array_unique($ids)
);

$placeholders = implode(
    ',',
    array_fill(0, count($ids), '?')
);

$types = str_repeat('i', count($ids));

$stmt = $DBReq->prepare(
    "SELECT id, username, discriminator, email, settings
     FROM users
     WHERE id IN ($placeholders)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Database error',
        'error' => $DBReq->error
    ]);
    exit;
}

$stmt->bind_param($types, ...$ids);
$stmt->execute();

$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

if (count($users) !== count($ids)) {
    http_response_code(404);
    echo json_encode([
        'message' => 'One or more users do not exist'
    ]);
    exit;
}

function multiplyString($number, $multiplier) {
    $carry = 0;
    $result = '';

    for ($i = strlen($number) - 1; $i >= 0; $i--) {

        $value =
            ((int)$number[$i] * $multiplier) +
            $carry;

        $result = ($value % 10) . $result;

        $carry = intdiv(
            $value,
            10
        );
    }

    while ($carry > 0) {

        $result =
            ($carry % 10) .
            $result;

        $carry = intdiv(
            $carry,
            10
        );
    }

    return ltrim($result, '0') ?: '0';
}

function addString($a, $b) {

    $i = strlen($a) - 1;
    $j = strlen($b) - 1;

    $carry = 0;
    $result = '';

    while ($i >= 0 || $j >= 0 || $carry) {

        $x = $i >= 0
            ? (int)$a[$i]
            : 0;

        $y = $j >= 0
            ? (int)$b[$j]
            : 0;

        $value = $x + $y + $carry;

        $result =
            ($value % 10) .
            $result;

        $carry = intdiv(
            $value,
            10
        );

        $i--;
        $j--;
    }

    return ltrim($result, '0') ?: '0';
}

$timestamp = (string)(
    (int)(microtime(true) * 1000)
    - 1420070400000
);

$sequence = random_int(0, 4095);

$id = multiplyString(
    $timestamp,
    4194304
);

$id = addString(
    $id,
    (string)$sequence
);

try {
    $DBReq->begin_transaction();

    $stmt = $DBReq->prepare(
        'INSERT INTO group_channels
        (id, type, owner_id, name, icon, created_at)
        VALUES (?, 3, ?, NULL, NULL, NOW())'
    );

    if (!$stmt) {
        throw new Exception(
            $DBReq->error
        );
    }

    $ownerId = (int)$me['id'];

    $stmt->bind_param(
        'si',
        $id,
        $ownerId
    );

    if (!$stmt->execute()) {
        throw new Exception(
            $stmt->error
        );
    }

    $stmt->close();

    $stmt = $DBReq->prepare(
        'INSERT INTO group_channel_recipients
        (channel_id, user_id)
        VALUES (?, ?)'
    );

    if (!$stmt) {
        throw new Exception(
            $DBReq->error
        );
    }

    foreach ($ids as $userId) {
        $userId = (int)$userId;
        $stmt->bind_param(
            'si',
            $id,
            $userId
        );
        if (!$stmt->execute()) {
            throw new Exception(
                $stmt->error
            );
        }
    }

    $stmt->close();
    $DBReq->commit();
} catch (Exception $e) {
    if ($DBReq->errno === 0) {
    }

    $DBReq->rollback();

    http_response_code(500);

    echo json_encode([
        'message' => 'Failed to create group channel',
        'error' => $e->getMessage()
    ]);

    exit;
}

$outUsers = [];

foreach ($users as $u) {
    if ((int)$u['id'] === (int)$me['id']) {
        continue;
    }
    $outUsers[] = [
        'id' => (string)$u['id'],
        'username' => $u['username'],
        'discriminator' => (string)$u['discriminator'],
        'avatar' => null,
        'email' => $u['email'],
        'verified' => true,
        'bot' => false,
        'premium' => true,
        'claimed' => true,
        'mfa_enabled' => false,
        'premium_type' => 2,
        'nsfw_allowed' => true,
        'settings' => json_decode(
            $u['settings'] ?? '{}',
            true
        ) ?: []
    ];
}

echo json_encode([
    'id' => $id,
    'type' => 3,
    'name' => null,
    'icon' => null,
    'owner_id' => (string)$me['id'],
    'recipients' => $outUsers,
    'last_message_id' => null
]);