<?php

namespace App\Enums;

/**
 * The model reports a confidence level, not a probability. Each level is
 * mapped to a fixed score so API clients get a number they can threshold on.
 */
enum ConfidenceLevel: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function score(): float
    {
        return match ($this) {
            self::High => 0.9,
            self::Medium => 0.7,
            self::Low => 0.4,
        };
    }
}
