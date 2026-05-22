<?php

return [
    'dispatch' => [
        'queue' => env('MAIL_CAMPAIGN_DISPATCH_QUEUE', 'default'),
        'max_per_minute' => (int) env('MAIL_CAMPAIGN_MAX_PER_MINUTE', 50),
        'lock_seconds' => (int) env('MAIL_CAMPAIGN_RECIPIENT_LOCK_SECONDS', 120),
        'release_after_seconds' => (int) env('MAIL_CAMPAIGN_RELEASE_AFTER_SECONDS', 15),
        'tries' => (int) env('MAIL_CAMPAIGN_TRIES', 50),
        'max_exceptions' => (int) env('MAIL_CAMPAIGN_MAX_EXCEPTIONS', 5),
        'backoff_seconds' => [
            60,
            300,
            900,
        ],
    ],
    'exports' => [
        'queue' => env('MAIL_CAMPAIGN_EXPORT_QUEUE', 'default'),
        'disk' => env('MAIL_CAMPAIGN_EXPORT_DISK', 'local'),
        'directory' => env('MAIL_CAMPAIGN_EXPORT_DIRECTORY', 'mail-exports/pdf'),
    ],
];
