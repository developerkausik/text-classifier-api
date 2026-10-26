<?php

namespace Tests\Unit;

use App\Enums\ConfidenceLevel;
use PHPUnit\Framework\TestCase;

class ConfidenceLevelTest extends TestCase
{
    public function test_each_level_maps_to_a_fixed_score(): void
    {
        $this->assertSame(0.9, ConfidenceLevel::High->score());
        $this->assertSame(0.7, ConfidenceLevel::Medium->score());
        $this->assertSame(0.4, ConfidenceLevel::Low->score());
    }
}
