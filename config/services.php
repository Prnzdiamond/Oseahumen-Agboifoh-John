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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    // ── Frontend / API gate ───────────────────────────────────────────────────
    // These MUST be read through config() (not env()) everywhere in the app.
    // Deploys run `php artisan optimize` (config:cache); after that env() returns
    // null, so any env() call in middleware silently breaks the origin gate.
    // Reading env() here — inside a config file — is correct: config files are
    // evaluated once at cache time and the resolved values are baked in.
    //   allowed_origins — comma-separated list checked against Origin/Referer.
    //   server_token    — shared secret for trusted server-to-server callers
    //                     (Nuxt SSR / sitemap) sent via the X-Server-Token header.
    'frontend' => [
        'allowed_origins' => env('ALLOWED_ORIGINS', ''),
        'server_token' => env('SERVER_API_TOKEN', ''),
    ],

];
