<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "token" => "MTQ5NzQ3MzM5NzYxMTgyODM0MA.adiWtw.cS3-fL1367ZCDZt5i_FoTU65N3TT46zgXpjm-g",
    "settings" => (object)[]
];

echo json_encode($data);