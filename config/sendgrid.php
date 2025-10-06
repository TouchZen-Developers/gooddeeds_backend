<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SendGrid Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SendGrid email service.
    |
    */

    'api_key' => config('app.sendgrid_api_key'),
    'from_email' => config('app.sendgrid_from_email'),
    'from_name' => config('app.sendgrid_from_name', 'GoodDeeds'),
];
