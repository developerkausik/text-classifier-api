<?php

namespace Tests\Feature;

use App\Contracts\AiClient;
use App\Exceptions\AiServiceException;
use App\Services\Ai\FakeAiClient;
use Tests\TestCase;

class ClassifyTextTest extends TestCase
{
    private function fakeAi(array|\Throwable $response): FakeAiClient
    {
        $fake = new FakeAiClient($response);
        $this->app->instance(AiClient::class, $fake);

        return $fake;
    }

    public function test_it_classifies_text(): void
    {
        $fake = $this->fakeAi([
            'category' => 'Complaint',
            'confidence' => 'high',
            'reason' => 'The customer reports a broken product.',
        ]);

        $response = $this->postJson('/api/classify', ['text' => 'My order arrived broken.']);

        $response->assertOk()->assertExactJson([
            'category' => 'Complaint',
            'confidence' => 0.9,
            'confidence_level' => 'high',
            'reason' => 'The customer reports a broken product.',
            'meta' => ['model' => 'fake', 'latency_ms' => 0],
        ]);

        $this->assertStringContainsString("<message>\nMy order arrived broken.\n</message>", $fake->calls[0]['prompt']);
    }

    public function test_unknown_category_from_model_falls_back_to_other(): void
    {
        $this->fakeAi(['category' => 'Spam', 'confidence' => 'high', 'reason' => '...']);

        $this->postJson('/api/classify', ['text' => 'Buy cheap watches now'])
            ->assertOk()
            ->assertJson(['category' => 'Other', 'confidence' => 0.4, 'confidence_level' => 'low']);
    }

    public function test_text_is_required(): void
    {
        $this->postJson('/api/classify', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    }

    public function test_text_must_be_a_string_of_reasonable_length(): void
    {
        $this->postJson('/api/classify', ['text' => 'hi'])->assertJsonValidationErrors('text');
        $this->postJson('/api/classify', ['text' => str_repeat('a', 5001)])->assertJsonValidationErrors('text');
        $this->postJson('/api/classify', ['text' => ['not', 'a', 'string']])->assertJsonValidationErrors('text');
    }

    public function test_api_returns_json_errors_without_accept_header(): void
    {
        $this->post('/api/classify', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    }

    public function test_ai_timeout_returns_504(): void
    {
        $this->fakeAi(AiServiceException::timeout(new \RuntimeException('timeout')));

        $this->postJson('/api/classify', ['text' => 'Where is my parcel?'])
            ->assertStatus(504)
            ->assertExactJson(['message' => 'AI service timed out.', 'error' => 'ai_timeout']);
    }

    public function test_ai_rate_limit_returns_503_with_retry_after(): void
    {
        $this->fakeAi(AiServiceException::rateLimited(new \RuntimeException('429'), '20'));

        $this->postJson('/api/classify', ['text' => 'Where is my parcel?'])
            ->assertStatus(503)
            ->assertHeader('Retry-After', '20')
            ->assertJson(['error' => 'ai_rate_limited']);
    }

    public function test_missing_api_key_returns_503(): void
    {
        config(['ai.driver' => 'claude', 'ai.anthropic.api_key' => null]);

        $this->postJson('/api/classify', ['text' => 'Where is my parcel?'])
            ->assertStatus(503)
            ->assertJson(['error' => 'ai_not_configured']);
    }
}
