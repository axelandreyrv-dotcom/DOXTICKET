<?php

return [
    'version' => env('DOXTICKET_VERSION', 'dev'),

    'updates' => [
        'github_repository' => env('DOXTICKET_GITHUB_REPOSITORY', 'doxsuite/doxticket'),
    ],

    'donations' => [
        'paypal_url' => env('DOXTICKET_DONATION_PAYPAL_URL'),
        'github_sponsors_url' => env('DOXTICKET_DONATION_GITHUB_SPONSORS_URL'),
        'buy_me_a_coffee_url' => env('DOXTICKET_DONATION_BUY_ME_A_COFFEE_URL'),
    ],

    'attachments' => [
        'max_bytes' => env('DOXTICKET_ATTACHMENT_MAX_BYTES', 10 * 1024 * 1024),
    ],

    'mail' => [
        'imap_validate_cert' => env('DOXTICKET_IMAP_VALIDATE_CERT', true),
    ],

    'oauth' => [
        'state_ttl_minutes' => env('DOXTICKET_OAUTH_STATE_TTL_MINUTES', 10),
        'providers' => [
            'gmail' => [
                'client_id' => env('DOXTICKET_GMAIL_CLIENT_ID'),
                'client_secret' => env('DOXTICKET_GMAIL_CLIENT_SECRET'),
                'redirect_uri' => env('DOXTICKET_GMAIL_REDIRECT_URI'),
                'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_endpoint' => 'https://oauth2.googleapis.com/token',
                'scopes' => [
                    'https://mail.google.com/',
                ],
            ],
            'microsoft365' => [
                'client_id' => env('DOXTICKET_MICROSOFT_CLIENT_ID'),
                'client_secret' => env('DOXTICKET_MICROSOFT_CLIENT_SECRET'),
                'redirect_uri' => env('DOXTICKET_MICROSOFT_REDIRECT_URI'),
                'tenant' => env('DOXTICKET_MICROSOFT_TENANT', 'organizations'),
                'authorization_endpoint' => null,
                'token_endpoint' => null,
                'scopes' => [
                    'offline_access',
                    'https://graph.microsoft.com/Mail.ReadWrite',
                    'https://graph.microsoft.com/Mail.Send',
                ],
            ],
        ],
    ],
];
