<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Responses;

use JsonException;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Exceptions\LimitException;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;

class ThinkificGraphQLResponse extends Response
{
    /**
     * @throws RateLimitReachedException
     * @throws LimitException
     * @throws JsonException
     */
    public function json(string|int|null $key = null, mixed $default = null): mixed
    {
        $data = parent::json($key, $default);

        if ($this->isRateLimitError($data)) {

            /**
             * @var \WooNinja\ThinkificSaloon\GraphQL\Connectors\ThinkificConnector $connector
             */
            $connector = $this->getConnector();
            $limit = $connector?->getLimits() ?? null;

            if (!empty($limit) && is_array($limit)) {

                $limit = $limit[0];

                throw new RateLimitReachedException($limit);
            }

        }

        return $data;
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