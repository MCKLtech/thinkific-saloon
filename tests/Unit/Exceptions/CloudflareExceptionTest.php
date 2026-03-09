<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Exceptions;

use Saloon\Exceptions\Request\ServerException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use WooNinja\ThinkificSaloon\Exceptions\CloudflareException;
use WooNinja\ThinkificSaloon\Requests\Users\Get;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class CloudflareExceptionTest extends TestCase
{
    // -----------------------------------------------------------------------
    // CloudflareException::isCloudflareStatus()
    // -----------------------------------------------------------------------

    public function test_is_cloudflare_status_returns_true_for_known_codes(): void
    {
        foreach (CloudflareException::CLOUDFLARE_STATUS_CODES as $status) {
            $this->assertTrue(
                CloudflareException::isCloudflareStatus($status),
                "Expected {$status} to be recognised as a Cloudflare status"
            );
        }
    }

    public function test_is_cloudflare_status_returns_false_for_standard_http_codes(): void
    {
        foreach ([200, 400, 401, 403, 404, 422, 429, 500, 503, 504] as $status) {
            $this->assertFalse(
                CloudflareException::isCloudflareStatus($status),
                "Expected {$status} NOT to be recognised as a Cloudflare status"
            );
        }
    }

    // -----------------------------------------------------------------------
    // Exception hierarchy
    // -----------------------------------------------------------------------

    public function test_cloudflare_exception_extends_server_exception(): void
    {
        $this->assertTrue(is_a(CloudflareException::class, ServerException::class, true));
    }

    // -----------------------------------------------------------------------
    // Connector throws CloudflareException on 520
    // -----------------------------------------------------------------------

    public function test_connector_throws_cloudflare_exception_on_520(): void
    {
        MockClient::global([
            Get::class => MockResponse::make(['error' => 'origin error'], 520),
        ]);

        $this->expectException(CloudflareException::class);

        $this->service->users->get(1);
    }

    /**
     * @dataProvider cloudflareStatusCodeProvider
     */
    public function test_cloudflare_exception_code_matches_http_status(int $status): void
    {
        MockClient::global([
            Get::class => MockResponse::make([], $status),
        ]);

        try {
            $this->service->users->get(1);
            $this->fail("Expected CloudflareException for status {$status}");
        } catch (CloudflareException $e) {
            $this->assertSame(
                $status,
                $e->getCode(),
                "getCode() should return the HTTP status {$status}, got {$e->getCode()}"
            );
            $this->assertSame(
                $status,
                $e->getStatus(),
                "getStatus() should return {$status}"
            );
        }
    }

    public static function cloudflareStatusCodeProvider(): array
    {
        return array_map(
            fn(int $status) => [$status],
            CloudflareException::CLOUDFLARE_STATUS_CODES
        );
    }

    public function test_cloudflare_exception_message_contains_status(): void
    {
        MockClient::global([
            Get::class => MockResponse::make('error code: 520', 520),
        ]);

        try {
            $this->service->users->get(1);
            $this->fail('Expected CloudflareException');
        } catch (CloudflareException $e) {
            $this->assertStringContainsString('520', $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Non-Cloudflare errors are NOT wrapped in CloudflareException
    // -----------------------------------------------------------------------

    public function test_standard_500_does_not_throw_cloudflare_exception(): void
    {
        MockClient::global([
            Get::class => MockResponse::make([], 500),
        ]);

        try {
            $this->service->users->get(1);
            $this->fail('Expected an exception for status 500');
        } catch (CloudflareException $e) {
            $this->fail('500 should not produce a CloudflareException');
        } catch (ServerException $e) {
            // Correct — a standard ServerException is fine
            $this->assertSame(500, $e->getStatus());
        }
    }

    public function test_standard_503_does_not_throw_cloudflare_exception(): void
    {
        MockClient::global([
            Get::class => MockResponse::make([], 503),
        ]);

        try {
            $this->service->users->get(1);
            $this->fail('Expected an exception for status 503');
        } catch (CloudflareException $e) {
            $this->fail('503 should not produce a CloudflareException');
        } catch (ServerException $e) {
            $this->assertSame(503, $e->getStatus());
        }
    }
}