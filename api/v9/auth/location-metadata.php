<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    'consent_required' => false,
    'country_code' => 'CA',
    'promotional_email_opt_in' => [
        'required' => true,
        'pre_checked' => false,
    ]
];

echo json_encode($data);