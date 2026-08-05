<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\DataTransferObjects\OAuth;

use InvalidArgumentException;
use WooNinja\ThinkificSaloon\DataTransferObjects\OAuth\Refresh;
use WooNinja\ThinkificSaloon\Requests\OAuth\Refresh as RefreshRequest;
use WooNinja\ThinkificSaloon\Tests\TestCase;

class RefreshTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Valid subdomains are accepted
    // -----------------------------------------------------------------------

    /**
     * @dataProvider validSubdomainProvider
     */
    public function test_accepts_valid_subdomains(string $subdomain): void
    {
        $refresh = new Refresh(
            client_id: 'client-id',
            client_secret: 'client-secret',
            refresh_token: 'refresh-token',
            subdomain: $subdomain,
        );

        $this->assertSame($subdomain, $refresh->subdomain);
    }

    public static function validSubdomainProvider(): array
    {
        return [
            'simple' => ['acme'],
            'with-hyphen' => ['acme-university'],
            'alphanumeric' => ['acme123'],
            'uppercase' => ['ACME'],
        ];
    }

    // -----------------------------------------------------------------------
    // Malicious / malformed subdomains are rejected
    // -----------------------------------------------------------------------

    /**
     * @dataProvider maliciousSubdomainProvider
     */
    public function test_rejects_subdomains_that_could_redirect_the_request(string $subdomain): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Refresh(
            client_id: 'client-id',
            client_secret: 'client-secret',
            refresh_token: 'refresh-token',
            subdomain: $subdomain,
        );
    }

    public static function maliciousSubdomainProvider(): array
    {
        return [
            'fragment injection' => ['evil.com#'],
            'query injection' => ['evil.com?x='],
            'path injection' => ['evil.com/path'],
            'userinfo injection' => ['acme@evil.com'],
            'empty string' => [''],
            'whitespace' => ['acme university'],
            'protocol relative' => ['//evil.com'],
        ];
    }

    // -----------------------------------------------------------------------
    // The resulting request always targets thinkific.com
    // -----------------------------------------------------------------------

    public function test_resolved_endpoint_always_targets_thinkific_domain(): void
    {
        $refresh = new Refresh(
            client_id: 'client-id',
            client_secret: 'client-secret',
            refresh_token: 'refresh-token',
            subdomain: 'acme-university',
        );

        $request = new RefreshRequest($refresh);

        $this->assertSame(
            'https://acme-university.thinkific.com/oauth2/token',
            $request->resolveEndpoint()
        );
    }
}