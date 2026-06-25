<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Centralised config for all Telegram bot settings. The services.telegram.*
    | keys remain for backward compatibility; new code should read from here.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', env('TELEGRAM_BOT_SECRET')),

    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'PlagExpertBot'),

    'enabled' => (bool) env('TELEGRAM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Chat IDs
    |--------------------------------------------------------------------------
    */

    'admin_chat_id' => env('ADMIN_TELEGRAM_CHAT_ID'),

    'vendor_chat_id' => env('TELEGRAM_VENDOR_CHAT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Thresholds and TTLs
    |--------------------------------------------------------------------------
    */

    /** Credit balance at or below which a low-credit alert is sent */
    'low_credit_threshold' => (int) env('TELEGRAM_LOW_CREDIT_THRESHOLD', 5),

    /** Action tokens (inline keyboard callbacks) expire after this many minutes */
    'action_token_ttl_minutes' => (int) env('TELEGRAM_ACTION_TOKEN_TTL_MINUTES', 30),

    /** Signed download links generated via Telegram expire after this many minutes */
    'download_link_ttl_minutes' => (int) env('TELEGRAM_DOWNLOAD_LINK_TTL_MINUTES', 15),

    /** Account-linking tokens (sent via /start link_<token>) expire after this many minutes */
    'link_token_ttl_minutes' => (int) env('TELEGRAM_LINK_TOKEN_TTL_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | When true, TelegramService fakes outbound calls in test environments
    | so HTTP is never actually sent during test runs.
    |
    */

    'testing_fake' => (bool) env('TELEGRAM_FAKE_IN_TESTS', true),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    */

    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org/bot'),

];
