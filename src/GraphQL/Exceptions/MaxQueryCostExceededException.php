<?php

declare(strict_types=1);

namespace WooNinja\ThinkificSaloon\GraphQL\Exceptions;

/**
 * A single request cost more than Thinkific's per-request cap (1000 points by
 * default). The query is rejected outright and nothing is charged against the
 * per-minute budget, so retrying without shrinking the page sizes will fail
 * identically. Reduce `first` on the outermost or nested connections (for
 * quiz/survey submissions, `answers_per_page` multiplies per submission).
 *
 * @see https://support.thinkific.dev/hc/en-us/articles/22113098742935-GraphQL-Query-Limitations
 */
class MaxQueryCostExceededException extends GraphQLException
{
    public const CODE = 'MAX_QUERY_COST_EXCEEDED';

    /**
     * The calculated cost of the rejected query (`extensions.rateLimit.cost`).
     */
    public function getCost(): ?int
    {
        $cost = $this->getRateLimit()['cost'] ?? null;

        return is_numeric($cost) ? (int) $cost : null;
    }

    /**
     * The per-request cap the query exceeded (`errors[].extensions.maxCost`).
     */
    public function getMaxCost(): ?int
    {
        foreach ($this->errors as $error) {
            if (($error['extensions']['code'] ?? null) !== self::CODE) {
                continue;
            }

            $maxCost = $error['extensions']['maxCost'] ?? null;

            if (is_numeric($maxCost)) {
                return (int) $maxCost;
            }
        }

        return null;
    }
}
