<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use Throwable;

/**
 * Offline stand-in for the real model. Used when AI_DRIVER=fake and in tests.
 */
final class FakeAiClient implements AiClient
{
    /** @var list<array{system: string, prompt: string, schema: array<string, mixed>}> */
    public array $calls = [];

    /**
     * @param  array<string, mixed>|Throwable|null  $response  Data to return, or an exception to throw.
     */
    public function __construct(private readonly array|Throwable|null $response = null) {}

    public function structured(string $system, string $prompt, array $schema): AiResult
    {
        $this->calls[] = compact('system', 'prompt', 'schema');

        if ($this->response instanceof Throwable) {
            throw $this->response;
        }

        return new AiResult(
            data: $this->response ?? [
                'category' => 'Other',
                'confidence' => 'low',
                'reason' => 'Fake AI driver is enabled, so no model was called.',
            ],
            model: 'fake',
            latencyMs: 0,
        );
    }
}
