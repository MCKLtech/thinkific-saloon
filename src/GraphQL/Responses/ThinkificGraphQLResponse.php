<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Responses;

use JsonException;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Exceptions\LimitException;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use WooNinja\ThinkificSaloon\GraphQL\Connectors\ThinkificConnector;

class ThinkificGraphQLResponse extends Response
{
    /**
     * @throws RateLimitReachedException
     * @throws LimitException
     * @throws JsonException
     */
    public function json(string|int|null $key = null, mixed $default = null): mixed
    {
        /**
         * Check the response for rate limit errors
         */
        $checkData = parent::json();

        if ($this->isRateLimitError($checkData)) {

            /**
             * @var ThinkificConnector $connector
             */
            $connector = $this->getConnector();
            $limit = $connector?->getLimits() ?? null;

            if (!empty($limit) && is_array($limit)) {

                $limit = $limit[0];

                $limit->setExpiryTimestamp(time() + 60); // Set expiry to 60 seconds from now

                throw new RateLimitReachedException($limit);
            }

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