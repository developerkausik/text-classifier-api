<?php

namespace Tests\Feature;

use App\Contracts\AiClient;
use App\Exceptions\AiServiceException;
use App\Services\Ai\OllamaClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaClientTest extends TestCase
{
    private function client(): OllamaClient
    {
        return new OllamaClient('http://ollama.test', 'llama3.2', 5);
    }

    public function test_it_sends_the_prompt_and_schema_and_parses_the_reply(): void
    {
        Http::fake([
            'ollama.test/api/chat' => Http::response([
                'model' => 'llama3.2',
                'message' => ['role' => 'assistant', 'content' => '{"category":"Query","confidence":"high","reason":"Asks about shipping."}'],
                'done' => true,
                'done_reason' => 'stop',
            ]),
        ]);

        $result = $this->client()->structured('system prompt', 'user prompt', ['type' => 'object']);

        $this->assertSame(['category' => 'Query', 'confidence' => 'high', 'reason' => 'Asks about shipping.'], $result->data);
        $this->assertSame('llama3.2', $result->model);

        Http::assertSent(fn (Request $request) => $request['model'] === 'llama3.2'
            && $request['messages'][0] === ['role' => 'system', 'content' => 'system prompt']
            && $request['messages'][1] === ['role' => 'user', 'content' => 'user prompt']
            && $request['format'] === ['type' => 'object']
            && $request['stream'] === false);
    }

    public function test_ollama_not_running_returns_502(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect to localhost port 11434'));

        try {
            $this->client()->structured('s', 'p', []);
            $this->fail('Expected AiServiceException');
        } catch (AiServiceException $e) {
            $this->assertSame(502, $e->status);
            $this->assertSame('ai_unavailable', $e->errorCode);
        }
    }

    public function test_slow_model_returns_504(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 5000 milliseconds'));

        try {
            $this->client()->structured('s', 'p', []);
            $this->fail('Expected AiServiceException');
        } catch (AiServiceException $e) {
            $this->assertSame(504, $e->status);
            $this->assertSame('ai_timeout', $e->errorCode);
        }
    }

    public function test_missing_model_explains_how_to_install_it(): void
    {
        Http::fake(['ollama.test/*' => Http::response(['error' => "model 'llama3.2' not found"], 404)]);

        try {
            $this->client()->structured('s', 'p', []);
            $this->fail('Expected AiServiceException');
        } catch (AiServiceException $e) {
            $this->assertSame(502, $e->status);
            $this->assertSame('ai_model_not_found', $e->errorCode);
            $this->assertStringContainsString('ollama pull llama3.2', $e->getMessage());
        }
    }

    public function test_invalid_json_from_model_is_rejected(): void
    {
        Http::fake(['ollama.test/*' => Http::response(['model' => 'llama3.2', 'message' => ['content' => 'not json'], 'done_reason' => 'stop'])]);

        $this->expectException(AiServiceException::class);
        $this->expectExceptionMessage('The model did not return valid JSON.');

        $this->client()->structured('s', 'p', []);
    }

    public function test_ollama_driver_is_selected_from_config(): void
    {
        config(['ai.driver' => 'ollama']);

        $this->assertInstanceOf(OllamaClient::class, $this->app->make(AiClient::class));
    }
}
