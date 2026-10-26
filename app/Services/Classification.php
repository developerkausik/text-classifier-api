<?php

namespace App\Services;

use App\Enums\Category;
use App\Enums\ConfidenceLevel;

final readonly class Classification
{
    public function __construct(
        public Category $category,
        public ConfidenceLevel $confidenceLevel,
        public string $reason,
        public string $model,
        public int $latencyMs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category->value,
            'confidence' => $this->confidenceLevel->score(),
            'confidence_level' => $this->confidenceLevel->value,
            'reason' => $this->reason,
            'meta' => [
                'model' => $this->model,
                'latency_ms' => $this->latencyMs,
            ],
        ];
    }
}
