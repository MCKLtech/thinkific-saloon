<?php


namespace WooNinja\ThinkificSaloon\Connectors;

use ReflectionClass;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
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
use WooNinja\ThinkificSaloon\Exceptions\CloudflareException;

class ThinkificConnector extends Connector implements HasPagination
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasRateLimits;
    use HasProxies;
    use HasTimeout;

    protected int $connectTimeout = 10;

    protected int $requestTimeout = 30;

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
            'User-Agent' => 'WooNinja/Saloon-PHP-SDK',
            'Accept-Encoding' => 'gzip',
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'allow_redirects' => false,
        ];
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
     * Sync the local rate-limit counter with the authoritative remaining count
     * returned by Thinkific in every REST response. This ensures the shared
     * Redis store stays accurate across parallel Laravel queue workers rather
     * than relying on the naive +1 increment from Saloon's pipeline alone.
     */
    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onResponse(function (Response $response): Response {
            $remaining = $response->header('ratelimit-remaining');

            if ($remaining === null) {
                return $response;
            }

            $limitHeader = $response->header('ratelimit-limit');
            $effectiveLimit = $limitHeader !== null ? (int) $limitHeader : $this->rateLimit;
            $used = max(0, $effectiveLimit - (int) $remaining);

            $resetHeader = $response->header('ratelimit-reset');
            if ($resetHeader !== null) {
                $resetValue = (int) $resetHeader;
                $resetTimestamp = $resetValue > 10_000_000_000
                    ? (int) ($resetValue / 1000)
                    : $resetValue;
            } else {
                $resetTimestamp = time() + 60;
            }

            $ttl = max(1, $resetTimestamp - time());
            $store = $this->rateLimitStore();

            foreach ($this->getLimits() as $limit) {
                if ($limit->usesResponse()) {
                    continue; // skip the custom "too many attempts" limiter
                }

                $store->set(
                    key: $limit->getName(),
                    value: json_encode(['timestamp' => $resetTimestamp, 'hits' => $used], JSON_THROW_ON_ERROR),
                    ttl: $ttl,
                );
            }

            return $response;
        });
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

    protected function getTooManyAttemptsLimiter(): ?Limit
    {
        return (new Limit(3, 1.0, $this->handleTooManyAttempts(...)))
            ->everySeconds(60, 'custom');
    }

    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() !== 429) {
            return;
        }

        // Thinkific returns ratelimit-reset as a Unix timestamp in milliseconds
        // Convert to seconds and calculate time until reset
        $resetHeader = $response->header('ratelimit-reset');

        if (empty($resetHeader)) {
            // Fallback to 60 seconds if header is missing
            $secondsUntilReset = 60;
        } else {
            $resetValue = (int) $resetHeader;

            // Detect if timestamp is in milliseconds (13 digits) or seconds (10 digits)
            // Timestamps > 10 billion are assumed to be in milliseconds
            if ($resetValue > 10000000000) {
                // Convert milliseconds to seconds
                $resetTimestamp = (int) ($resetValue / 1000);
            } else {
                // Already in seconds
                $resetTimestamp = $resetValue;
            }

            $secondsUntilReset = max(0, $resetTimestamp - time());
        }

        $limit->hit();
        if ($limit->hasReachedLimit()) {
            $limit->exceeded(releaseInSeconds: $secondsUntilReset);
        }
    }

    /**
     * Return a CloudflareException for Cloudflare-specific status codes so that
     * callers can distinguish transient CF errors from real origin failures and
     * implement targeted retry logic. The PHP exception $code is set to the
     * actual HTTP status (e.g. 520) rather than 0.
     */
    public function getRequestException(Response $response, ?\Throwable $senderException): ?\Throwable
    {
        if (CloudflareException::isCloudflareStatus($response->status())) {
            return new CloudflareException($response, previous: $senderException);
        }

        return null;
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
                if ($this->isV2Pagination($this->currentResponse)) {
                    return $this->currentResponse->json('meta.page.total_items') ?? 0;
                }

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
                return $this->getTotalPages($this->currentResponse);
            }

            /**
             * The v2 API (currently only Webhooks) reports pagination under
             * meta.page.{has_next,next_page,page_items,total_items} instead
             * of v1's meta.pagination.{next_page,total_pages,...}.
             */
            private function isV2Pagination(Response $response): bool
            {
                return $response->json('meta.page') !== null;
            }

            /**
             * Without this branch, meta.pagination.next_page is simply
             * missing on a v2 response, is_null() trivially returns true,
             * and the paginator silently stops after page 1 even when more
             * pages exist.
             */
            protected function isLastPage(Response $response): bool
            {
                if ($this->isV2Pagination($response)) {
                    $hasNext = $response->json('meta.page.has_next');

                    if ($hasNext !== null) {
                        return $hasNext === false;
                    }

                    // has_next is unexpectedly absent from an otherwise
                    // v2-shaped response: infer completion from a short
                    // page rather than assuming "last page", which would
                    // silently truncate results.
                    $pageItems = $response->json('meta.page.page_items') ?? 0;

                    return $pageItems < $this->perPageLimit;
                }

                return is_null($response->json('meta.pagination.next_page'));
            }

            protected function getTotalPages(Response $response): int
            {
                if ($this->isV2Pagination($response)) {
                    $totalItems = $response->json('meta.page.total_items') ?? 0;

                    // Use the configured per-page limit rather than this
                    // response's page_items, which reflects only however
                    // many items happened to be on the currently-fetched
                    // page (e.g. a partial last page) and would otherwise
                    // skew the total page count.
                    $perPage = $this->perPageLimit ?: ($response->json('meta.page.page_items') ?? 0);

                    return $perPage > 0 ? (int) ceil($totalItems / $perPage) : 1;
                }

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
