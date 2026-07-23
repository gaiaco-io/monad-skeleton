<?php

/**
 * Mailer configuration
 * Provides defaults for missing environment variables
 */
$MAILER = [
    'provider' => getenv('MAILER_PROVIDER') ?: '',
    'api_key' => getenv('MAILER_API_KEY') ?: '',
    'sender_name' => getenv('MAILER_SENDER_NAME') ?: getenv('APP_NAME') ?: 'No-Name',
    'sender_email' => getenv('MAILER_SENDER_EMAIL') ?: ''
];

define('MAILER', $MAILER);

