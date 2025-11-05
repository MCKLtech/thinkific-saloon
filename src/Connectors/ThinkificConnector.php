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
use Saloon\Traits\Plugins\HasTimeout;
use WooNinja\ThinkificSaloon\Senders\ProxySender;
use Saloon\Contracts\Sender;
use Saloon\Config;
use WooNinja\ThinkificSaloon\Traits\HasProxies;

class ThinkificConnector extends Connector implements HasPagination
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasRateLimits;
    use HasProxies;

    public bool|RateLimitStore $rateStore = false;

    public int $rateLimit = 120;

    public string $base_url = 'https://api.thinkific.com/api/public/v1/';

    public string $limiter_prefix = '';

    public function __construct(
        protected string $subdomain
    )
    {

    }

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

    /**
     * Return the limiter prefix name
     *
     * @return string
     */
    public function getLimiterPrefixName(): string
    {
        return $this->getLimiterPrefix();
    }

    /**
     * Dynamically set the limiter prefix name
     *
     * @param string $prefix
     * @return void
     */
    public function setLimiterPrefixName(string $prefix): void
    {
        $this->limiter_prefix = $prefix;
    }

    protected function getLimiterPrefix(): ?string
    {
        if (empty($this->limiter_prefix)) {
            return "{$this->subdomain}";
        }
        return $this->limiter_prefix;
    }

    /**
     * Rate limits for Thinkific. Default to 120/requests per minute.
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

            private int $pageItemsKey;
            private array $pageItems;
            protected ?int $perPageLimit = 50;

            /**
             * Override count to use async to avoid loading each page in a loop
             *
             * @return int
             */
            public function count() : int
            {
                $this->async();

                $count = parent::count();

                $this->async(false);

                return $count;
            }

            /**
             * The total number of results as indicated by the pagination meta from the API
             * Important: You must make at least one API call before calling this e.g. count($pages)
             *
             * @return int
             */
            public function getTotalAPIResults(): int
            {
                return $this->currentResponse->json('meta.pagination.total_items');
            }

            /**
             * The total number of pages as indicated by the pagination meta from the API
             * Important: You must make at least one API call before calling this e.g. count($pages)
             *
             * @return int
             */
            public function getTotalAPIPages(): int
            {
                return $this->currentResponse->json('meta.pagination.total_pages');
            }

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
                /**
                 * This is a workaround to avoid a double API call when using the paginator.
                 * @see https://github.com/saloonphp/saloon/discussions/449
                 */
                $cacheKey = spl_object_id($response);

                if (isset($this->pageItemsKey) && $this->pageItemsKey === $cacheKey) {
                    return $this->pageItems;
                }

                $this->pageItemsKey = $cacheKey;
                $this->pageItems = $response->dtoOrFail();
                return $this->pageItems;
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
