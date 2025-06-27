<?php


namespace WooNinja\ThinkificSaloon\GraphQL\Connectors;

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

class ThinkificConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasRateLimits;

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
     * This is a work around as Thinkific using point value, thus it is not based on time
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
     * Retry is handled via  point value. Returned in array at end of GraphQL response.
     *
     * @param Response $response
     * @param Limit $limit
     * @return void
     * @throws \JsonException
     */
    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() !== 429) {
            return;
        }

        $limit->exceeded(
            releaseInSeconds: RetryAfterHelper::parse($response->json('extensions.rateLimit.resetAt', time() + 60)),
        );
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
