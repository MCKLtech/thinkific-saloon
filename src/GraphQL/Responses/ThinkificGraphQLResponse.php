<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Responses;

use JsonException;
use Random\RandomException;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Exceptions\LimitException;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use WooNinja\ThinkificSaloon\GraphQL\Connectors\ThinkificConnector;

class ThinkificGraphQLResponse extends Response
{
    /**
     * @throws RateLimitReachedException
     * @throws LimitException
     * @throws JsonException|RandomException
     */
    public function json(string|int|null $key = null, mixed $default = null): mixed
    {
        /**
         * Check the response for rate limit errors
         * Wrap in try-catch because 429 responses may contain HTML instead of JSON
         */
        try {
            $checkData = parent::json();

            if ($this->isRateLimitError($checkData)) {

                /**
                 * @var ThinkificConnector $connector
                 */
                $connector = $this->getConnector();
                $limit = $connector?->getLimits() ?? null;

                if (!empty($limit) && is_array($limit)) {

                    $limit = $limit[0];

                    // Calculate release time based on resetAt from response
                    $resetAt = $checkData['extensions']['rateLimit']['resetAt'] ?? null;
                    if ($resetAt) {
                        $resetTimestamp = strtotime($resetAt);
                        if ($resetTimestamp !== false) {
                            $secondsUntilReset = max(0, $resetTimestamp - time());
                        } else {
                            $secondsUntilReset = 60;
                        }
                    } else {
                        $secondsUntilReset = 60;
                    }

                    // Add jitter only if we don't have a precise reset time (thundering herd protection)
                    if ($resetAt === null) {
                        $secondsUntilReset = (int) (($secondsUntilReset / 2) + random_int(0, (int) ($secondsUntilReset / 2)));
                    }

                    $limit->setExpiryTimestamp(time() + $secondsUntilReset);

                    throw new RateLimitReachedException($limit);
                }
            }
        } catch (\JsonException $e) {
            // If we can't parse JSON (e.g., HTML error page on 429), 
            // just continue and let parent::json() handle it (or throw)
        }

        return parent::json($key, $default);
    }

    protected function isRateLimitError(array $data): bool
    {
        if (!isset($data['errors']) || !is_array($data['errors'])) {
            return false;
        }

        foreach ($data['errors'] as $error) {
            if (
                ($error['message'] ?? null) === 'API rate limit exceeded' ||
                ($error['extensions']['code'] ?? null) === 'RATE_LIMITED'
            ) {
                return true;
            }
        }

        return false;
    }
}