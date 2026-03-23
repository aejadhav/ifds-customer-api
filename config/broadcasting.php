<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'pusher'),

    'connections' => [

        // Points at the Reverb server running inside the ifds process.
        // The BFF uses this to broadcast customer notifications over the same
        // private-customer.{ifds_customer_id} channel that the portal subscribes to.
        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('REVERB_APP_KEY'),
            'secret'  => env('REVERB_APP_SECRET', ''),
            'app_id'  => env('REVERB_APP_ID', 'fuelflow-local'),
            'options' => [
                'cluster'    => 'mt1',
                'host'       => env('REVERB_HOST', '127.0.0.1'),
                'port'       => env('REVERB_PORT', 8080),
                'scheme'     => env('REVERB_SCHEME', 'http'),
                'encrypted'  => false,
                'useTLS'     => false,
            ],
        ],

        'null' => ['driver' => 'null'],

    ],

];
