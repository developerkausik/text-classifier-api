<?php

namespace App\Services\Ai;

final readonly class AiResult
{
    /**
     * @param  array<string, mixed>  $data  Decoded JSON returned by the model.
     */
    public function __construct(
        public array $data,
        public string $model,
        public int $latencyMs,
    ) {}
}
