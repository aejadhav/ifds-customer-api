<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'reverb' => [
        'app_key' => env('REVERB_APP_KEY'),
        'host'    => env('REVERB_HOST', '127.0.0.1'),
        'port'    => env('REVERB_PORT', 8080),
        'scheme'  => env('REVERB_SCHEME', 'http'),
    ],

    'bff' => [
        'internal_secret' => env('BFF_INTERNAL_SECRET'),
        'ifds_queue'      => env('IFDS_QUEUE_NAME', 'bff_customer'),
    ],

    'sms' => [
        'provider'          => env('SMS_PROVIDER', 'log'),
        'twofactor_api_key' => env('TWOFACTOR_API_KEY'),
    ],

];
