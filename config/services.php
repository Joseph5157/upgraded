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

    'portal' => [
        'default_sla_minutes'      => env('PORTAL_SLA_MINUTES', 20),
        'vendor_payout_per_order'  => env('PORTAL_VENDOR_PAYOUT_PER_ORDER', 30.00),
        'default_client_price'     => env('PORTAL_DEFAULT_CLIENT_PRICE', 100.00),
    ],

    'telegram' => [
        'bot_token'       => env('TELEGRAM_BOT_TOKEN'),
        'bot_username'    => env('TELEGRAM_BOT_USERNAME'),
        'vendor_chat_id'  => env('TELEGRAM_VENDOR_CHAT_ID'),
        'admin_chat_id'   => env('ADMIN_TELEGRAM_CHAT_ID'),
        'webhook_secret'  => env('TELEGRAM_WEBHOOK_SECRET', env('TELEGRAM_BOT_SECRET')),
        'testing_fake'    => env('TELEGRAM_FAKE_IN_TESTS', true),
    ],


];
