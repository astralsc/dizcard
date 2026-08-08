<?php

header('Content-Type: application/json');

include __DIR__ . '/../../../../../config/db.php';

$h = getallheaders();
$token = trim($h['Authorization'] ?? $h['authorization'] ?? '');
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (!$token) {
    http_response_code(401);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$friendId = (string)end($parts);

if (!$friendId || !ctype_digit($friendId)) {
    http_response_code(400);
    exit;
}

$stmt = $DBReq->prepare(
    "SELECT id, settings FROM users WHERE token=? LIMIT 1"
);
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($myId, $mySettings);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(401);
    exit;
}

$stmt->close();
$myId = (string)$myId;

if ($friendId === $myId) {
    http_response_code(204);
    exit;
}

$stmt = $DBReq->prepare(
    "SELECT id, username, discriminator, settings
     FROM users WHERE id=? LIMIT 1"
);
$stmt->bind_param('s', $friendId);
$stmt->execute();
$stmt->bind_result($id, $username, $discriminator, $friendSettings);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(204);
    exit;
}

$stmt->close();

$me = json_decode($mySettings ?: '{}', true) ?: [];
$fr = json_decode($friendSettings ?: '{}', true) ?: [];

foreach (['friends', 'pending_incoming', 'pending_outgoing', 'blocked'] as $k) {
    $me[$k] = array_values(
        array_unique(array_map('strval', $me[$k] ?? []))
    );

    $fr[$k] = array_values(
        array_unique(array_map('strval', $fr[$k] ?? []))
    );
}

$type = $input['type'] ?? null;

// block
if ((string)$type === '2') {
    if (!in_array($friendId, $me['blocked'], true)) {
        $me['blocked'][] = $friendId;

        foreach (['friends', 'pending_incoming', 'pending_outgoing'] as $k) {
            $me[$k] = array_values(
                array_diff($me[$k], [$friendId])
            );

            $fr[$k] = array_values(
                array_diff($fr[$k], [$myId])
            );
        }

        $me['blocked'] = array_values(
            array_unique($me['blocked'])
        );
    }

    $a = json_encode($me);
    $b = json_encode($fr);

    $stmt = $DBReq->prepare(
        "UPDATE users SET settings=? WHERE id=?"
    );
    $stmt->bind_param('ss', $a, $myId);
    $stmt->execute();
    $stmt->close();

    $stmt = $DBReq->prepare(
        "UPDATE users SET settings=? WHERE id=?"
    );
    $stmt->bind_param('ss', $b, $friendId);
    $stmt->execute();
    $stmt->close();

    http_response_code(204);
    exit;
}

// unblock
if (in_array($friendId, $me['blocked'], true)) {
    $me['blocked'] = array_values(
        array_diff($me['blocked'], [$friendId])
    );

    $a = json_encode($me);

    $stmt = $DBReq->prepare(
        "UPDATE users SET settings=? WHERE id=?"
    );
    $stmt->bind_param('ss', $a, $myId);
    $stmt->execute();
    $stmt->close();

    http_response_code(204);
    exit;
}

// unfriend
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    foreach (['friends', 'pending_incoming', 'pending_outgoing'] as $k) {
        $me[$k] = array_values(
            array_diff($me[$k], [$friendId])
        );

        $fr[$k] = array_values(
            array_diff($fr[$k], [$myId])
        );
    }

    $a = json_encode($me);
    $b = json_encode($fr);

    $stmt = $DBReq->prepare(
        "UPDATE users SET settings=? WHERE id=?"
    );
    $stmt->bind_param('ss', $a, $myId);
    $stmt->execute();
    $stmt->close();

    $stmt = $DBReq->prepare(
        "UPDATE users SET settings=? WHERE id=?"
    );
    $stmt->bind_param('ss', $b, $friendId);
    $stmt->execute();
    $stmt->close();

    http_response_code(204);
    exit;
}

$incoming = in_array(
    $friendId,
    $me['pending_incoming'],
    true
);

$outgoing = in_array(
    $friendId,
    $me['pending_outgoing'],
    true
);

$myIncoming = in_array(
    $myId,
    $fr['pending_incoming'],
    true
);

$myOutgoing = in_array(
    $myId,
    $fr['pending_outgoing'],
    true
);

if (in_array($friendId, $me['friends'], true)) {
    http_response_code(204);
    exit;
}

// accept friend request
if (
    ($incoming && $myOutgoing) ||
    ($myIncoming && $outgoing)
) {
    $me['pending_incoming'] = array_values(
        array_diff(
            $me['pending_incoming'],
            [$friendId]
        )
    );

    $me['pending_outgoing'] = array_values(
        array_diff(
            $me['pending_outgoing'],
            [$friendId]
        )
    );

    $fr['pending_incoming'] = array_values(
        array_diff(
            $fr['pending_incoming'],
            [$myId]
        )
    );

    $fr['pending_outgoing'] = array_values(
        array_diff(
            $fr['pending_outgoing'],
            [$myId]
        )
    );

    $me['friends'][] = $friendId;
    $fr['friends'][] = $myId;

    $me['friends'] = array_values(
        array_unique($me['friends'])
    );

    $fr['friends'] = array_values(
        array_unique($fr['friends'])
    );

    $a = json_encode($me);
    $b = json_encode($fr);

    $stmt = $DBReq->prepare(
        "UPDATE users SET settings=? WHERE id=?"
    );
    $stmt->bind_param('ss', $a, $myId);
    $stmt->execute();
    $stmt->close();

    $stmt = $DBReq->prepare(
        "UPDATE users SET settings=? WHERE id=?"
    );
    $stmt->bind_param('ss', $b, $friendId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'accepted' => true,
        'id' => $friendId,
        'username' => $username,
        'discriminator' => (string)$discriminator
    ]);

    exit;
}

// existing
if (
    $incoming ||
    $outgoing ||
    $myIncoming ||
    $myOutgoing
) {
    http_response_code(204);
    exit;
}

// send friend request
$me['pending_outgoing'][] = $friendId;
$fr['pending_incoming'][] = $myId;

$me['pending_outgoing'] = array_values(
    array_unique($me['pending_outgoing'])
);

$fr['pending_incoming'] = array_values(
    array_unique($fr['pending_incoming'])
);

$a = json_encode($me);
$b = json_encode($fr);

$stmt = $DBReq->prepare(
    "UPDATE users SET settings=? WHERE id=?"
);
$stmt->bind_param('ss', $a, $myId);
$stmt->execute();
$stmt->close();

$stmt = $DBReq->prepare(
    "UPDATE users SET settings=? WHERE id=?"
);
$stmt->bind_param('ss', $b, $friendId);
$stmt->execute();
$stmt->close();

echo json_encode([
    'pending' => true,
    'id' => $friendId,
    'username' => $username,
    'discriminator' => (string)$discriminator
]);
?>