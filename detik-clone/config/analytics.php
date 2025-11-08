<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the analytics system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Analytics Salt
    |--------------------------------------------------------------------------
    |
    | This salt is used for hashing sensitive analytics data when user consent
    | is not provided. This should be a random string and should not be the
    | same as your application key for security reasons.
    | 
    | REQUIRED: This value must be set in your .env file as ANALYTICS_SALT
    | The application will throw a RuntimeException if this is not configured.
    |
    */

    'salt' => env('ANALYTICS_SALT'),

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep analytics data in days
    |
    */

    'retention_days' => env('ANALYTICS_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Anonymization Settings
    |--------------------------------------------------------------------------
    |
    | Settings for data anonymization
    |
    */

    'anonymize_ip' => env('ANALYTICS_ANONYMIZE_IP', true),
    'hash_user_agents' => env('ANALYTICS_HASH_USER_AGENTS', true),
];