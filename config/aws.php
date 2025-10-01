<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AWS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS services including S3 for file storage.
    |
    */

    'credentials' => [
        'key' => config('app.aws_access_key_id'),
        'secret' => config('app.aws_secret_access_key'),
    ],

    'region' => config('app.aws_default_region', 'us-east-1'),

    'version' => 'latest',

    's3' => [
        'bucket' => config('app.aws_bucket'),
        'region' => config('app.aws_default_region', 'us-east-1'),
        'url' => config('app.aws_url'),
        'endpoint' => config('app.aws_endpoint'),
        'use_path_style_endpoint' => config('app.aws_use_path_style_endpoint', false),
    ],

    'cloudfront' => [
        'url' => config('app.cloudfront_url'),
    ],
];
