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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

    // Rainforest API for Amazon product data
    'rainforest' => [
        'api_key' => env('RAINFOREST_API_KEY'),
        'base_url' => env('RAINFOREST_BASE_URL', 'https://api.rainforestapi.com/request'),
        'timeout' => env('RAINFOREST_TIMEOUT', 30),
    ],

    // Frontend/app URL to redirect to after successful social login
    // Example: https://app.gooddeeds.org/auth/callback or myapp://auth
    'frontend' => [
        'social_login_redirect_url' => env('SOCIAL_LOGIN_REDIRECT_URL'),
        'social_login_error_redirect_url' => env('SOCIAL_LOGIN_ERROR_REDIRECT_URL'),
    ],

];
