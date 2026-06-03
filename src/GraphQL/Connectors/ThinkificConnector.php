<?php


namespace WooNinja\ThinkificSaloon\GraphQL\Connectors;

use ReflectionClass;
use Saloon\Config;
use Saloon\Contracts\Sender;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\MemoryStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;
use WooNinja\ThinkificSaloon\GraphQL\Responses\ThinkificGraphQLResponse;
use WooNinja\ThinkificSaloon\Senders\ProxySender;
use WooNinja\ThinkificSaloon\Traits\HasProxies;

class ThinkificConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use HasRateLimits;
    use HasProxies;
    use HasTimeout;

    protected int $connectTimeout = 10;

    protected int $requestTimeout = 30;

    public string $base_url = 'https://api.thinkific.com/stable/graphql';

    public bool|RateLimitStore $rateStore = false;

    protected ?string $response = ThinkificGraphQLResponse::class;

    /**
     * Thinkific GraphQL rate limit in cost points per minute.
     *
     * @see https://support.thinkific.dev/hc/en-us/articles/22113098742935
     * @var int
     */
    public int $rateLimit = 2000;

    public string $limiter_prefix = '';

    public function __construct(
        protected ?string $subdomain = null,
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
            'User-Agent' => 'WooNinja/Saloon-GraphQL-SDK',
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

    public function setRateLimit(int $limit): void
    {
        $this->rateLimit = $limit;
    }

    /**
     * Return the limiter prefix name
     */
    public function getLimiterPrefixName(): string
    {
        return $this->getLimiterPrefix();
    }

    /**
     * Dynamically set the limiter prefix name
     */
    public function setLimiterPrefixName(string $prefix): void
    {
        $this->limiter_prefix = $prefix;
    }

    /**
     * When a subdomain or explicit prefix is known, use it as the limiter
     * key to avoid collisions in shared rate-limit stores (e.g. Redis).
     * Falls back to the trait default (class short name) otherwise.
     */
    protected function getLimiterPrefix(): ?string
    {
        if (! empty($this->limiter_prefix)) {
            return $this->limiter_prefix;
        }

        if ($this->subdomain !== null) {
            return $this->subdomain;
        }

        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * Proactive rate limiter using the GraphQL point budget (2000 pts/min by default).
     *
     * Saloon's `requests` parameter is repurposed here as a point budget: after every
     * response, boot() overwrites `hits` in the store with (limit - remaining) from
     * extensions.rateLimit, so the counter always reflects actual cost consumed rather
     * than request count. The 0.99 threshold blocks new requests when ≤20 points remain.
     *
     * High-cost queries that push remaining past zero are caught reactively by
     * handleTooManyAttempts().
     *
     * @see https://support.thinkific.dev/hc/en-us/articles/22113098742935
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
     * Detect GraphQL rate-limit errors, which arrive as HTTP 200 with an
     * errors array rather than a 429. Falls back to HTTP 429 handling for
     * edge cases (e.g. a Cloudflare or proxy layer returning a real 429).
     */
    protected function getTooManyAttemptsLimiter(): ?Limit
    {
        return (new Limit(3, 1.0, $this->handleTooManyAttempts(...)))
            ->everySeconds(60, 'custom');
    }

    protected function handleTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() === 429) {
            $limit->exceeded(releaseInSeconds: 60);
            return;
        }

        try {
            $errors = $response->json('errors');

            if (!is_array($errors)) {
                return;
            }

            $isRateLimited = false;
            foreach ($errors as $error) {
                if (
                    ($error['extensions']['code'] ?? null) === 'RATE_LIMITED' ||
                    ($error['message'] ?? '') === 'API rate limit exceeded.'
                ) {
                    $isRateLimited = true;
                    break;
                }
            }

            if (!$isRateLimited) {
                return;
            }

            $secondsUntilReset = 60;
            $resetAt = $response->json('extensions.rateLimit.resetAt');

            if ($resetAt) {
                $resetTimestamp = strtotime($resetAt);
                if ($resetTimestamp !== false) {
                    $secondsUntilReset = max(1, $resetTimestamp - time());
                }
            }

            $limit->hit();
            if ($limit->hasReachedLimit()) {
                $limit->exceeded(releaseInSeconds: $secondsUntilReset);
            }

        } catch (\JsonException $e) {
            $limit->exceeded(releaseInSeconds: 60);
        }
    }

    /**
     * Sync the local cost-point counter with the authoritative remaining budget
     * returned in every GraphQL response. This keeps the shared Redis store
     * accurate across parallel Laravel queue workers.
     */
    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onResponse(function (Response $response): Response {
            try {
                $rateLimitData = $response->json('extensions.rateLimit');
            } catch (\JsonException $e) {
                return $response;
            }

            if (!is_array($rateLimitData) || !isset($rateLimitData['remaining'])) {
                return $response;
            }

            $remaining = (int) $rateLimitData['remaining'];
            $apiLimit = (int) ($rateLimitData['limit'] ?? $this->rateLimit);
            $used = max(0, $apiLimit - $remaining);

            $resetAt = $rateLimitData['resetAt'] ?? null;
            if ($resetAt) {
                $resetTimestamp = strtotime($resetAt);
                $resetTimestamp = $resetTimestamp !== false ? $resetTimestamp : time() + 60;
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
