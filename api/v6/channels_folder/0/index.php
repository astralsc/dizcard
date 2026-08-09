<?php
http_response_code(200);
header('Content-Type: application/json');

include __DIR__ . '/../../../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

preg_match('#/channels/([0-9]+)#', $_SERVER['REQUEST_URI'], $match);
$channelId = $match[1] ?? null;

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

// Closing a DM: DELETE /channels/{id}. Only applies to 1-on-1 DMs, not groups
// (groups are left via the leave-channel endpoint instead).
if ($method === 'DELETE') {
    if (!$channelId) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Channel id is required'
        ]);
        exit;
    }

    $myId = (int)$me['id'];

    $stmt = $DBReq->prepare(
        'SELECT id, type FROM group_channels WHERE id=? LIMIT 1'
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'message' => 'Database error',
            'error' => $DBReq->error
        ]);
        exit;
    }

    $stmt->bind_param('s', $channelId);
    $stmt->execute();
    $channel = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$channel) {
        http_response_code(404);
        echo json_encode([
            'message' => 'Unknown channel'
        ]);
        exit;
    }

    if ((int)$channel['type'] !== 1) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Only DM channels can be closed this way'
        ]);
        exit;
    }

    $stmt = $DBReq->prepare(
        'SELECT 1 FROM group_channel_recipients WHERE channel_id=? AND user_id=? LIMIT 1'
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'message' => 'Database error',
            'error' => $DBReq->error
        ]);
        exit;
    }

    $stmt->bind_param('si', $channelId, $myId);
    $stmt->execute();
    $isMember = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$isMember) {
        http_response_code(403);
        echo json_encode([
            'message' => 'You are not a member of this channel'
        ]);
        exit;
    }

    // We don't delete the recipient row (that would break the other person's
    // view of the DM and its history). We just mark it closed for this user;
    // requires a `closed` TINYINT(1) DEFAULT 0 column on
    // group_channel_recipients.
    $stmt = $DBReq->prepare(
        'UPDATE group_channel_recipients SET closed=1 WHERE channel_id=? AND user_id=?'
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'message' => 'Database error',
            'error' => $DBReq->error
        ]);
        exit;
    }

    $stmt->bind_param('si', $channelId, $myId);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            'message' => 'Failed to close channel',
            'error' => $stmt->error
        ]);
        exit;
    }

    $stmt->close();

    http_response_code(204);
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

// If there's only one recipient, this is a DM, not a group channel.
$isDM = count($recipients) === 1;
$channelType = $isDM ? 1 : 3;

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

// If this is a DM, check whether one already exists between these two users
// and reuse it instead of creating a duplicate.
$existingId = null;
$existingOwnerId = null;

if ($isDM) {
    $otherUserId = $recipients[0];
    $myId = (int)$me['id'];

    $stmt = $DBReq->prepare(
        'SELECT gc.id, gc.owner_id
         FROM group_channels gc
         WHERE gc.type = 1
           AND gc.id IN (
               SELECT channel_id FROM group_channel_recipients WHERE user_id = ?
           )
           AND gc.id IN (
               SELECT channel_id FROM group_channel_recipients WHERE user_id = ?
           )
           AND (
               SELECT COUNT(*) FROM group_channel_recipients WHERE channel_id = gc.id
           ) = 2
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

    $stmt->bind_param('ii', $myId, $otherUserId);
    $stmt->execute();

    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();

    $stmt->close();

    if ($existing) {
        $existingId = $existing['id'];
        $existingOwnerId = $existing['owner_id'];
    }
}

if ($existingId !== null) {
    // Reuse the existing DM channel; no need to insert anything.
    $id = $existingId;
    $ownerId = $existingOwnerId;

    // If the requester had previously closed this DM, reopen it for them.
    $myId = (int)$me['id'];

    $stmt = $DBReq->prepare(
        'UPDATE group_channel_recipients SET closed=0 WHERE channel_id=? AND user_id=?'
    );

    if ($stmt) {
        $stmt->bind_param('si', $id, $myId);
        $stmt->execute();
        $stmt->close();
    }
} else {
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

    $ownerId = (int)$me['id'];

    try {
        $DBReq->begin_transaction();

        $stmt = $DBReq->prepare(
            'INSERT INTO group_channels
            (id, type, owner_id, name, icon, created_at)
            VALUES (?, ?, ?, NULL, NULL, NOW())'
        );

        if (!$stmt) {
            throw new Exception(
                $DBReq->error
            );
        }

        $stmt->bind_param(
            'sii',
            $id,
            $channelType,
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
            'message' => $isDM
                ? 'Failed to create DM channel'
                : 'Failed to create group channel',
            'error' => $e->getMessage()
        ]);

        exit;
    }
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
    'type' => $channelType,
    'name' => null,
    'icon' => null,
    'owner_id' => (string)$ownerId,
    'recipients' => $outUsers,
    'last_message_id' => null
]);