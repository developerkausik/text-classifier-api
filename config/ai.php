<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Driver
    |--------------------------------------------------------------------------
    |
    | "ollama" (default) runs a local model through Ollama, free and offline.
    | "claude" calls the Anthropic API and needs ANTHROPIC_API_KEY. "fake"
    | returns a canned response so the API can be run without any model.
    |
    */

    'driver' => env('AI_DRIVER', 'ollama'),

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('AI_MODEL', 'claude-opus-5-5'),

        // Classification is a simple task, so low effort keeps latency and cost down.
        'effort' => env('AI_EFFORT', 'low'),

        'max_tokens' => (int) env('AI_MAX_TOKENS', 2048),
        'timeout' => (float) env('AI_TIMEOUT', 30),
        'max_retries' => (int) env('AI_MAX_RETRIES', 2),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2'),

        // Local models can be slow, especially on the first call while the model loads.
        'timeout' => (float) env('OLLAMA_TIMEOUT', 120),
    ],

];
