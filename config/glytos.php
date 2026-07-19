<?php

declare(strict_types=1);

use Glytos\Client;

return [
    // Your organization API key (starts with "gly_").
    'api_key' => env('GLYTOS_API_KEY', ''),

    // API base URL; override to target a regional stack.
    'base_url' => env('GLYTOS_BASE_URL', Client::DEFAULT_BASE_URL),

    // The environment to act in: "dev", "staging", "prod", or an environment uuid.
    // Leave null for the organization's default (Development) environment.
    'environment' => env('GLYTOS_ENVIRONMENT'),
];
