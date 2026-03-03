<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contact Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for contact management system including spam detection
    | and rate limiting settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | IP Blacklist
    |--------------------------------------------------------------------------
    |
    | List of IP addresses that are blacklisted and will be automatically
    | marked as spam.
    |
    */
    'ip_blacklist' => env('CONTACT_IP_BLACKLIST', '') ? explode(',', env('CONTACT_IP_BLACKLIST')) : [],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum number of contacts allowed per hour from the same email/IP.
    |
    */
    'rate_limit_per_hour' => env('CONTACT_RATE_LIMIT', 5),

    /*
    |--------------------------------------------------------------------------
    | Spam Detection Threshold
    |--------------------------------------------------------------------------
    |
    | Score threshold for spam detection. Contacts with score >= threshold
    | will be automatically marked as spam.
    |
    */
    'spam_threshold' => env('CONTACT_SPAM_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Maximum file size and allowed MIME types for contact attachments.
    |
    */
    'attachment_max_size' => 10240, // 10MB in KB
    'attachment_allowed_mimes' => [
        'jpg', 'jpeg', 'png', 'gif',
        'pdf',
        'doc', 'docx',
        'xls', 'xlsx',
    ],
];

