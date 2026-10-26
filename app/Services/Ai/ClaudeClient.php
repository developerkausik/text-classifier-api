<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\APITimeoutException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Contracts\AiClient;
use App\Exceptions\AiServiceException;

final class ClaudeClient implements AiClient
{
    public function __construct(
        private readonly Client $client,
        private readonly string $model,
        private readonly string $effort,
        private readonly int $maxTokens,
    ) {}

    public function structured(string $system, string $prompt, array $schema): AiResult
    {
        $startedAt = hrtime(true);

        try {
            // The beta endpoint is used only for server-side refusal fallbacks:
            // if the model declines, the API retries on a fallback model.
            $message = $this->client->beta->messages->create(
                maxTokens: $this->maxTokens,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: $this->model,
                system: $system,
                outputConfig: [
                    'effort' => $this->effort,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (APITimeoutException $e) {
            throw AiServiceException::timeout($e);
        } catch (RateLimitException $e) {
            throw AiServiceException::rateLimited($e, $e->response?->getHeaderLine('retry-after') ?: null);
        } catch (APIConnectionException $e) {
            throw AiServiceException::unavailable($e);
        } catch (APIStatusException $e) {
            throw AiServiceException::upstream($e);
        }

        $latencyMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        if ($message->stopReason === 'refusal') {
            throw AiServiceException::invalidResponse('The model declined to answer.');
        }

        if ($message->stopReason === 'max_tokens') {
            throw AiServiceException::invalidResponse('The model response was cut off. Increase AI_MAX_TOKENS.');
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);

                if (! is_array($data)) {
                    throw AiServiceException::invalidResponse('The model did not return valid JSON.');
                }

                return new AiResult($data, $message->model, $latencyMs);
            }
        }

        throw AiServiceException::invalidResponse('The model returned no text.');
    }
}
