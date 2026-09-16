<?php

declare(strict_types=1);

namespace WooNinja\ThinkificSaloon\GraphQL\Exceptions;

use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Throwable;

/**
 * Thrown when a Thinkific GraphQL response carries a top-level `errors` array.
 *
 * Thinkific returns system and validation errors (MAX_QUERY_COST_EXCEEDED,
 * RATE_LIMITED, BAD_USER_INPUT, FORBIDDEN, SERVER_ERROR, ...) as HTTP 200, so
 * Saloon's status-based failure detection never fires on its own. The connector
 * marks any such response as failed and builds one of these from it.
 *
 * Extends Saloon's RequestException so existing `catch (RequestException)`
 * blocks keep working.
 *
 * @see https://support.thinkific.dev/hc/en-us/articles/22166388092823-GraphQL-Error-Handling
 */
class GraphQLException extends RequestException
{
    /**
     * @param array<int, array<string, mixed>> $errors The raw `errors` array from the body
     */
    public function __construct(
        Response $response,
        protected readonly array $errors,
        ?Throwable $previous = null,
    ) {
        parent::__construct($response, static::buildMessage($errors), 0, $previous);
    }

    /**
     * Pull the top-level `errors` array out of a response body.
     *
     * Returns [] for non-JSON bodies or bodies without a populated `errors` key.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function extractErrors(Response $response): array
    {
        try {
            $errors = $response->json('errors');
        } catch (\JsonException) {
            return [];
        }

        return is_array($errors) ? array_values(array_filter($errors, 'is_array')) : [];
    }

    /**
     * Build the most specific exception for the given response.
     */
    public static function fromResponse(Response $response, ?Throwable $previous = null): static
    {
        $errors = static::extractErrors($response);
        $codes = static::codesFrom($errors);

        if (in_array(MaxQueryCostExceededException::CODE, $codes, true)) {
            return new MaxQueryCostExceededException($response, $errors, $previous);
        }

        if (in_array(GraphQLRateLimitedException::CODE, $codes, true)) {
            return new GraphQLRateLimitedException($response, $errors, $previous);
        }

        return new static($response, $errors, $previous);
    }

    /**
     * The raw `errors` array as returned by Thinkific.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Distinct `extensions.code` values across all errors, in order of first appearance.
     *
     * @return array<int, string>
     */
    public function getErrorCodes(): array
    {
        return static::codesFrom($this->errors);
    }

    public function hasErrorCode(string $code): bool
    {
        return in_array($code, $this->getErrorCodes(), true);
    }

    /**
     * The `extensions.rateLimit` block, when Thinkific included one.
     *
     * @return array{cost?: int, remaining?: int, resetAt?: string, limit?: int}|null
     */
    public function getRateLimit(): ?array
    {
        try {
            $rateLimit = $this->getResponse()->json('extensions.rateLimit');
        } catch (\JsonException) {
            return null;
        }

        return is_array($rateLimit) ? $rateLimit : null;
    }

    /**
     * @param array<int, array<string, mixed>> $errors
     * @return array<int, string>
     */
    protected static function codesFrom(array $errors): array
    {
        $codes = [];

        foreach ($errors as $error) {
            $code = $error['extensions']['code'] ?? null;

            if (is_string($code) && $code !== '' && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /**
     * @param array<int, array<string, mixed>> $errors
     */
    protected static function buildMessage(array $errors): string
    {
        $messages = [];

        foreach ($errors as $error) {
            $message = $error['message'] ?? null;

            if (is_string($message) && $message !== '') {
                $messages[] = $message;
            }
        }

        return $messages === []
            ? 'Thinkific GraphQL request failed with an unspecified error.'
            : implode(' | ', $messages);
    }
}
