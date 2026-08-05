<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Connectors;

use PHPUnit\Framework\TestCase;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use Saloon\RateLimitPlugin\Stores\MemoryStore;
use WooNinja\ThinkificSaloon\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\Requests\Users\Get;

/**
 * Exercises ThinkificConnector's rate limit handling against a real header
 * sample captured from a live `GET /users?limit=1` call on 2026-08-05:
 *
 *   x-ratelimit-remaining-minute: 118
 *   ratelimit-reset:              1785947700000
 *   ratelimit-remaining:          118
 *   ratelimit-limit:              120
 *   x-ratelimit-limit-minute:     120
 *
 * The ratelimit-reset value was exactly the next whole-minute boundary on
 * Thinkific's clock (41 seconds after the response's own Date header), and
 * the x-ratelimit-*-minute headers were exact duplicates of the
 * non-prefixed pair, not a separate signal - both are included in the
 * fixture below to confirm the connector isn't confused by having both
 * present, and it only reads the non-prefixed pair.
 *
 * Thinkific's 429 behaviour also differs from GraphQL: REST returns a real
 * HTTP 429, whereas GraphQL returns HTTP 200 with an error body.
 *
 * Each test uses a unique subdomain as the limiter prefix, and MemoryStore
 * is cleared in setUp, because MemoryStore's backing array is a process-wide
 * static shared by every instance.
 */
