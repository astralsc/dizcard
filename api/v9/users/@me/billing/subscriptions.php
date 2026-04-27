<?php
http_response_code(200);
header('Content-Type: application/json');

$data = [
    [
        'id'                              => '1487301142647013427',
        'type'                            => 1,
        'created_at'                      => '2026-03-28T04:03:45.200547+00:00',
        'canceled_at'                     => null,
        'current_period_start'            => '2026-03-28T04:03:45.110091+00:00',
        'current_period_end'              => '2026-04-28T04:03:45.110091+00:00',
        'status'                          => 1,
        'payment_source_id'               => null,
        'payment_gateway'                 => null,
        'payment_gateway_plan_id'         => null,
        'payment_gateway_subscription_id' => null,
        'items'                           => [
            [
                'id'      => '1487301142647013426',
                'plan_id' => '511651880837840896',
                'quantity' => 1,
            ],
        ],
        'currency'                  => 'usd',
        'country_code'              => null,
        'user_id'                   => '909281565899645038',
        'pause_ends_at'             => null,
        'pause_reason'              => null,
        'use_storekit_resubscribe'  => false,
        'price'                     => null,
    ],
];

echo json_encode($data);