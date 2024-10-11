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

    public string $base_url = 'https://api.thinkific.com/api/public/v1/';

    public function __construct(
        protected string $subdomain
    )
    {

    }

    public function resolveBaseUrl(): string
    {
        return $this->base_url;
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
     * Dynamically change the URL
     *
     * @param string $url
     * @return void
     */
    public function setBaseURL(string $url): void
    {
        $this->base_url = $url;
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
     * Dynamically set the rate limit. All Thinkific plans default to 120 requests per minute.
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
        return "thinkific_subdomain_{$this->subdomain}";
    }

    /**
     * Rate limits for Thinkific. Default to 120/requests per minute.
     *
     * @return array
     */
    protected function resolveLimits(): array
    {
        return [
            Limit::allow(requests: $this->rateLimit, threshold: 0.95)->everyMinute()
        ];
    }

    /**
     * The Rate Limit Store for Saloon
     *
     * @return RateLimitStore
     */
    protected function resolveRateLimitStore(): RateLimitStore
    {
        if ($this->rateStore) return $this->rateStore;

        return new MemoryStore();
    }

    /**
     * Pagination configuration for Thinkific
     *
     * @param Request $request
     * @return PagedPaginator
     */
    public function paginate(Request $request): PagedPaginator
    {
        $paginator = new class(connector: $this, request: $request) extends PagedPaginator {

            protected ?int $perPageLimit = 100;

            protected function isLastPage(Response $response): bool
            {
                return is_null($response->json('meta.pagination.next_page'));
            }

            protected function getTotalPages(Response $response): int
            {
                return $response->json('meta.pagination.total_pages');
            }

            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->dto();
            }

            protected function applyPagination(Request $request): Request
            {
                $request->query()->add('page', $this->page);

                $filters = $request->query()->all();

                $this->setPerPageLimit($filters['limit'] ?? $this->perPageLimit);

                if (is_numeric($this->perPageLimit)) {
                    $request->query()->add('limit', $this->perPageLimit);
                }

                return $request;
            }

        };

        $filters = $request->query()->all();

        $paginator->setStartPage($filters['start_page'] ?? 1);

        $paginator->setPerPageLimit($filters['limit'] ?? 100);

        /**
         * @see https://github.com/saloonphp/saloon/issues/432
         */
        $paginator->rewind();

        if (isset($filters['max_pages'])) {

            /**
             * We add on the max_pages otherwise we may already be at the 'max' page
             */
            $currentPage = $paginator->getCurrentPage();

            $paginator->setMaxPages($currentPage + $filters['max_pages']);

            /**
             * One good rewind deserves another
             */
            $paginator->rewind();
        }

        return $paginator;
    }


}