class RateLimitTest extends TestCase
{
    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        MemoryStore::clear();
    }

    private function makeConnector(int $rateLimit = 120): ThinkificConnector
    {
        // Unique subdomain per test so limiter keys can never collide even
        // if a future change makes MemoryStore genuinely per-instance.
        $connector = new ThinkificConnector('acme-' . (++self::$counter));
        $connector->rateLimit = $rateLimit;

        return $connector;
    }

    /**
     * Builds a realistic Thinkific rate limit header set, anchored to the
     * live sample above. $secondsUntilReset defaults to 41 to match the
     * captured sample; the reset is computed relative to "now" so tests
     * stay valid regardless of when they run.
     */
    private function rateLimitHeaders(int $remaining, int $limit = 120, int $secondsUntilReset = 41): array
    {
        $resetMs = (string) ((time() + $secondsUntilReset) * 1000);

        return [
            'ratelimit-limit' => (string) $limit,
            'ratelimit-remaining' => (string) $remaining,
            'ratelimit-reset' => $resetMs,
            'x-ratelimit-limit-minute' => (string) $limit,
            'x-ratelimit-remaining-minute' => (string) $remaining,
        ];
    }

    private function storedHits(ThinkificConnector $connector): ?int
    {
        foreach ($connector->getLimits() as $limit) {
            if (!$limit->usesResponse()) {
                $limit->update($connector->rateLimitStore());

                return $limit->getHits();
            }
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // Local counter is synced from Thinkific's authoritative headers
    // -----------------------------------------------------------------------

    public function test_local_counter_is_synced_from_ratelimit_remaining_header_not_a_naive_increment(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        $mockClient = new MockClient([
            // Server reports 100 already used elsewhere (e.g. another
            // worker), even though this is the *first* request this
            // connector instance has made.
            Get::class => MockResponse::make(['id' => 1], 200, $this->rateLimitHeaders(remaining: 20)),
        ]);

        $connector->send(new Get(1), $mockClient);

        // A naive "+1 per request" counter would show 1 hit. The synced
        // counter should reflect the server's authoritative usage: 120 - 20 = 100.
        $this->assertSame(100, $this->storedHits($connector));
    }

    public function test_local_counter_tracks_decreasing_remaining_across_requests(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        $mockClient = new MockClient([
            MockResponse::make(['id' => 1], 200, $this->rateLimitHeaders(remaining: 119)),
            // Matches the live sample: 118 remaining out of 120.
            MockResponse::make(['id' => 1], 200, $this->rateLimitHeaders(remaining: 118)),
        ]);

        $connector->send(new Get(1), $mockClient);
        $this->assertSame(1, $this->storedHits($connector));

        $connector->send(new Get(1), $mockClient);
        $this->assertSame(2, $this->storedHits($connector));
    }

    public function test_responses_without_rate_limit_headers_fall_back_to_a_naive_increment(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        $mockClient = new MockClient([
            Get::class => MockResponse::make(['id' => 1], 200),
        ]);

        $connector->send(new Get(1), $mockClient);

        // The header-based sync in ThinkificConnector::boot() only runs when
        // 'ratelimit-remaining' is present, but the underlying rate-limit-plugin
        // still counts every response with its own naive +1 regardless -
        // so the connector never goes completely unprotected even if
        // Thinkific ever omits the headers on a particular response.
        $this->assertSame(1, $this->storedHits($connector));
    }

    // -----------------------------------------------------------------------
    // Preemptive self-throttling once the threshold is reached
    // -----------------------------------------------------------------------

    public function test_requests_are_blocked_locally_once_the_threshold_is_reached_without_a_429(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        $mockClient = new MockClient([
            // threshold is 0.99 * 120 = 118.8, so remaining=1 (used=119)
            // should already be enough to block the *next* request locally.
            Get::class => MockResponse::make(['id' => 1], 200, $this->rateLimitHeaders(remaining: 1)),
        ]);

        $connector->send(new Get(1), $mockClient);

        $this->assertTrue($connector->hasReachedRateLimit());

        $this->expectException(RateLimitReachedException::class);

        // No mock response is queued for this second call - if the
        // connector doesn't block it locally, MockClient will throw for an
        // unexpected request instead of the expected RateLimitReachedException.
        $connector->send(new Get(1), $mockClient);
    }

    public function test_requests_are_not_blocked_below_the_threshold(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        $mockClient = new MockClient([
            Get::class => MockResponse::make(['id' => 1], 200, $this->rateLimitHeaders(remaining: 119)),
        ]);

        $connector->send(new Get(1), $mockClient);

        $this->assertFalse($connector->hasReachedRateLimit());
    }

    // -----------------------------------------------------------------------
    // 429 handling
    // -----------------------------------------------------------------------

    public function test_a_429_response_is_thrown_to_the_caller(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        $mockClient = new MockClient([
            Get::class => MockResponse::make(['error' => 'rate limited'], 429, $this->rateLimitHeaders(remaining: 0)),
        ]);

        $this->expectException(ClientException::class);

        $connector->send(new Get(1), $mockClient);
    }

    public function test_a_single_429_with_headers_self_throttles_via_the_ordinary_limit_not_the_429_counter(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        // In practice a 429 always arrives with ratelimit-remaining: 0
        $mockClient = new MockClient([
            Get::class => MockResponse::make(['error' => 'rate limited'], 429, $this->rateLimitHeaders(remaining: 0)),
        ]);

        try {
            $connector->send(new Get(1), $mockClient);
            $this->fail('Expected the request to throw for the 429 response.');
        } catch (ClientException) {
            // expected
        }

        // ThinkificConnector's too-many-attempts limiter requires 3 hits
        // (allow: 3) before it self-throttles on its own, so it would not
        // yet consider a single 429 "exceeded". But that's not actually a
        // gap in practice: a 429 response's own headers report
        // ratelimit-remaining: 0, and ThinkificConnector::boot() syncs that
        // straight into the *ordinary* per-minute limit's hit count
        // (0 remaining -> 120 used, comfortably over the 0.99 * 120
        // threshold). So the connector is already correctly blocked -
        // just via the header sync, not the 429 counter.
        $this->assertTrue($connector->hasReachedRateLimit());

        $this->expectException(RateLimitReachedException::class);

        $connector->send(new Get(1), $mockClient);
    }

    public function test_a_single_429_without_headers_does_not_yet_self_throttle(): void
    {
        $connector = $this->makeConnector(rateLimit: 120);

        $mockClient = new MockClient([
            MockResponse::make(['error' => 'rate limited'], 429),
            MockResponse::make(['id' => 1], 200),
        ]);

        try {
            $connector->send(new Get(1), $mockClient);
            $this->fail('Expected the first request to throw for the 429 response.');
        } catch (ClientException) {
            // expected
        }

        // This is the one real gap: without ratelimit-remaining to sync
        // from, the only signal is the too-many-attempts limiter, which
        // needs 3 hits (allow: 3) before it self-throttles - so a 429 that
        // (unusually) arrives without rate limit headers does not stop the
        // very next request from going out over the wire.
        $this->assertFalse($connector->hasReachedRateLimit());

        $response = $connector->send(new Get(1), $mockClient);
        $this->assertSame(200, $response->status());
    }
}