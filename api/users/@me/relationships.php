<?php
header('Content-Type: application/json');
include __DIR__ . '/../../../config/db.php';

$in = json_decode(file_get_contents('php://input'), true);

$username = trim($in['username'] ?? '');
$discriminator = trim((string)($in['discriminator'] ?? ''));

$headers = getallheaders();
$token = trim(
    $headers['Authorization']
    ?? $headers['authorization']
    ?? ''
);

if (!$token || !$username || !$discriminator) {
    http_response_code(400);
    exit;
}

$stmt = $DBReq->prepare(
    "SELECT id, settings
     FROM users
     WHERE token=?
     LIMIT 1"
);

$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->bind_result($myId, $mySettings);

if (!$stmt->fetch()) {
    http_response_code(401);
    exit;
}

$stmt->close();

$stmt = $DBReq->prepare(
    "SELECT id, username, discriminator, settings
     FROM users
     WHERE username=?
     AND discriminator=?
     LIMIT 1"
);

$stmt->bind_param(
    'ss',
    $username,
    $discriminator
);

$stmt->execute();

$stmt->bind_result(
    $friendId,
    $friendUsername,
    $friendDiscriminator,
    $friendSettings
);

if (!$stmt->fetch() || $friendId == $myId) {
    http_response_code(204);
    exit;
}

$stmt->close();

$mySettingsArray =
    json_decode($mySettings ?: '{}', true) ?: [];

$friendSettingsArray =
    json_decode($friendSettings ?: '{}', true) ?: [];

$mySettingsArray['friends'] =
    array_values(
        array_unique(
            array_map(
                'strval',
                $mySettingsArray['friends'] ?? []
            )
        )
    );

$mySettingsArray['pending_outgoing'] =
    array_values(
        array_unique(
            array_map(
                'strval',
                $mySettingsArray['pending_outgoing'] ?? []
            )
        )
    );

$friendSettingsArray['friends'] =
    array_values(
        array_unique(
            array_map(
                'strval',
                $friendSettingsArray['friends'] ?? []
            )
        )
    );

$friendSettingsArray['pending_incoming'] =
    array_values(
        array_unique(
            array_map(
                'strval',
                $friendSettingsArray['pending_incoming'] ?? []
            )
        )
    );

$friendIdString = (string)$friendId;
$myIdString = (string)$myId;

if (
    in_array(
        $friendIdString,
        $mySettingsArray['friends'],
        true
    )
) {
    http_response_code(204);
    exit;
}

if (
    in_array(
        $friendIdString,
        $mySettingsArray['pending_outgoing'],
        true
    )
) {
    http_response_code(204);
    exit;
}

if (
    in_array(
        $myIdString,
        $friendSettingsArray['pending_incoming'],
        true
    )
) {
    http_response_code(204);
    exit;
}

$mySettingsArray['pending_outgoing'][] =
    $friendIdString;

$friendSettingsArray['pending_incoming'][] =
    $myIdString;

$mySettingsJson =
    json_encode($mySettingsArray);

$friendSettingsJson =
    json_encode($friendSettingsArray);

$stmt = $DBReq->prepare(
    "UPDATE users
     SET settings=?
     WHERE id=?"
);

$stmt->bind_param(
    'si',
    $mySettingsJson,
    $myId
);

$stmt->execute();
$stmt->close();

$stmt = $DBReq->prepare(
    "UPDATE users
     SET settings=?
     WHERE id=?"
);

$stmt->bind_param(
    'si',
    $friendSettingsJson,
    $friendId
);

$stmt->execute();
$stmt->close();

echo json_encode([
    'id' => $friendIdString,
    'username' => $friendUsername,
    'discriminator' => (string)$friendDiscriminator,
    'type' => 4
]);