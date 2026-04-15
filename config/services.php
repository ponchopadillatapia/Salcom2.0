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

    'recaptcha' => [
    'site_key'   => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
],

    'proveedor_api' => [
        'url'             => env('PROVEEDOR_API_URL', ''),
        'connect_timeout' => (int) env('PROVEEDOR_API_CONNECT_TIMEOUT', 5),
        'timeout'         => (int) env('PROVEEDOR_API_TIMEOUT', 15),
        'max_retries'     => (int) env('PROVEEDOR_API_MAX_RETRIES', 3),
        'login_mode'      => env('PROVEEDOR_LOGIN_MODE', 'fallback'),
    ],

    'cliente_api' => [
        'url'             => env('CLIENTE_API_URL', ''),
        'connect_timeout' => (int) env('CLIENTE_API_CONNECT_TIMEOUT', 5),
        'timeout'         => (int) env('CLIENTE_API_TIMEOUT', 15),
        'max_retries'     => (int) env('CLIENTE_API_MAX_RETRIES', 3),
        'login_mode'      => env('CLIENTE_LOGIN_MODE', 'fallback'),
    ],

    'groq' => [
        'url'     => env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'),
        'api_key' => env('GROQ_API_KEY', ''),
        'model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'timeout' => (int) env('GROQ_TIMEOUT', 30),
    ],
];
