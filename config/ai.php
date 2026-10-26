<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Driver
    |--------------------------------------------------------------------------
    |
    | "claude" calls the Anthropic API. "fake" returns a canned response so the
    | API can be run and tested without an API key.
    |
    */

    'driver' => env('AI_DRIVER', 'claude'),

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('AI_MODEL', 'claude-opus-5-5'),

        // Classification is a simple task, so low effort keeps latency and cost down.
        'effort' => env('AI_EFFORT', 'low'),

        'max_tokens' => (int) env('AI_MAX_TOKENS', 2048),
        'timeout' => (float) env('AI_TIMEOUT', 30),
        'max_retries' => (int) env('AI_MAX_RETRIES', 2),
    ],

];
