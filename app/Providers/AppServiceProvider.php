<?php

namespace App\Providers;

use Anthropic\Client;
use Anthropic\RequestOptions;
use App\Contracts\AiClient;
use App\Exceptions\AiServiceException;
use App\Services\Ai\ClaudeClient;
use App\Services\Ai\FakeAiClient;
use App\Services\Ai\OllamaClient;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiClient::class, function () {
            $driver = config('ai.driver');

            return match ($driver) {
                'fake' => new FakeAiClient,
                'claude' => $this->makeClaudeClient(config('ai.anthropic')),
                'ollama' => new OllamaClient(
                    config('ai.ollama.base_url'),
                    config('ai.ollama.model'),
                    config('ai.ollama.timeout'),
                ),
                default => throw new InvalidArgumentException("Unknown AI_DRIVER [{$driver}]. Use \"claude\", \"ollama\" or \"fake\"."),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeClaudeClient(array $config): ClaudeClient
    {
        if (blank($config['api_key'])) {
            throw AiServiceException::notConfigured();
        }

        $client = new Client(
            apiKey: $config['api_key'],
            requestOptions: RequestOptions::with(
                timeout: $config['timeout'],
                maxRetries: $config['max_retries'],
            ),
        );

        return new ClaudeClient($client, $config['model'], $config['effort'], $config['max_tokens']);
    }
}
