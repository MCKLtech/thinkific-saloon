<?php


namespace WooNinja\ThinkificSaloon\Connectors;

use ReflectionClass;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\PagedPaginator;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\MemoryStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class ThinkificConnector extends Connector implements HasPagination
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasRateLimits;

    public bool|RateLimitStore $rateStore = false;

    public int $rateLimit = 120;

    public function __construct(
        protected string $subdomain
    )
    {

    }

    public function resolveBaseUrl(): string
    {
        return 'https://api.thinkific.com/api/public/v1/';
    }

    protected function defaultHeaders(): array
    {

        return [
            'User-Agent' => 'WooNinja/Saloon-PHP-SDK'
        ];
    }

    protected function defaultConfig(): array
    {
        return [];
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

    /**
     * Set the rate limit. Thinkific defaults to 120 requests per minute.
     * For Thinkific Plus, this can be increased to 1,000 requests per minute. (Approval is required from Thinkific)
     *
     * @param int $limit
     * @return void
     */
    public function setRateLimit(int $limit): void
    {
        $this->rateLimit = $limit;
    }

    protected function getLimiterPrefix(): ?string
    {
        return (new ReflectionClass($this))->getShortName()
            . ':subdomain_'.$this->subdomain;
    }

    protected function resolveLimits(): array
    {
        return [
            Limit::allow(requests: $this->rateLimit)->everyMinute()
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        if ($this->rateStore) return $this->rateStore;

        return new MemoryStore();
    }

    public function paginate(Request $request): PagedPaginator
    {
        return new class(connector: $this, request: $request) extends PagedPaginator {
            protected ?int $perPageLimit = 100;

            protected function isLastPage(Response $response): bool
            {
                return is_null($response->json('meta.pagination.next_page'));
            }

            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->dto();
            }
        };
    }


}
