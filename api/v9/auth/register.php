<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    "token" => "MTQ5NzQ4MTI4NTk2NTE5MDI4Mw.adieEA.-yW86G4PN-tbkjGKceefIVL1QA7OiBHdVqgL8Q"
];

echo json_encode($data);