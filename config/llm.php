<?php

/**
 * LLM (Large Language Model) configuration
 * Provides defaults for missing environment variables
 */
$LLM = [
    'secret_key' => getenv('LLM_SECRET_KEY') ?: '',
    'api_url' => getenv('LLM_API_URL') ?: '',
    'model' => getenv('LLM_MODEL') ?: ''
];

define('LLM', $LLM);
