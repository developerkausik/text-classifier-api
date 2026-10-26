<?php

namespace App\Services;

use App\Contracts\AiClient;
use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Prompts\ClassificationPrompt;

class ClassificationService
{
    public function __construct(private readonly AiClient $ai) {}

    public function classify(string $text): Classification
    {
        $result = $this->ai->structured(
            ClassificationPrompt::system(),
            ClassificationPrompt::user($text),
            ClassificationPrompt::schema(),
        );

        $category = Category::tryFrom((string) ($result->data['category'] ?? ''));
        $confidence = ConfidenceLevel::tryFrom((string) ($result->data['confidence'] ?? ''));

        // The schema should prevent this, but never trust a model's output blindly:
        // anything unexpected becomes a low-confidence "Other".
        if ($category === null) {
            $category = Category::Other;
            $confidence = ConfidenceLevel::Low;
        }

        return new Classification(
            category: $category,
            confidenceLevel: $confidence ?? ConfidenceLevel::Low,
            reason: (string) ($result->data['reason'] ?? ''),
            model: $result->model,
            latencyMs: $result->latencyMs,
        );
    }
}
