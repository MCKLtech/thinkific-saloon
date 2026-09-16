<?php

declare(strict_types=1);

namespace WooNinja\ThinkificSaloon\GraphQL\Exceptions;

/**
 * The per-minute cost budget is exhausted. Thinkific reports this as HTTP 200
 * with `RATE_LIMITED`; the budget refills at `resetAt`.
 *
 * The connector's rate limiter also records this response, so once its strike
 * threshold is reached it will throw Saloon's RateLimitReachedException from
 * the plugin instead of this class. Catch both when retrying.
 */
class GraphQLRateLimitedException extends GraphQLException
{
    public const CODE = 'RATE_LIMITED';

    /**
     * ISO-8601 timestamp at which the budget resets (`extensions.rateLimit.resetAt`).
     */
    public function getResetAt(): ?string
    {
        $resetAt = $this->getRateLimit()['resetAt'] ?? null;

        return is_string($resetAt) ? $resetAt : null;
    }

    /**
     * Seconds until the budget resets, never less than 1. Defaults to 60 when
     * the response did not include a parseable `resetAt`.
     */
    public function getSecondsUntilReset(): int
    {
        $resetAt = $this->getResetAt();
        $timestamp = $resetAt !== null ? strtotime($resetAt) : false;

        return $timestamp === false ? 60 : max(1, $timestamp - time());
    }

    public function getRemaining(): ?int
    {
        $remaining = $this->getRateLimit()['remaining'] ?? null;

        return is_numeric($remaining) ? (int) $remaining : null;
    }
}
