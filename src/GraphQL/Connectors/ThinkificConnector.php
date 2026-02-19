<?php


namespace WooNinja\ThinkificSaloon\GraphQL\Connectors;

use Saloon\Config;
use Saloon\Contracts\Sender;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Helpers\RetryAfterHelper;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\MemoryStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use WooNinja\ThinkificSaloon\GraphQL\Responses\ThinkificGraphQLResponse;
use WooNinja\ThinkificSaloon\Senders\ProxySender;
use WooNinja\ThinkificSaloon\Traits\HasProxies;

class ThinkificConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasRateLimits;
    use HasProxies;

    public string $base_url = 'https://api.thinkific.com/stable/graphql';

    public bool|RateLimitStore $rateStore = false;

    protected ?string $response = ThinkificGraphQLResponse::class;

    /**
     * Thinkific Rate Limit (Point Value Complexity)
     *
     * @var int
     */
    public int $rateLimit = 10000;


    public function resolveBaseUrl(): string
    {
        return $this->base_url;
    }

    protected function defaultSender(): ProxySender|Sender
    {
        if ($this->isUsingProxy()) {
            return new ProxySender($this->getProxyUrl(), true);
        }

        return Config::getDefaultSender();
    }

    protected function defaultHeaders(): array
    {
        return [
            'User-Agent' => 'WooNinja/Saloon-GraphQL-SDK'
        ];
    }

    protected function defaultConfig(): array
    {
        return [];
    }

    /**
     * Dynamically change the URL
     *
     * @param string $url
     * @return void
     */
    public function setBaseURL(string $url): void
    {
        $this->base_url = $url;
    }

    public function setRateLimit(int $limit): void
    {
        $this->rateLimit = $limit;
    }

    /**
     * Rate limit for Thinkific.
     * This is a workaround as Thinkific using point value, thus it is not based on time
     * @see https://support.thinkific.dev/hc/en-us/articles/22113098742935-GraphQL-Query-Limitations#h_01HS26E3HHVSZVG5Z2EZMKQHCM
     *
     * @return array
     */
    protected function resolveLimits(): array
    {
        return [
            Limit::allow(requests: $this->rateLimit, threshold: 0.99)
                ->everyMinute()
                ->name($this->getLimiterPrefix())
        ];
    }

    /**
     * Retry is handled via point value. Returned in array at end of GraphQL response.
     *
     * @param Response $response
     * @param Limit $limit
     * @return void
     */
    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() !== 429) {
            return;
        }

        $releaseInSeconds = 60; // Default fallback
        $hasPreciseResetTime = false;

        // Try to get reset time from JSON response
        try {
            $resetAt = $response->json('extensions.rateLimit.resetAt');
            if ($resetAt) {
                $parsedRelease = RetryAfterHelper::parse($resetAt);
                if ($parsedRelease !== null) {
                    $releaseInSeconds = $parsedRelease;
                    $hasPreciseResetTime = true;
                }
            }
        } catch (\JsonException $e) {
            // If JSON parsing fails (e.g., HTML error page), check for Retry-After header
            $retryAfter = $response->header('Retry-After');
            if ($retryAfter) {
                $parsedRelease = RetryAfterHelper::parse($retryAfter);
                if ($parsedRelease !== null) {
                    $releaseInSeconds = $parsedRelease;
                    $hasPreciseResetTime = true;
                }
            }
        }

        // Add jitter only if we don't have a precise reset time (thundering herd protection)
        if (!$hasPreciseResetTime) {
            $releaseInSeconds = (int) (($releaseInSeconds / 2) + random_int(0, (int) ($releaseInSeconds / 2)));
        }

        $limit->exceeded(releaseInSeconds: $releaseInSeconds);
    }

    /**
     * Dynamically set the RateLimit Store
     * e.g. new LaravelCacheStore(Cache::store(config('cache.default')));
     *
     * @param RateLimitStore $store
     * @return void
     */
    public function setRateStore(RateLimitStore $store): void
    {
        $this->rateStore = $store;
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        if ($this->rateStore) return $this->rateStore;

        return new MemoryStore();
    }
}
