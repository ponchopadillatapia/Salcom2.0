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

    'salcom_api' => [
        'token' => env('SALCOM_API_TOKEN', ''),
    ],

    'groq' => [
        'url'     => env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'),
        'api_key' => env('GROQ_API_KEY', ''),
        'model'   => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'timeout' => (int) env('GROQ_TIMEOUT', 30),
    ],

    // ── Twilio (WhatsApp Business API) ──
    'twilio' => [
        'sid'            => env('TWILIO_SID', ''),
        'token'          => env('TWILIO_AUTH_TOKEN', ''),
        'whatsapp_from'  => env('TWILIO_WHATSAPP_FROM', ''),  // whatsapp:+14155238886
    ],

    // ── Validación RFC ante SAT (RENAPO / lista 69-B) ──
    'sat' => [
        'rfc_url'  => env('SAT_RFC_VALIDATION_URL', 'https://api.el-sat.com/v1'),
        'api_key'  => env('SAT_API_KEY', ''),
        'timeout'  => (int) env('SAT_TIMEOUT', 10),
    ],

    // ── PAC para timbrado CFDI (Facturama, SW Sapien, Diverza) ──
    'pac' => [
        'driver'   => env('PAC_DRIVER', 'facturama'),  // facturama | sw_sapien | diverza
        'url'      => env('PAC_API_URL', ''),
        'user'     => env('PAC_API_USER', ''),
        'password' => env('PAC_API_PASSWORD', ''),
        'sandbox'  => env('PAC_SANDBOX', true),
        'timeout'  => (int) env('PAC_TIMEOUT', 30),
    ],

    // ── Paqueterías (tracking) ──
    'paqueterias' => [
        'estafeta' => [
            'url'     => env('ESTAFETA_API_URL', ''),
            'api_key' => env('ESTAFETA_API_KEY', ''),
        ],
        'dhl' => [
            'url'     => env('DHL_API_URL', 'https://api-eu.dhl.com/track/shipments'),
            'api_key' => env('DHL_API_KEY', ''),
        ],
        'fedex' => [
            'url'       => env('FEDEX_API_URL', ''),
            'api_key'   => env('FEDEX_API_KEY', ''),
            'secret_key'=> env('FEDEX_SECRET_KEY', ''),
        ],
    ],
];
