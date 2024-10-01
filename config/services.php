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

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'proxy' => env('PROXY_ADDR'),

    'rt' => [
        'bb_session' => env('RT_BB_SESSION'),

        'forums' => [
            '187' => [
                'name' => 'Классика мирового кинематографа',
                'type' => 'movies'
            ],

            '2090' => [
                'name' => 'Фильмы до 1990 года',
                'type' => 'movies'
            ],

            '2221' => [
                'name' => 'Фильмы 1991-2000',
                'type' => 'movies'
            ],

            '2091' => [
                'name' => 'Фильмы 2001-2005',
                'type' => 'movies'
            ],

            '2092' => [
                'name' => 'Фильмы 2006-2010',
                'type' => 'movies'
            ],

            '2093' => [
                'name' => 'Фильмы 2011-2015',
                'type' => 'movies'
            ],

            '2200' => [
                'name' => 'Фильмы 2016-2020',
                'type' => 'movies'
            ],

            '1950' => [
                'name' => 'Фильмы 2021-2023',
                'type' => 'movies'
            ],

            '252' => [
                'name' => 'Фильмы 2024',
                'type' => 'movies'
            ],

            '2540' => [
                'name' => 'Фильмы Ближнего Зарубежья',
                'type' => 'movies'
            ],

            '934' => [
                'name' => 'Азиатские фильмы',
                'type' => 'movies'
            ]
        ]
    ]

];
