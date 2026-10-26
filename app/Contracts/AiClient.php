<?php

namespace App\Contracts;

use App\Exceptions\AiServiceException;
use App\Services\Ai\AiResult;

interface AiClient
{
    /**
     * Send a prompt to the model and get back JSON that matches the given schema.
     *
     * @param  array<string, mixed>  $schema  JSON Schema the response must follow.
     *
     * @throws AiServiceException
     */
    public function structured(string $system, string $prompt, array $schema): AiResult;
}
