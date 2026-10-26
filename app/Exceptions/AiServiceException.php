<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * A failure talking to the AI provider, already mapped to the HTTP status
 * this API should return.
 */
class AiServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status,
        public readonly ?string $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function notConfigured(): self
    {
        return new self('AI service is not configured. Set ANTHROPIC_API_KEY or use AI_DRIVER=fake.', 'ai_not_configured', 503);
    }

    public static function timeout(Throwable $previous): self
    {
        return new self('AI service timed out.', 'ai_timeout', 504, previous: $previous);
    }

    public static function rateLimited(Throwable $previous, ?string $retryAfter): self
    {
        return new self('AI service is rate limited. Try again shortly.', 'ai_rate_limited', 503, $retryAfter, $previous);
    }

    public static function unavailable(Throwable $previous): self
    {
        return new self('AI service is unreachable.', 'ai_unavailable', 502, previous: $previous);
    }

    public static function upstream(Throwable $previous): self
    {
        return new self('AI service returned an error.', 'ai_error', 502, previous: $previous);
    }

    public static function invalidResponse(string $message): self
    {
        return new self($message, 'ai_invalid_response', 502);
    }

    public function render(): JsonResponse
    {
        $headers = $this->retryAfter !== null ? ['Retry-After' => $this->retryAfter] : [];

        return response()->json([
            'message' => $this->getMessage(),
            'error' => $this->errorCode,
        ], $this->status, $headers);
    }
}
